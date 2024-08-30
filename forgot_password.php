<?php
session_start();
include 'config.php'; // Include the database connection file

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    // Check if the email exists in the database
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        // Fetch user data
        $user = $result->fetch_assoc();
        $user_id = $user['user_id']; // Use the correct column name

        if (!$user_id) {
            $error_message = "User ID not found. Please check the database schema.";
        } else {
            // Generate a unique token
            $token = bin2hex(random_bytes(50));

            // Store the token in the database
            $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $token);

            if ($stmt->execute()) {
                // Send the reset link to the user's email
                $reset_link = "http://localhost:89/gym_management/reset_password.php?token=" . $token;
                $subject = "Password Reset Request";
                $message = "Hi, click on the following link to reset your password: " . $reset_link;
                $headers = "From: no-reply@gym_management.com";
                
                if (mail($email, $subject, $message, $headers)) {
                    $success_message = "A password reset link has been sent to your email.";
                } else {
                    $error_message = "Failed to send email. Please try again.";
                }
            } else {
                $error_message = "Failed to insert token into the database.";
            }
        }
    } else {
        $error_message = "No account found with that email address.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gym Management System - Forgot Password</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .forgot-password-container {
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
    </style>
</head>
<body>
<div class="forgot-password-container">
    <h2 class="text-center">Forgot Password</h2>
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    <form method="POST" action="forgot_password.php">
        <div class="form-group">
            <label for="email">Email:</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                </div>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
    </form>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
