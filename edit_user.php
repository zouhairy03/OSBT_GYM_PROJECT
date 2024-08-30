<?php
session_start();
include 'config.php'; // Include the database connection file

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $phone = $_POST['phone'];
    $image = $_FILES['image']['name'];

    // Sanitize inputs
    $name = htmlspecialchars($name);
    $email = htmlspecialchars($email);
    $phone = htmlspecialchars($phone);
    $role = htmlspecialchars($role);

    // Check if the email is already in use by another user
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND user_id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $error_message = 'Email already exists.';
    } else {
        // Handle image upload if a new image is provided
        if (!empty($image)) {
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($image);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowed_file_types = ['jpg', 'png', 'jpeg', 'gif'];

            if (!in_array($imageFileType, $allowed_file_types)) {
                $error_message = 'Only JPG, JPEG, PNG & GIF files are allowed.';
            } else {
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, phone = ?, image = ? WHERE user_id = ?");
                    $stmt->bind_param("sssssi", $name, $email, $role, $phone, $target_file, $user_id);
                } else {
                    $error_message = 'There was an error uploading the image.';
                }
            }
        } else {
            // Update without changing the image
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, phone = ? WHERE user_id = ?");
            $stmt->bind_param("ssssi", $name, $email, $role, $phone, $user_id);
        }

        if (empty($error_message) && $stmt->execute()) {
            $success_message = 'User updated successfully.';
        } else {
            $error_message = 'Error updating user: ' . $stmt->error;
        }
    }
}

// Fetch the user details to be edited
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
} else {
    header('Location: manage_users.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Gym Management System</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .form-group .fas {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
        }
        .form-control {
            padding-left: 45px;
            border-radius: 50px;
        }
        .btn-primary {
            border-radius: 50px;
            padding: 10px 30px;
        }
        .alert {
            border-radius: 15px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-center">Edit User</h2>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <form action="edit_user.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id']); ?>">
        <div class="form-group">
            <i class="fas fa-user"></i>
            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" placeholder="Name" required>
        </div>
        <div class="form-group">
            <i class="fas fa-envelope"></i>
            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" placeholder="Email" required>
        </div>
        <div class="form-group">
            <i class="fas fa-user-tag"></i>
            <select name="role" class="form-control" required>
                <option value="admin" <?php if($user['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                <option value="trainer" <?php if($user['role'] == 'trainer') echo 'selected'; ?>>Trainer</option>
                <option value="user" <?php if($user['role'] == 'user') echo 'selected'; ?>>User</option>
            </select>
        </div>
        <div class="form-group">
            <i class="fas fa-phone"></i>
            <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" placeholder="Phone" required>
        </div>
        <div class="form-group">
            <i class="fas fa-image"></i>
            <input type="file" class="form-control" name="image">
            <img src="<?php echo htmlspecialchars($user['image']); ?>" class="img-fluid mt-2" alt="Profile Image">
        </div>
        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save"></i> Save Changes</button>
    </form>
</div>

<!-- Bootstrap and jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
