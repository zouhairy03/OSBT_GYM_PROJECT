<?php
session_start();
include 'config.php'; // Include the database connection file

// Ensure the user is logged in and is an admin or trainer
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'trainer'])) {
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
        // Collect and validate form data
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $type = trim($_POST['type']);
        $quantity = (int)$_POST['quantity'];
        $purchase_date = $_POST['purchase_date'] ?: null;
        $condition = trim($_POST['condition']) ?: 'Good';
        $last_maintenance_date = $_POST['last_maintenance_date'] ?: null;
        $purchase_price = !empty($_POST['purchase_price']) ? (float)$_POST['purchase_price'] : null;
        $warranty_expiration_date = $_POST['warranty_expiration_date'] ?: null;
        $vendor = trim($_POST['vendor']) ?: null;
        $status = $_POST['status'] ?: 'Active';
        $location = trim($_POST['location']) ?: null;
        $depreciation_value = !empty($_POST['depreciation_value']) ? (float)$_POST['depreciation_value'] : null;
        $serial_number = trim($_POST['serial_number']) ?: null;
        $notes = trim($_POST['notes']) ?: null;

        if (empty($name) || empty($type) || $quantity <= 0) {
            $error_message = 'Please fill out all required fields and ensure quantity is a positive number.';
        } else {
            // Update the equipment in the database
            $stmt = $conn->prepare("UPDATE equipment SET name = ?, type = ?, quantity = ?, purchase_date = ?, `condition` = ?, last_maintenance_date = ?, purchase_price = ?, warranty_expiration_date = ?, vendor = ?, `status` = ?, location = ?, depreciation_value = ?, serial_number = ?, notes = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('ssisssdsssdsdsi', $name, $type, $quantity, $purchase_date, $condition, $last_maintenance_date, $purchase_price, $warranty_expiration_date, $vendor, $status, $location, $depreciation_value, $serial_number, $notes, $id);
                if ($stmt->execute()) {
                    $success_message = "Equipment updated successfully!";
                    header('Location: add_equipment.php');
                    exit();
                } else {
                    $error_message = "Failed to update equipment.";
                }
                $stmt->close();
            } else {
                $error_message = "Failed to prepare the statement.";
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
    <title>Edit Equipment - Gym Management System</title>
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
    <h2 class="text-center mb-4"><i class="fas fa-edit"></i> Edit Equipment</h2>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <!-- Form for editing equipment is managed via modals on the add_equipment.php page -->
</div>

<!-- Bootstrap and jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
