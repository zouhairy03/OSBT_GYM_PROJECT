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
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF token validation
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = 'Invalid CSRF token.';
    } else {
        // Handle the search functionality
        $search_query = trim($_POST['search_query']); // Trim any extra spaces
        $search_query = "%" . $search_query . "%"; // Add wildcards for partial matching

        // Prepare the SQL query with case-insensitivity and partial matching
        $users = $conn->prepare("SELECT user_id, name, email, password, role, phone, image, created_at FROM users WHERE role = 'user' AND (LOWER(name) LIKE LOWER(?) OR LOWER(email) LIKE LOWER(?))");
        $users->bind_param("ss", $search_query, $search_query);
        $users->execute();
        $users_result = $users->get_result();
    }
} else {
    // Fetch only users with the role 'user' to display in the table
    $users_result = $conn->query("SELECT user_id, name, email, password, role, phone, image, created_at FROM users WHERE role = 'user'");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Gym Management System</title>
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
        .search-bar {
            margin-bottom: 20px;
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
      <i class="fas fa-user-plus"></i> Manage Users
    </li>
  </ol>
</nav>

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-center mb-4">Manage Users</h2>
        <form class="form-inline search-bar" action="manage_users.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="input-group">
                <input type="text" class="form-control" name="search_query" placeholder="Search Users" value="<?php echo isset($search_query) ? htmlspecialchars($search_query) : ''; ?>">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </div>
        </form>
        <a href="export_users.php" class="btn btn-success"><i class="fas fa-file-excel"></i> Download Excel</a>
        <button class="btn btn-info" data-toggle="modal" data-target="#addUserModal"><i class="fas fa-user-plus"></i> Add New User</button>
    </div>

    <div class="table-container">
        <table class="table table-hover table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Profile</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Password</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Date Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($users_result) && $users_result->num_rows > 0): ?>
                    <?php while ($user = $users_result->fetch_assoc()): ?>
                        <tr>
                            <td><img src="<?php echo htmlspecialchars($user['image'] ?? 'uploads/default.png'); ?>" class="profile-image" alt="Profile Image"></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['password']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td><?php echo date('d-m-Y H:i:s', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#viewUserModal<?php echo $user['user_id']; ?>"><i class="fas fa-eye"></i> View</button>
                                    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editUserModal<?php echo $user['user_id']; ?>"><i class="fas fa-edit"></i> Edit</button>
                                    <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteUserModal<?php echo $user['user_id']; ?>"><i class="fas fa-trash"></i> Delete</button>
                                </div>
                            </td>
                        </tr>

                        <!-- Modals for View, Edit, and Delete Actions -->
                        <!-- View User Modal -->
                        <div class="modal fade" id="viewUserModal<?php echo $user['user_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewUserLabel<?php echo $user['user_id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="viewUserLabel<?php echo $user['user_id']; ?>">View User Details</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                        <p><strong>Password:</strong> <?php echo htmlspecialchars($user['password']); ?></p>
                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                                        <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
                                        <p><strong>Date Joined:</strong> <?php echo date('d-m-Y H:i:s', strtotime($user['created_at'])); ?></p>
                                        <img src="<?php echo htmlspecialchars($user['image']); ?>" class="img-fluid" alt="Profile Image">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit User Modal -->
                        <div class="modal fade" id="editUserModal<?php echo $user['user_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editUserLabel<?php echo $user['user_id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editUserLabel<?php echo $user['user_id']; ?>">Edit User Details</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <form action="edit_user.php" method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id']); ?>">
                                            <div class="form-group">
                                                <label>Name</label>
                                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Email</label>
                                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Password</label>
                                                <input type="text" class="form-control" name="password" value="<?php echo htmlspecialchars($user['password']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Role</label>
                                                <select name="role" class="form-control" required>
                                                    <option value="user" <?php if($user['role'] == 'user') echo 'selected'; ?>>User</option>
                                                    <option value="admin" <?php if($user['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                                                    <option value="trainer" <?php if($user['role'] == 'trainer') echo 'selected'; ?>>Trainer</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Phone</label>
                                                <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Profile Image</label>
                                                <input type="file" class="form-control" name="image">
                                                <img src="<?php echo htmlspecialchars($user['image']); ?>" class="img-fluid mt-2" alt="Profile Image">
                                            </div>
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Delete User Modal -->
                        <div class="modal fade" id="deleteUserModal<?php echo $user['user_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteUserLabel<?php echo $user['user_id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="deleteUserLabel<?php echo $user['user_id']; ?>">Delete User</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Are you sure you want to delete the user <strong><?php echo htmlspecialchars($user['name']); ?></strong>?</p>
                                    </div>
                                    <div class="modal-footer">
                                        <form action="delete_user.php" method="post">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id']); ?>">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserLabel">Add New User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="add_user.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="form-group">
                        <label for="name">Full Name:</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Enter full name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter email address" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number:</label>
                        <input type="text" class="form-control" id="phone" name="phone" placeholder="Enter phone number" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role:</label>
                        <select name="role" id="role" class="form-control" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                            <option value="trainer">Trainer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="image">Profile Image:</label>
                        <input type="file" class="form-control" id="image" name="image" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap and jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    document.getElementById('imageUpload').onchange = function (e) {
        let reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('imagePreview').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        };
        reader.readAsDataURL(this.files[0]);
    };
</script>
</body>
</html>
