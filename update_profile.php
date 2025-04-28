<?php
    include 'php/config.php';
    session_start();
    $user_id = $_SESSION['user_id'];

    if (!isset($user_id)) {
        header('location: login.php');
        exit();
    }

    $select = mysqli_query($conn, "SELECT * FROM user_form WHERE user_id = '$user_id' ");
    if (mysqli_num_rows($select) > 0) {
        $row = mysqli_fetch_assoc($select);
    } else {
        echo "Error: User data not found.";
        exit();
    }

    $alert = [];

    if (isset($_POST['update_profile'])) {

        $update_name = mysqli_real_escape_string($conn, trim($_POST['update_name']));
        if (!empty($update_name) && $update_name != $row['name']) {
            $update_nm_query = mysqli_query($conn, "UPDATE user_form SET name = '$update_name' WHERE user_id = '$user_id'");
            if ($update_nm_query) {
                $alert[] = "Name updated successfully!";
                $row['name'] = $update_name;
            } else {
                $alert[] = "Failed to update name.";
            }
        }

        $update_email = mysqli_real_escape_string($conn, trim($_POST['update_email']));
        if (!empty($update_email) && $update_email != $row['email']) {
            if (filter_var($update_email, FILTER_VALIDATE_EMAIL)) {
                $update_em_query = mysqli_query($conn, "UPDATE user_form SET email = '$update_email' WHERE user_id = '$user_id'");
                if ($update_em_query) {
                    $alert[] = "Email updated successfully!";
                    $row['email'] = $update_email;
                } else {
                    $alert[] = "Failed to update email.";
                }
            } else {
                $alert[] = "'" . htmlspecialchars($update_email) . "' is not a valid Email!";
            }
        }

        if (isset($_FILES['update_image']) && !empty($_FILES['update_image']['name'])) {
            $image = $_FILES['update_image']['name'];
            $image_size = $_FILES['update_image']['size'];
            $image_tmp_name = $_FILES['update_image']['tmp_name'];
            $image_basename = basename($image);
            $image_folder = 'uploaded_img/' . $image_basename;
            $image_file_type = strtolower(pathinfo($image_folder, PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($image_file_type, $allowed_extensions)) {
                $alert[] = "Invalid image type. Only JPG, JPEG, PNG, GIF allowed.";
            } elseif ($image_size > 2000000) {
                $alert[] = "Image size is too large (Max 2MB)!";
            } else {
                $image_db_name = mysqli_real_escape_string($conn, $image_basename);

                $update_img_query = mysqli_query($conn, "UPDATE user_form SET img = '$image_db_name' WHERE user_id = '$user_id'");

                if ($update_img_query) {
                    if (move_uploaded_file($image_tmp_name, $image_folder)) {
                        $alert[] = "Image updated successfully!";
                        $row['img'] = $image_basename;
                    } else {
                        $alert[] = "Failed to upload image.";
                    }
                } else {
                    $alert[] = "Failed to update image record in database.";
                }
            }
        }

        $old_pass = $_POST['old_pass'];
        $new_pass = $_POST['new_pass'];
        $confirm_pass = $_POST['confirm_pass'];

        if (!empty($old_pass) || !empty($new_pass) || !empty($confirm_pass)) {

            if (empty($old_pass) || empty($new_pass) || empty($confirm_pass)) {
                $alert[] = "To change password, please provide Old, New, and Confirm passwords.";
            } else {
                $old_pass_hashed = md5($old_pass);
                $main_pass_from_db = $row['password'];

                if ($old_pass_hashed != $main_pass_from_db) {
                    $alert[] = "Old password not matched!";
                } elseif ($new_pass != $confirm_pass) {
                    $alert[] = "New and Confirm passwords do not match!";
                } elseif ($old_pass == $new_pass) {
                    $alert[] = "New password must be different from the old password.";
                } else {
                    $new_pass_hashed = md5($new_pass);
                    $update_pass_query = mysqli_query($conn, "UPDATE user_form SET password = '$new_pass_hashed' WHERE user_id = '$user_id'");

                    if ($update_pass_query) {
                        $alert[] = "Password updated successfully!";
                    } else {
                        $alert[] = "Failed to update password.";
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
    <link rel="stylesheet" href="css/style.css">
    <title>Update profile</title>
</head>
<body>
    <div class="update-profile">
        <form action="" method="post" enctype="multipart/form-data">
            <?php
                $image_display = 'uploaded_img/default-avatar.png';
                if (!empty($row['img']) && file_exists('uploaded_img/' . $row['img'])) {
                    $image_display = 'uploaded_img/' . htmlspecialchars($row['img']);
                }
            ?>
            <img src="<?php echo $image_display; ?>" alt="Profile Picture">

            <?php
                if (!empty($alert)) {
                    foreach ($alert as $msg) {
                        echo '<div class="alert">' . htmlspecialchars($msg) . '</div>';
                    }
                }
            ?>
            <div class="flex">
                <div class="inputBox">
                    <span>Username :</span>
                    <input type="text" name="update_name" value="<?php echo htmlspecialchars($row['name']); ?>" class="box">
                    <span>Your Email :</span>
                    <input type="email" name="update_email" value="<?php echo htmlspecialchars($row['email']); ?>" class="box">
                    <span>Update your pfp (Max 2MB)</span>
                    <input type="file" name="update_image" accept="image/jpeg, image/png, image/gif" class="box">
                </div>
                <div class="inputBox">
                    <span>Old password :</span>
                    <input type="password" name="old_pass" placeholder="Enter old password to change" class="box">
                    <span>New password :</span>
                    <input type="password" name="new_pass" placeholder="Enter new password" class="box">
                    <span>Confirm password</span>
                    <input type="password" name="confirm_pass" placeholder="Confirm new password" class="box">
                </div>
            </div>
            <div class="flex btns">
                <input type="submit" value="Update profile" name="update_profile" class="btn">
                <a href="home.php" class="delete-btn">Go back</a>
            </div>
        </form>
    </div>
</body>
</html>