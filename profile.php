<?php
// -------------------------------------------
// Session and Configuration
// -------------------------------------------
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

// if (!$user_id) {
//     echo "User ID not found in session.";
//     exit();
// }

// -------------------------------------------
// Fetch User Data
// -------------------------------------------
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Calculate profile completion percentage
$profile_completion = 0;
$fields_total = 4; // Number of fields considered for profile completion
$fields_filled = 0;

if (!empty($user['name'])) $fields_filled++;
if (!empty($user['email'])) $fields_filled++;
if (!empty($user['image'])) $fields_filled++;
if (!empty($user['phone'])) $fields_filled++; 

$profile_completion = ($fields_filled / $fields_total) * 100;

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $password = trim($_POST['password']);
    $update_password = !empty($password);

    // Handle image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["profile_image"]["name"]);

        // Check if the file is an image
        $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                $user['image'] = $target_file;
            } else {
                $error_message = "Sorry, there was an error uploading your file.";
            }
        } else {
            $error_message = "File is not an image.";
        }
    }

    // Update user information in the database
    if (!isset($error_message)) {
        if ($update_password) {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, password = ?, image = ? WHERE user_id = ?");
            $stmt->bind_param("sssssi", $name, $email, $phone, $password, $user['image'], $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, image = ? WHERE user_id = ?");
            $stmt->bind_param("ssssi", $name, $email, $phone, $user['image'], $user_id);
        }

        if ($stmt->execute()) {
            // Update session data
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['phone'] = $phone;
            $_SESSION['user']['image'] = $user['image'];

            $success_message = "Profile updated successfully!";
        } else {
            $error_message = "SQL Error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Gym Management System</title>

    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS for Light and Rounded Design -->
    <style>
        body {
            background-color: #f2f4f7;
            font-family: 'Arial', sans-serif;
        }
        .profile-container {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-top: 30px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .profile-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }
        .profile-header img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 5px solid #007bff;
            margin-bottom: 15px;
        }
        .progress-bar {
            background-color: #007bff;
            border-radius: 10px;
        }
        .form-control {
            border-radius: 30px;
            background-color: #f2f4f7;
            border: 1px solid #ddd;
            padding-left: 20px;
        }
        .form-control:focus {
            background-color: #e9ecef;
            border-color: #007bff;
            box-shadow: none;
        }
        .btn-primary {
            background-color: #007bff;
            border-radius: 30px;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: 700;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .card {
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .card-header {
            border-radius: 15px 15px 0 0;
            background-color: #007bff;
            color: white;
            font-size: 18px;
            padding: 15px;
            text-align: center;
        }
        .card-body {
            padding: 20px;
        }
        .btn-danger {
            background-color: #dc3545;
            border-radius: 30px;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: 700;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .greeting {
            font-size: 22px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 30px;
        }
        .quick-actions {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
        }
        .quick-actions button {
            border-radius: 30px;
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            transition: all 0.3s;
        }
        .quick-actions button:hover {
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Profile Section -->
        <div class="profile-container">
            <!-- Personalized Greeting -->
            <div class="greeting">
                <?php
                $hour = date('H');
                if ($hour < 12) {
                    echo "Good Morning, " . htmlspecialchars($user['name']) . "!";
                } elseif ($hour < 18) {
                    echo "Good Afternoon, " . htmlspecialchars($user['name']) . "!";
                } else {
                    echo "Good Evening, " . htmlspecialchars($user['name']) . "!";
                }
                ?>
            </div>

            <div class="profile-header">
                <img src="<?php echo htmlspecialchars($user['image']); ?>" alt="Profile Image">
                <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>

            <!-- Profile Completion Progress Bar -->
            <div class="progress mb-4" style="height: 25px;">
                <div class="progress-bar" role="progressbar" style="width: <?php echo $profile_completion; ?>%;" aria-valuenow="<?php echo $profile_completion; ?>" aria-valuemin="0" aria-valuemax="100">
                    <?php echo round($profile_completion); ?>%
                </div>
            </div>

            <!-- Display Success or Error Messages -->
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <!-- Profile Update Form -->
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name"><i class="fas fa-user"></i> Name:</label>
                    <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email:</label>
                    <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone"><i class="fas fa-phone"></i> Phone:</label>
                    <input type="tel" name="phone" id="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Enter your phone number">
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> New Password:</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Leave blank to keep current password">
                </div>
                <div class="form-group">
                    <label for="profile_image"><i class="fas fa-camera"></i> Profile Image:</label>
                    <input type="file" name="profile_image" id="profile_image" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save"></i> Update Profile</button>
            </form>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <button class="btn btn-info"><i class="fas fa-calendar-check"></i> Book a Class</button>
                <button class="btn btn-success"><i class="fas fa-dumbbell"></i> View Progress</button>
                <button class="btn btn-warning"><i class="fas fa-user-plus"></i> Invite a Friend</button>
            </div>
        </div>

        <!-- Membership Status Section -->
        <div class="card mt-4">
            <div class="card-header">
                Membership Status
            </div>
            <div class="card-body">
                <?php
                $stmt = $conn->prepare("SELECT * FROM memberships WHERE user_id = ? AND end_date > NOW()");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $membership = $stmt->get_result()->fetch_assoc();

                if ($membership):
                ?>
                    <p>Your membership is active until: <strong><?php echo date('F j, Y', strtotime($membership['end_date'])); ?></strong></p>
                <?php else: ?>
                    <p>You do not have an active membership.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Account Deletion Section -->
        <div class="card mt-4 mb-5">
            <div class="card-header bg-danger text-white">
                Delete Account
            </div>
            <div class="card-body">
                <form method="POST" action="delete_account.php" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                    <button type="submit" class="btn btn-danger btn-block"><i class="fas fa-trash-alt"></i> Delete My Account</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS, Popper.js, and jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
