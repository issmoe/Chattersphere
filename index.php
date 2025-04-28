<?php
    include 'php/config.php';
    session_start();
    $image_rename = 'default-avatar.png';
    if(isset($_POST['submit'])){ 
        $ran_id = rand(time(), 1000000000); 

        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = $_POST['password']; 
        $cpassword = $_POST['cpassword']; 
      
        $best_friend_name = mysqli_real_escape_string($conn, $_POST['best_friend_name']);

        if(filter_var($email, FILTER_VALIDATE_EMAIL)){// checking if email is valid
            $image = $_FILES['image']['name'];
            $image_size = $_FILES['image']['size'];
            $image_tmp_name = $_FILES['image']['tmp_name'];
            // Append image name only if an image is uploaded, otherwise use default
            if (!empty($image)) {
                 // Sanitize image name to prevent potential issues
                 $image_extension = pathinfo($image, PATHINFO_EXTENSION);
                 $image_filename = pathinfo($image, PATHINFO_FILENAME);
                 $image_rename = $image_filename . '_' . uniqid() . '.' . $image_extension; 
            }
             $image_folder = 'uploaded_img/'.$image_rename;// image folder
            $status = 'Active Now';//user status
            // Check if email or username already exists
            $select_query = "SELECT * FROM user_form WHERE email = ? OR name = ?";
            $stmt = mysqli_prepare($conn, $select_query);

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ss', $email, $name);
                mysqli_stmt_execute($stmt);
                $select_result = mysqli_stmt_get_result($stmt);
                $num_rows = mysqli_num_rows($select_result);
                $row = mysqli_fetch_assoc($select_result);
                mysqli_stmt_close($stmt);
            } else {
                $num_rows = 0; 
                 error_log("Database error preparing select query during registration: " . mysqli_error($conn));
                 $alert[] = "A database error occurred. Please try again.";
            }

            if($num_rows > 0){
                if(isset($row['email']) && $row['email'] === $email && isset($row['name']) && $row['name'] === $name){
                    $alert[] = "Username and Email already exist!";
                } elseif (isset($row['email']) && $row['email'] === $email){
                    $alert[] = "Email already exists!";
                } elseif (isset($row['name']) && $row['name'] === $name){
                    $alert[] = "Username already exists!";
                }
            } else {
                // Add the password length condition here
                if(strlen($password) <= 8) {
                    $alert[] = "Password must be more than 8 characters long!";
                } elseif ($password != $cpassword){
                    $alert[] = "Password not matched!";
                } elseif($image_size > 0 && $image_size > 2000000){ // Check image size only if file is uploaded
                    $alert[] = "image size is too large!" ;
                } elseif (empty($best_friend_name)) { 
                     $alert[] = "Please enter your best friend's name.";
                }
                else {
                    // Hash the password before insertion
                    //  i used method md5() for security!
                    $hashed_password = mysqli_real_escape_string($conn, md5($password));
                    $insert_query = "INSERT INTO `user_form`(`user_id`, `name`, `email`, `password`, `img`, `status`, `best_friend_name`)
                                     VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt_insert = mysqli_prepare($conn, $insert_query);
                    if ($stmt_insert) {
                        mysqli_stmt_bind_param($stmt_insert, 'issssss', $ran_id, $name, $email, $hashed_password, $image_rename, $status, $best_friend_name);
                        $insert_success = mysqli_stmt_execute($stmt_insert);
                        mysqli_stmt_close($stmt_insert);
                    } else {
                         $insert_success = false; 
                         error_log("Database error preparing registration insert: " . mysqli_error($conn));
                    }
                    if($insert_success){ 
                        if (!empty($image) && $image_size > 0) { 
                           if (!move_uploaded_file($image_tmp_name, $image_folder)) {
                               error_log("Error moving uploaded file to: " . $image_folder);
                           }
                        }
                        header('location: login.php');
                        exit(); 
                    } else {
                        $alert[] = "Connection failed please try again!";
                         if(mysqli_error($conn) && !$stmt_insert) {
                             error_log("Database error during registration insert: " . mysqli_error($conn));
                         }
                    }
                }
            }
        } else {
            $alert[] = "$email is not a valid email!" ;
        }
    }
    if(isset($_SESSION['user_id'])){
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
    <title>Create account</title>
</head>
<body>
    <div class="form-container">
        <form action="" method="post" enctype="multipart/form-data">
            <h3>Create account</h3>
            <?php
                if(isset($alert)){
                    foreach($alert as $msg){ 
                        echo '<div class="alert" style="position: relative; margin-left: auto; margin-right: auto; width: 100%; box-sizing: border-box;">' . htmlspecialchars($msg) . '</div>';
                    }
                }
            ?>
            <input type="text" name="name" placeholder="Enter username" class="box" required>
            <input type="email" name="email" placeholder="Enter email" class="box" required>
            <input type="password" name="password" placeholder="Enter password (More than 8 characters)" class="box" required>
            <input type="password" name="cpassword" placeholder="Confirm password" class="box" required>
            <input type="text" name="best_friend_name" placeholder="Enter your best friend's name" class="box" required>
            <p style="text-align: left;">Add profile picture:</p>
            <input type="file" name="image" class="box" accept="image/*">
            <input type="submit" name="submit" class="btn" value="Start chatting">
            <p>Already have an account? <a href="login.php">Login now</a></p>
        </form>
    </div>
</body>
</html>
