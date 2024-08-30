<?php
session_start();
include 'config.php'; // Include the database connection file

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Initialize messages
$error_message = '';
$success_message = '';

// Generate or regenerate a new CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = 'Invalid CSRF token.';
    } else {
        // Regenerate the CSRF token after a valid submission to prevent reuse
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Collect and validate form data
        $trainer_id = (int)$_POST['user_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = trim($_POST['password']);
        $image = $_FILES['image'];

        if (empty($name) || empty($email) || empty($phone) || empty($password)) {
            $error_message = 'Please fill out all fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Invalid email format.';
        } else {
            // Handle image upload
            if ($image['error'] === UPLOAD_ERR_OK) {
                $image_name = uniqid() . '-' . basename($image['name']);
                $image_path = 'uploads/trainers/' . $image_name;

                if (!is_dir('uploads/trainers')) {
                    mkdir('uploads/trainers', 0777, true);
                }

                if (move_uploaded_file($image['tmp_name'], $image_path)) {
                    // Image uploaded successfully
                } else {
                    $error_message = 'Failed to upload image.';
                }
            } else {
                // If no new image is uploaded, keep the existing one
                $image_path = trim($_POST['existing_image']);
            }

            if (!$error_message) {
                // Update the trainer's information in the database, including the password without hashing it
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, password = ?, image = ? WHERE user_id = ?");
                $stmt->bind_param('sssssi', $name, $email, $phone, $password, $image_path, $trainer_id);

                if ($stmt->execute()) {
                    $success_message = "Trainer updated successfully!";
                } else {
                    $error_message = "Failed to update trainer.";
                }
                $stmt->close();
            }
        }
    }
    // Redirect back to the manage trainers page
    header("Location: add_trainer.php");
    exit();
} else {
    // If the form is not submitted correctly, redirect back
    header("Location: add_trainer.php");
    exit();
}
?>
