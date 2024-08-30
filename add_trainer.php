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

        // Check if it's a search or add operation
        if (isset($_POST['search_query'])) {
            // Handle search functionality
            $search_query = trim($_POST['search_query']);
            $search_query = "%{$search_query}%";
            $trainers_result = $conn->prepare("SELECT * FROM users WHERE role = 'trainer' AND (name LIKE ? OR email LIKE ?) ORDER BY name");
            $trainers_result->bind_param('ss', $search_query, $search_query);
            $trainers_result->execute();
            $trainers_result = $trainers_result->get_result();
        } else {
            // Handle add trainer functionality
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $password = trim($_POST['password']);
            $phone = trim($_POST['phone']);
            $image = $_FILES['image'];

            if (empty($name) || empty($email) || empty($password) || empty($phone)) {
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
                    $image_path = 'uploads/trainers/default.png'; // Default image if none is uploaded
                }

                if (!$error_message) {
                    // Insert the trainer into the database without hashing the password
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role, image) VALUES (?, ?, ?, ?, 'trainer', ?)");
                    if ($stmt) {
                        $stmt->bind_param('sssss', $name, $email, $password, $phone, $image_path);
                        if ($stmt->execute()) {
                            $success_message = "Trainer added successfully!";
                        } else {
                            $error_message = "Failed to add trainer.";
                        }
                        $stmt->close();
                    } else {
                        $error_message = "Failed to prepare the statement.";
                    }
                }
            }
        }
    }
} else {
    // Fetch all trainers for display if no search or add operation was performed
    $trainers_result = $conn->query("SELECT * FROM users WHERE role = 'trainer' ORDER BY name");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Trainers - Gym Management System</title>
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
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-primary, .btn-success, .btn-warning, .btn-danger {
            border-radius: 20px;
        }
        .table-container {
            margin-top: 30px;
        }
        .table th, .table td {
            vertical-align: middle;
            text-align: center;
        }
        .profile-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
        }
        .modal-header i {
            margin-right: 10px;
        }
    </style>
</head>
<body>

<nav aria-label="breadcrumb" style="text-align: center;">
  <ol class="breadcrumb">
    <li class="breadcrumb-item">
      <a href="admin_dashboard.php">
        <i class="fas fa-home"></i> Dashboard
      </a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">
      <i class="fas fa-user-plus"></i> Manage Trainers
    </li>
  </ol>
</nav>

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-center mb-4"><i class="fas fa-user-plus"></i> Trainer Management</h2>
        <form class="form-inline search-bar" action="add_trainer.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="input-group">
                <input type="text" class="form-control" name="search_query" placeholder="Search Trainers" value="<?php echo htmlspecialchars($search_query ?? ''); ?>">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </div>
        </form>
        <button class="btn btn-info" data-toggle="modal" data-target="#addTrainerModal"><i class="fas fa-user-plus"></i> Add New Trainer</button>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <div class="table-container">
        <table class="table table-hover table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Profile</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Password</th>
                    <th>Date Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($trainers_result && $trainers_result->num_rows > 0): ?>
                    <?php while ($trainer = $trainers_result->fetch_assoc()): ?>
                        <tr>
                            <td><img src="<?php echo htmlspecialchars($trainer['image'] ?? 'uploads/trainers/default.png'); ?>" class="profile-image" alt="Profile Image"></td>
                            <td><?php echo htmlspecialchars($trainer['name']); ?></td>
                            <td><?php echo htmlspecialchars($trainer['email']); ?></td>
                            <td><?php echo htmlspecialchars($trainer['phone']); ?></td>
                            <td><?php echo htmlspecialchars($trainer['password']); ?></td> <!-- Display the password -->
                            <td><?php echo date('d-m-Y H:i:s', strtotime($trainer['created_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#viewTrainerModal<?php echo $trainer['user_id']; ?>"><i class="fas fa-eye"></i> View</button>
                                    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editTrainerModal<?php echo $trainer['user_id']; ?>"><i class="fas fa-edit"></i> Edit</button>
                                    <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteTrainerModal<?php echo $trainer['user_id']; ?>"><i class="fas fa-trash"></i> Delete</button>
                                </div>
                            </td>
                        </tr>

                        <!-- View Trainer Modal -->
                        <div class="modal fade" id="viewTrainerModal<?php echo $trainer['user_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewTrainerLabel<?php echo $trainer['user_id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="viewTrainerLabel<?php echo $trainer['user_id']; ?>"><i class="fas fa-info-circle"></i> Trainer Details</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Name:</strong> <?php echo htmlspecialchars($trainer['name']); ?></p>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($trainer['email']); ?></p>
                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($trainer['phone']); ?></p>
                                        <p><strong>Password:</strong> <?php echo htmlspecialchars($trainer['password']); ?></p> <!-- Display the password -->
                                        <p><strong>Date Joined:</strong> <?php echo date('d-m-Y H:i:s', strtotime($trainer['created_at'])); ?></p>
                                        <img src="<?php echo htmlspecialchars($trainer['image']); ?>" class="img-fluid rounded-circle mt-2" alt="Profile Image">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Trainer Modal -->
                        <div class="modal fade" id="editTrainerModal<?php echo $trainer['user_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editTrainerLabel<?php echo $trainer['user_id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editTrainerLabel<?php echo $trainer['user_id']; ?>"><i class="fas fa-edit"></i> Edit Trainer Details</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <form action="edit_trainer.php" method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($trainer['user_id'] ?? ''); ?>">
                                            <div class="form-group">
                                                <label for="name">Name</label>
                                                <input type="text" class="form-control" name="name" id="name" value="<?php echo htmlspecialchars($trainer['name'] ?? ''); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="email">Email</label>
                                                <input type="email" class="form-control" name="email" id="email" value="<?php echo htmlspecialchars($trainer['email'] ?? ''); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="phone">Phone</label>
                                                <input type="text" class="form-control" name="phone" id="phone" value="<?php echo htmlspecialchars($trainer['phone'] ?? ''); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="password">Password</label>
                                                <input type="text" class="form-control" name="password" id="password" value="<?php echo htmlspecialchars($trainer['password'] ?? ''); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="image">Profile Image</label>
                                                <input type="file" class="form-control" name="image" id="image">
                                                <img src="<?php echo htmlspecialchars($trainer['image']); ?>" class="img-fluid rounded-circle mt-2" alt="Profile Image">
                                            </div>
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Delete Trainer Modal -->
                        <div class="modal fade" id="deleteTrainerModal<?php echo $trainer['user_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteTrainerLabel<?php echo $trainer['user_id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="deleteTrainerLabel<?php echo $trainer['user_id']; ?>"><i class="fas fa-trash"></i> Delete Trainer</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Are you sure you want to delete the trainer <strong><?php echo htmlspecialchars($trainer['name']); ?></strong>?</p>
                                    </div>
                                    <div class="modal-footer">
                                        <form action="delete_trainer.php" method="post">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($trainer['user_id'] ?? ''); ?>">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7">No trainers found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Trainer Modal -->
<div class="modal fade" id="addTrainerModal" tabindex="-1" role="dialog" aria-labelledby="addTrainerLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTrainerLabel"><i class="fas fa-user-plus"></i> Add New Trainer</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="add_trainer.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Enter full name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter email address" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="text" class="form-control" id="password" name="password" placeholder="Enter password" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" placeholder="Enter phone number" required>
                    </div>
                    <div class="form-group">
                        <label for="image">Profile Image</label>
                        <input type="file" class="form-control" id="image" name="image">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-user-plus"></i> Add Trainer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap and jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
