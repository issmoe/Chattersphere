<?php
    include 'php/config.php';
    session_start();
    if(isset($_POST['submit'])){ 

        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = mysqli_real_escape_string($conn, md5($_POST['password']));
        // declaring input

        if(filter_var($email, FILTER_VALIDATE_EMAIL)){// checking if email is valid
                
            $select = mysqli_query($conn, "SELECT * FROM user_form WHERE email = '$email' 
            AND password = '$password' ");// checking if user password or email is correct
                if(mysqli_num_rows($select) > 0){
                    $row = mysqli_fetch_assoc($select);
                    $status = 'Active Now';
                    $update = mysqli_query($conn, "UPDATE user_form SET status = '$status' 
                                            WHERE user_id = '{$row['user_id']}' ");
                    if($update){
                        $_SESSION['user_id'] = $row['user_id'];
                        header('location: home.php');
                    }

                }else{
                    $alert[] = "incorrect password or email!";
                }
        }else{
            $alert[] = "$email is not a valid email!" ;
        }
    }
    if(isset($_SESSION['user_id'])){
        header("location: home.php");
    }
 ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>Welcome back</title>
</head>
<body>
    <div class="form-container">
        <form action="" method="post" enctype="multipart/form-data">
        <div class="title-container">
            <img src="images/chattersphere-logo.png" alt="ChatterSphere Logo" class="title-logo">
            <h3>Welcome to ChatterSphere</h3>
        </div>
            <?php 
                if(isset($alert)){
                    foreach($alert as $alert){
                        echo '<div class="alert">'.$alert.'</div>';
                    }
                }
            ?>
            <input type="email" name="email" placeholder="Enter Email" class="box" required>
            <input type="password" name="password" placeholder="Enter Password" class="box" required>
            <input type="submit" name="submit" class="btn" value="Start Chatting">
            <p><a href="forgot_password.php">Forgot your password?</a></p>
            <p>Don't have an account? <a href="index.php">Register now</a></p>
        </form>
    </div>
</body>
</html>