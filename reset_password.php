<?php
include 'php/config.php'; 
session_start();

$alert = []; 
$error_alert = false; 
$success_alert = false; 
$user_id_to_reset = null; // Will hold the user ID if session is valid

//  check session hasn't expired after 5 minutes = 300 seconds
if (isset($_SESSION['reset_qa_verified_user_id']) && isset($_SESSION['reset_qa_timestamp'])) {
    $timeout_seconds = 300; 
    if (time() - $_SESSION['reset_qa_timestamp'] < $timeout_seconds) {
        // Session is valid and not expired
        $user_id_to_reset = $_SESSION['reset_qa_verified_user_id'];
    } else {
        // Session has expired
        $alert[] = "Your password reset session has expired. Please start over.";
        $error_alert = true;
        // Clear session variables
        unset($_SESSION['reset_qa_verified_user_id']);
        unset($_SESSION['reset_qa_timestamp']);
    }
} else {
    $alert[] = "Invalid password reset attempt. Please start the process from the forgot password page.";
    $error_alert = true;
}


// New Password Submission 
if (isset($_POST['submit_new_password']) && $user_id_to_reset !== null) {
    // Get submitted passwords, including the old password
    $old_password = $_POST['old_password']; 
    $new_password = $_POST['password'];
    $cpassword = $_POST['cpassword'];

    //Old Password Verifie
    $fetch_old_password_query = "SELECT password FROM user_form WHERE user_id = ?";
    $stmt_fetch = mysqli_prepare($conn, $fetch_old_password_query);
    $old_password_match = false; 
    if ($stmt_fetch) {
        mysqli_stmt_bind_param($stmt_fetch, 'i', $user_id_to_reset);
        mysqli_stmt_execute($stmt_fetch);
        $result_fetch = mysqli_stmt_get_result($stmt_fetch);
        if ($result_fetch && mysqli_num_rows($result_fetch) > 0) {
            $user_data = mysqli_fetch_assoc($result_fetch);
            $stored_hashed_password = $user_data['password'];
            $submitted_old_hashed = md5($old_password); // md5 method

            if ($submitted_old_hashed === $stored_hashed_password) {
                $old_password_match = true; // Old password verified
            } else {
                $alert[] = "Incorrect old password.";
                $error_alert = true;
            }
        } else {
            $alert[] = "Could not retrieve your data for verification.";
            $error_alert = true;
            error_log("Error fetching user data for old password check for user_id: " . $user_id_to_reset);
        }
        mysqli_stmt_close($stmt_fetch);
    } else {
        $alert[] = "A database error occurred during old password verification.";
        $error_alert = true;
        error_log("Database error preparing old password fetch: " . mysqli_error($conn));
    }

    // old password matched
    if ($old_password_match) {
        // Perform new password validation
        if (strlen($new_password) < 9) {
             $alert[] = "New password must be at least 8 characters long!";
             $error_alert = true;
        } elseif ($new_password != $cpassword) {
            $alert[] = "New passwords do not match!";
            $error_alert = true;
        } else {
            // Hash new password md5 method
            $hashed_new_password = mysqli_real_escape_string($conn, md5($new_password)); 
            $update_query = "UPDATE user_form SET password = ? WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $update_query);

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'si', $hashed_new_password, $user_id_to_reset);
                $update_success = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            } else {
                $update_success = false; 
                error_log("Database error preparing password update: " . mysqli_error($conn));
            }

            if ($update_success) {
                $alert[] = "Your password has been reset successfully. You can now login.";
                $success_alert = true;
                // Clear the session variables after successful 
                unset($_SESSION['reset_qa_verified_user_id']);
                unset($_SESSION['reset_qa_timestamp']);
                $user_id_to_reset = null;
            } else {
                $alert[] = "Error updating password. Please try again.";
                $error_alert = true; 
                if(mysqli_error($conn)) {
                    error_log("Database error during password update: " . mysqli_error($conn));
                }
            }
        }
    } 
} 

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/reset_password.css"> <title>Reset Password</title>
    <style>
        .alert.alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert.alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="form-container"> <?php
            // Display the form ONLY wasn't successful
            if ($user_id_to_reset !== null):
        ?>
            <form action="" method="post"> <h3>Reset Your Password</h3> <?php
                    if (!empty($alert)) {
                        foreach ($alert as $msg) {
                            $alert_div_class = $error_alert ? 'alert alert-error' : 'alert'; 
                            echo '<div class="' . $alert_div_class . '">' . htmlspecialchars($msg) . '</div>';
                        }
                    }
                ?>
                <p>Please enter your old password and set a new one.</p> <input type="password" name="old_password" placeholder="Enter Old Password" class="box" required>
                <input type="password" name="password" placeholder="Enter New Password (min. 8 characters)" class="box" required>
                <input type="password" name="cpassword" placeholder="Confirm New Password" class="box" required>
                <input type="submit" name="submit_new_password" class="btn" value="Set New Password">
                <p>Remembered it?<a href="login.php"> Login here.</a></p>
            </form>
        <?php
            else:
        ?>
            <h3>Reset Password Status</h3> <?php
                // Display success or session expiry messages
                if (!empty($alert)) {
                    foreach ($alert as $msg) {
                        $alert_div_class = $success_alert ? 'alert alert-success' : ($error_alert ? 'alert alert-error' : 'alert');
                        echo '<div class="' . $alert_div_class . '">' . htmlspecialchars($msg) . '</div>';
                    }
                }
            ?>
             <p><a href="login.php">Proceed to Login</a></p>

             <?php
                if ($error_alert && !isset($_SESSION['reset_qa_verified_user_id']) && !$success_alert) {
                    echo '<p><a href="forgot_password.php">Try Forgot Password Again</a></p>';
                }
             ?>
        <?php endif;  ?>

    </div> </body>
</html>