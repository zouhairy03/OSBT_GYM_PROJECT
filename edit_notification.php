<?php
session_start();
include 'config.php'; // Include your database connection file

// Ensure the user is logged in and is an admin or trainer
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'trainer'])) {
    header('Location: login.php');
    exit();
}

$error_message = '';
$success_message = '';

// Check if the ID is provided in the GET request
if (!isset($_GET['id'])) {
    header('Location: send_notification.php');
    exit();
}

$notification_id = (int)$_GET['id'];

// Fetch the notification details from the database
$stmt = $conn->prepare("SELECT * FROM notifications WHERE id = ?");
$stmt->bind_param("i", $notification_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: send_notification.php');
    exit();
}

$notification = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = 'Invalid CSRF token.';
    } else {
        // Collect and validate form data
        $title = trim($_POST['title']);
        $message = trim($_POST['message']);
        $recipient_role = trim($_POST['recipient_role']);

        if (empty($title) || empty($message)) {
            $error_message = 'Please fill out all required fields.';
        } else {
            // Update the notification in the database
            $stmt = $conn->prepare("UPDATE notifications SET title = ?, message = ?, recipient_role = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('sssi', $title, $message, $recipient_role, $notification_id);
                if ($stmt->execute()) {
                    $success_message = "Notification updated successfully!";
                    header('Location: send_notification.php');
                    exit();
                } else {
                    $error_message = "Failed to update notification.";
                }
                $stmt->close();
            } else {
                $error_message = "Failed to prepare the statement.";
            }
        }
    }
}

// Generate a new CSRF token if it's not already set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Notification - Gym Management System</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .main-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-primary {
            border-radius: 20px;
        }
    </style>
</head>
<body>

<div class="main-container">
    <h2 class="text-center mb-4"><i class="fas fa-edit"></i> Edit Notification</h2>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <form action="edit_notification.php?id=<?php echo $notification_id; ?>" method="post">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($notification['title']); ?>" required>
        </div>
        <div class="form-group">
            <label for="message">Message</label>
            <textarea class="form-control" id="message" name="message" rows="4" required><?php echo htmlspecialchars($notification['message']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="recipient_role">Send To</label>
            <select class="form-control" id="recipient_role" name="recipient_role" required>
                <option value="user" <?php echo ($notification['recipient_role'] === 'user') ? 'selected' : ''; ?>>Users</option>
                <option value="trainer" <?php echo ($notification['recipient_role'] === 'trainer') ? 'selected' : ''; ?>>Trainers</option>
                <option value="all" <?php echo ($notification['recipient_role'] === 'all') ? 'selected' : ''; ?>>All</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
    </form>
</div>

<!-- Bootstrap and jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
