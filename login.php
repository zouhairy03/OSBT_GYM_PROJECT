<?php
session_start();
include 'config.php'; // Include the database connection file

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("SELECT user_id, name, email, role, image FROM users WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Debugging: Check the fetched user data
        var_dump($user);

        // Store the user data in session
        $_SESSION['user'] = [
            'id' => $user['user_id'],  // Ensure this is not NULL
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'image' => $user['image'] ?? 'path/to/default/admin_image.jpg'
        ];

        // Debugging: Check session data
        var_dump($_SESSION);

        // Redirect based on user role
        switch($user['role']) {
            case 'admin':
                header('Location: admin_dashboard.php');
                break;
            case 'trainer':
                header('Location: trainer_dashboard.php');
                break;
            default:
                header('Location: user_dashboard.php');
                break;
        }
        exit();
    } else {
        $error_message = "Invalid email or password.";
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Gym Management System - Login</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 0 auto;
            margin-top: 100px;
        }
        .form-control {
            border-radius: 30px;
        }
        .btn {
            border-radius: 30px;
        }
        .input-group-text {
            cursor: pointer;
        }
        .custom-control-label {
            cursor: pointer;
        }
        .spinner-border {
            display: none;
        }
    </style>
</head>
<body>
<div class="login-container">
    <h2 class="text-center">Gym Management System - Login</h2>
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>
    <form method="POST" action="login.php" onsubmit="showSpinner()">
        <div class="form-group">
            <label for="email">Email:</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                </div>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
        </div>
        <div class="form-group">
            <label for="pwd">Password:</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                </div>
                <input type="password" class="form-control" id="pwd" name="password" required>
                <div class="input-group-append">
                    <span class="input-group-text" onclick="togglePassword()"><i class="fas fa-eye" id="toggleIcon"></i></span>
                </div>
            </div>
        </div>
        <div class="form-group form-check">
            <input type="checkbox" class="form-check-input" id="rememberMe">
            <label class="form-check-label custom-control-label" for="rememberMe">Remember Me</label>
        </div>
        <div class="form-group text-center">
            <button type="submit" class="btn btn-primary btn-block">Login</button>
            <div class="spinner-border text-primary" role="status" id="loadingSpinner">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <div class="form-group text-center">
            <a href="forgot_password.php">Forgot Password?</a>
        </div>
        <div class="form-group text-center">
            <p>or login with</p>
            <a href="login_with_google.php" class="btn btn-danger btn-block"><i class="fab fa-google"></i> Google</a>
            <a href="login_with_facebook.php" class="btn btn-primary btn-block"><i class="fab fa-facebook-f"></i> Facebook</a>
        </div>
    </form>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    function togglePassword() {
        var passwordField = document.getElementById('pwd');
        var toggleIcon = document.getElementById('toggleIcon');
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }

    function showSpinner() {
        document.getElementById('loadingSpinner').style.display = 'inline-block';
    }
</script>
</body>
</html>
