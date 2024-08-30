<?php
session_start();
include 'config.php'; // Include the database connection file

// Enable error reporting for debugging
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/php-error.log'); // Set this to your log file path
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = 'Invalid CSRF token.';
    } else {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password']; // Password is stored as plain text
        $phone = $_POST['phone'];
        $image = $_FILES['image']['name'];
        $role = $_POST['role']; // Get role from form input

        $target_dir = "uploads/";
        $target_file = $target_dir . basename($image);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_file_types = ['jpg', 'png', 'jpeg', 'gif'];

        if (empty($name) || empty($email) || empty($password) || empty($phone) || empty($role)) {
            $error_message = 'All fields are required.';
        } elseif (!in_array($imageFileType, $allowed_file_types)) {
            $error_message = 'Only JPG, JPEG, PNG & GIF files are allowed.';
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error_message = 'Email already exists.';
            } else {
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    // Insert the new user into the database with the image path and current timestamp
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, phone, image, created_at) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
                    $stmt->bind_param("ssssss", $name, $email, $password, $role, $phone, $target_file);

                    if ($stmt->execute()) {
                        $success_message = 'User added successfully.';
                    } else {
                        $error_message = 'Error adding user: ' . $stmt->error;
                    }
                } else {
                    $error_message = 'There was an error uploading the image.';
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
    <title>Add User - Gym Management System</title>
</head>
<body>
    <h2>Add New User</h2>
    
    <?php if (!empty($error_message)): ?>
        <div style="color: red;"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div style="color: green;"><?php echo $success_message; ?></div>
    <?php endif; ?>
</body>
</html>
