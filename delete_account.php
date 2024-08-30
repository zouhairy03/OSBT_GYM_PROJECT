<?php
session_start();
include 'config.php'; // Include the database connection file

// Enable error reporting for debugging during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure the user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// Fetch the user's ID from the session
$user_id = $_SESSION['user']['id'] ?? null;

if (!$user_id) {
    echo "User ID not found in session.";
    exit();
}

// Handle account deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Start a transaction to ensure all deletions happen atomically
    $conn->begin_transaction();

    try {
        // Delete related records (e.g., memberships, payments, etc.)
        $stmt = $conn->prepare("DELETE FROM memberships WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Add more deletion queries here if your application has other related tables
        // Example: $conn->prepare("DELETE FROM payments WHERE user_id = ?");

        // Finally, delete the user record
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Commit the transaction
        $conn->commit();

        // Destroy the session and redirect to a goodbye page or home page
        session_destroy();
        header('Location: goodbye.php'); // Redirect to a goodbye page
        exit();

    } catch (Exception $e) {
        // Roll back the transaction in case of error
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account - Gym Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h3><i class="fas fa-exclamation-triangle"></i> Delete Account</h3>
                    </div>
                    <div class="card-body">
                        <p>Are you sure you want to delete your account? This action cannot be undone.</p>
                        <form method="POST" action="">
                            <button type="submit" class="btn btn-danger btn-block"><i class="fas fa-trash-alt"></i> Yes, Delete My Account</button>
                            <a href="profile.php" class="btn btn-secondary btn-block"><i class="fas fa-times"></i> No, Keep My Account</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS, Popper.js, and jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
