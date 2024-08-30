<?php
session_start();
include 'config.php'; // Include the database connection file

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Check if the user ID is provided in the POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];

    // Prepare the SQL statement to delete the user
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        // Redirect to the admin dashboard with a success message
        header('Location: admin_dashboard.php?msg=UserDeleted');
        exit();
    } else {
        // Redirect to the admin dashboard with an error message
        header('Location: admin_dashboard.php?msg=ErrorDeletingUser');
        exit();
    }
} else {
    // If no user ID is provided, redirect to the admin dashboard
    header('Location: admin_dashboard.php');
    exit();
}
?>
