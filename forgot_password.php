<?php
include 'php/config.php';
session_start();

$alert = []; 

if (isset($_POST['submit'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $submitted_friend_name = mysqli_real_escape_string($conn, $_POST['best_friend_name']);

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

         if (empty($submitted_friend_name)) {
             $alert[] = "Please enter your best friend's name.";
         } else {
            // Check if user with this email AND matching best friend name exists
            $select_query = "SELECT user_id FROM user_form WHERE email = ? AND best_friend_name = ?";
            $stmt = mysqli_prepare($conn, $select_query);

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ss', $email, $submitted_friend_name);
                mysqli_stmt_execute($stmt);
                $select_result = mysqli_stmt_get_result($stmt);
                $num_rows = mysqli_num_rows($select_result);
                $user_row = mysqli_fetch_assoc($select_result);
                mysqli_stmt_close($stmt);
            } else {
                $num_rows = 0; // Indicate failure
                 // Log database error
                 error_log("Database error preparing select query: " . mysqli_error($conn));
                 $alert[] = "A database error occurred. Please try again.";
            }


            if ($num_rows > 0) {
                $user_id = $user_row['user_id'];
                // Set a session variable to allow the user to reset their password
                $_SESSION['reset_qa_verified_user_id'] = $user_id;
                $_SESSION['reset_qa_timestamp'] = time(); // Add a timestamp for potential timeout
                // Redirect the user to the reset password page
                header('location: reset_password.php');
                exit(); // exit after a header redirect

            } else {
                 // User not found or best friend's name didn't match
                 if(empty($alert)) { 
                    $alert[] = "Incorrect email or best friend's name.";
                 }
            }
        } 

    } else { // Email format is invalid
        $alert[] = "$email is not a valid email address!" ;
    }
}
// Redirect logged-in users away from this page
if (isset($_SESSION['user_id'])) {
    header("location: home.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>Forgot Password</title>
</head>
<body>
    <div class="form-container">
        <form action="" method="post">
            <h3>Forgot Password</h3>
            <?php
                if (isset($alert)) {
                    foreach ($alert as $msg) {
                        echo '<div class="alert">' . $msg . '</div>';
                    }
                }
            ?>
            <p>Please enter your email and best friend's name to reset your password.</p>
            <input type="email" name="email" placeholder="Enter your email" class="box" required>
            <input type="text" name="best_friend_name" placeholder="Enter your best friend's name" class="box" required>

            <input type="submit" name="submit" class="btn" value="Verify and Reset Password">
            <p>Remember your password?<a href="login.php"> Login here.</a></p>
             
        </form>
    </div>
</body>
</html>