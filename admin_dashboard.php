<?php
session_start();
include 'config.php'; // Include the database connection file

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Fetch statistics from the database
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$active_memberships = $conn->query("SELECT COUNT(*) as count FROM memberships WHERE end_date > CURDATE()")->fetch_assoc()['count'];
$pending_payments_result = $conn->query("SELECT SUM(amount) as total FROM payments WHERE payment_date IS NULL");
$pending_payments = $pending_payments_result->fetch_assoc()['total'] ?? 0.00;
$total_revenue_result = $conn->query("SELECT SUM(amount) as total FROM payments WHERE payment_date IS NOT NULL");
$total_revenue = $total_revenue_result->fetch_assoc()['total'] ?? 0.00;

// Fetch data for additional charts
$total_classes = $conn->query("SELECT COUNT(*) as count FROM classes")->fetch_assoc()['count'];
$total_trainers = $conn->query("SELECT COUNT(*) as count FROM trainers")->fetch_assoc()['count'];
$equipment_count = $conn->query("SELECT COUNT(*) as count FROM equipment")->fetch_assoc()['count'];

// Get admin's name and image URL
$admin_name = $_SESSION['user']['name'];
$admin_image = $_SESSION['user']['image'] ?? 'uploads/default_admin_image.jpg'; // Ensure this path is correct

// Fetch new notifications
$new_notifications = $conn->query("SELECT * FROM notifications WHERE status = 'unread' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_image'])) {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["profile_image"]["name"]);

    // Check if the file is an image
    $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
    if ($check === false) {
        $error_message = "File is not an image.";
    } else {
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            if (isset($_SESSION['user']['id'])) {
                $user_id = $_SESSION['user']['id']; // Ensure user_id is correctly retrieved from the session
                $stmt = $conn->prepare("UPDATE users SET image = ? WHERE user_id = ?");
                $stmt->bind_param("si", $target_file, $user_id);
                if ($stmt->execute()) {
                    $_SESSION['user']['image'] = $target_file;
                    header('Location: admin_dashboard.php');
                    exit();
                } else {
                    $error_message = "SQL Error: " . $stmt->error;
                }
            } else {
                $error_message = "User ID not found in session.";
            }
        } else {
            $error_message = "Sorry, there was an error uploading your file.";
        }
    }
}

// Set login alert
$login_alert = true;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Gym Management System</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background: white;
            color: black;
            padding-top: 20px;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        .sidebar a {
            padding: 15px 10px;
            text-decoration: none;
            font-size: 18px;
            color: black;
            display: block;
            transition: 0.3s;
        }
        .sidebar a:hover {
            background-color: #495057;
            color: #fff;
        }
        .sidebar .closebtn {
            position: absolute;
            top: 0;
            right: 25px;
            font-size: 36px;
            margin-left: 50px;
        }
        .openbtn {
            font-size: 20px;
            cursor: pointer;
            color: white;
            padding: 10px 15px;
            border: none;
        }
        .openbtn:hover {
            background-color: #0056b3;
        }
        #main {
            transition: margin-left 0.3s ease;
            padding: 16px;
            margin-left: 250px;
        }
        .card {
            border-radius: 15px;
        }
        .navbar {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
        }
        .navbar-brand {
            padding-left: 20px;
        }
        .sidebar-hidden {
            width: 0;
            overflow: hidden;
        }
        .main-expanded {
            margin-left: 0 !important;
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
        .welcome-message {
            padding: 15px;
            color: black;
            font-size: 27px;
            text-align: center;
            margin-bottom: 20px;
        }
        .welcome-message i {
            margin-right: 5px;
        }
        .profile-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .profile-image:hover {
            opacity: 0.7;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            padding-top: 100px;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        .quick-actions {
            margin-bottom: 20px;
            text-align: center;
        }
        .quick-actions a {
            margin-right: 10px;
            margin-bottom: 10px;
            border-radius: 50px;
            padding: 10px 25px;
            text-decoration: none;
            color: white;
            display: inline-block;
        }
        .btn-rounded {
            border-radius: 50px;
        }
        .search-bar {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .search-bar input {
            width: 50%;
            border-radius: 50px;
            padding: 10px;
            border: 1px solid #ccc;
        }
        .search-bar button {
            margin-left: 10px;
            border-radius: 50px;
            padding: 10px 20px;
            border: none;
            background-color: #007bff;
            color: white;
            cursor: pointer;
        }
        .search-bar button:hover {
            background-color: #0056b3;
        }
        .activity-feed {
            margin-top: 20px;
        }
        .activity-feed h5 {
            font-size: 18px;
            margin-bottom: 15px;
        }
        .activity-feed .activity {
            border-bottom: 1px solid #ddd;
            padding: 10px 0;
        }
        .activity-feed .activity:last-child {
            border-bottom: none;
        }
        .metrics {
            margin-top: 20px;
        }
        .metrics .metric {
            padding: 15px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1001;
            display: none;
        }
        .notifications-list {
            list-style: none;
            padding: 0;
        }
        .notifications-list li {
            background-color: #f8f9fa;
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .notification-alert {
            position: relative;
            display: block;
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
        .notification-alert .btn-link {
            position: absolute;
            right: 10px;
            top: 10px;
        }
        .centered-profile {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="sidebar" id="mySidebar">
<a href="admin_dashboard.php"><img src="rmvbg.png" alt="Logo" style="width: 100%;margin-left:0px; height: 150px;"></a>

    <a href="javascript:void(0)" class="closebtn" onclick="toggleSidebar()">×</a>
    <a href="#analyticsSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-chart-line"></i> Analytics</a>
    <ul class="collapse list-unstyled" id="analyticsSubmenu">
        <li>
            <a href="user_growth.php"><i class="fas fa-user-plus"></i> User Growth</a>
        </li>
        <li>
            <a href="revenue_analysis.php"><i class="fas fa-dollar-sign"></i> Revenue Analysis</a>
        </li>
        <li>
            <a href="membership_trends.php"><i class="fas fa-chart-pie"></i> Membership Trends</a>
        </li>
        <li>
            <a href="payment_history.php"><i class="fas fa-credit-card"></i> Payment History</a>
        </li>
    </ul>
    <a href="#managementSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-user-cog"></i> Management</a>
    <ul class="collapse list-unstyled" id="managementSubmenu">
        <li>
            <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
        </li>
        <li>
            <a href="manage_classes.php"><i class="fas fa-calendar-alt"></i> Manage Classes</a>
        </li>
        <li>
            <a href="manage_memberships.php"><i class="fas fa-id-card"></i> Manage Memberships</a>
        </li>
        <li>
            <a href="manage_payments.php"><i class="fas fa-wallet"></i> Manage Payments</a>
        </li>
        <li>
            <a href="manage_equipment.php"><i class="fas fa-dumbbell"></i> Manage Equipment</a>
        </li>
        <li>
            <a href="manage_trainers.php"><i class="fas fa-chalkboard-teacher"></i> Manage Trainers</a>
        </li>
    </ul>
    <a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a>
    <a href="settings.php"><i class="fas fa-cogs"></i> Settings</a>
    <a href="feedback.php"><i class="fas fa-comments"></i> Feedback</a>
    <a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a>
    <a href="support.php"><i class="fas fa-headset"></i> Support</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div id="main">
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
    <!-- <a href="admin_dashboard.php"><img src="rmvbg.png" alt="Logo" style="width: 60%;margin-left:0px; height: 300px;"></a> -->

        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="profile.php"><i class="fas fa-user"></i> Profile</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </nav>

    <button class="openbtn" onclick="toggleSidebar()">☰ </button>

    <div class="welcome-message">
        <i class="fas fa-user"></i> Welcome,To The Ultrim Admin <?php echo $admin_name; ?>!
    </div>

    <!-- Centered Profile Image -->
    <div class="centered-profile">
        <img src="<?php echo htmlspecialchars($admin_image); ?>" class="rounded-circle profile-image" alt="Admin Picture" id="profileImage">
        <h2><?php echo htmlspecialchars($admin_name); ?></h2>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
    </div>

    <div class="search-bar">
        <input type="text" placeholder="Search...">
        <button type="button"><i class="fas fa-search"></i> Search</button>
    </div>

    <div class="container-fluid">
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="manage_users.php" class="btn btn-primary btn-rounded"><i class="fas fa-user-plus"></i> Add User</a>
            <a href="schedule_class.php" class="btn btn-success btn-rounded"><i class="fas fa-calendar-plus"></i> Schedule Class</a>
            <a href="add_equipment.php" class="btn btn-info btn-rounded"><i class="fas fa-dumbbell"></i> Add Equipment</a>
            <a href="add_trainer.php" class="btn btn-warning btn-rounded"><i class="fas fa-chalkboard-teacher"></i> Add Trainer</a>
            <a href="send_notification.php" class="btn btn-danger btn-rounded"><i class="fas fa-bell"></i> Send Notification</a>
            <a href="generate_report.php" class="btn btn-secondary btn-rounded"><i class="fas fa-file-alt"></i> Generate Report</a>
            <a href="export_report.php" style="background: orange;" class="btn btn-outline-orange btn-rounded"><i class="fas fa-file-export"></i> Export Report</a>
        </div>

        <!-- Metrics Overview -->
        <div class="row mt-3">
            <div class="col-md-3">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-users"></i> Total Users</h5>
                        <p class="card-text"><?php echo $total_users; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-id-card"></i> Active Memberships</h5>
                        <p class="card-text"><?php echo $active_memberships; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-wallet"></i> Pending Payments</h5>
                        <p class="card-text">$<?php echo number_format($pending_payments, 2); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-danger mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-dollar-sign"></i> Total Revenue</h5>
                        <p class="card-text">$<?php echo number_format($total_revenue, 2); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body chart-container">
                        <h5 class="card-title"><i class="fas fa-chart-line"></i> User Growth</h5>
                        <canvas id="userGrowthChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body chart-container">
                        <h5 class="card-title"><i class="fas fa-dollar-sign"></i> Revenue</h5>
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body chart-container">
                        <h5 class="card-title"><i class="fas fa-chart-pie"></i> Membership Trends</h5>
                        <canvas id="membershipTrendsChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body chart-container">
                        <h5 class="card-title"><i class="fas fa-credit-card"></i> Payment History</h5>
                        <canvas id="paymentHistoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- New Charts -->
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body chart-container">
                        <h5 class="card-title"><i class="fas fa-chart-line"></i> Membership Retention Rate</h5>
                        <canvas id="retentionRateChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body chart-container">
                        <h5 class="card-title"><i class="fas fa-chart-bar"></i> New vs Returning Users</h5>
                        <canvas id="newReturningUsersChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Charts Based on Database -->
        <div class="row mt-3">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body chart-container">
                        <h5 class="card-title"><i class="fas fa-calendar-alt"></i> Total Classes</h5>
                        <canvas id="classesChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body chart-container">
                        <h5 class="card-title"><i class="fas fa-chalkboard-teacher"></i> Total Trainers</h5>
                        <canvas id="trainersChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body chart-container">
                        <h5 class="card-title"><i class="fas fa-dumbbell"></i> Equipment Count</h5>
                        <canvas id="equipmentChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Management -->
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title"><i class="fas fa-tasks"></i> Task Management</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item">Review new user registrations <span class="badge badge-warning float-right">Pending</span></li>
                            <li class="list-group-item">Update membership plans <span class="badge badge-success float-right">Completed</span></li>
                            <li class="list-group-item">Send newsletter to users <span class="badge badge-primary float-right">In Progress</span></li>
                        </ul>
                        <button class="btn btn-outline-success mt-3"><i class="fas fa-plus"></i> Add Task</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Display Each Notification as a Separate Alert -->
        <div class="row mt-3">
            <div class="col-md-12">
                <h5><i class="fas fa-bell"></i> New Notifications</h5>
                <?php foreach ($new_notifications as $notification): ?>
                    <div class="alert alert-info notification-alert">
                        <p><?php echo $notification['message']; ?></p>
                        <small><i><?php echo $notification['created_at']; ?></i></small>
                        <button class="btn btn-link" onclick="window.location.href='notifications.php'">View More</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- The Modal -->
<div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <form action="admin_dashboard.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="profile_image">Select image to upload:</label>
                <input type="file" name="profile_image" id="profile_image" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Upload Image</button>
        </form>
    </div>
</div>

<!-- Login Alert -->
<?php if (isset($login_alert)): ?>
<div class="alert alert-success" id="loginAlert">
    <strong>Welcome back, <?php echo $admin_name; ?>!</strong> You have successfully logged in.
    <button type="button" class="btn btn-link">View More</button>
</div>
<?php endif; ?>

<!-- Notification Alert -->
<?php if (!empty($new_notifications)): ?>
<div class="alert alert-info" id="notificationAlert">
    <strong>New Notifications!</strong> You have new notifications.
    <button type="button" class="btn btn-link" onclick="window.location.href='notifications.php'">View More</button>
</div>
<?php endif; ?>

<!-- JavaScript and Chart.js Integration -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function toggleSidebar() {
        var sidebar = document.getElementById("mySidebar");
        var main = document.getElementById("main");

        if (sidebar.classList.contains('sidebar-hidden')) {
            sidebar.classList.remove('sidebar-hidden');
            main.classList.remove('main-expanded');
        } else {
            sidebar.classList.add('sidebar-hidden');
            main.classList.add('main-expanded');
        }
    }

    // Data for charts
    var userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
    var userGrowthChart = new Chart(userGrowthCtx, {
        type: 'line', // Line chart
        data: {
            labels: ['January', 'February', 'March', 'April', 'May', 'June'],
            datasets: [{
                label: 'User Growth',
                data: [10, 20, 30, 40, 50, 60],
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    var revenueCtx = document.getElementById('revenueChart').getContext('2d');
    var revenueChart = new Chart(revenueCtx, {
        type: 'bar', // Bar chart
        data: {
            labels: ['January', 'February', 'March', 'April', 'May', 'June'],
            datasets: [{
                label: 'Revenue',
                data: [500, 1000, 1500, 2000, 2500, 3000],
                backgroundColor: 'rgba(153, 102, 255, 0.2)',
                borderColor: 'rgba(153, 102, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    var membershipTrendsCtx = document.getElementById('membershipTrendsChart').getContext('2d');
    var membershipTrendsChart = new Chart(membershipTrendsCtx, {
        type: 'doughnut', // Doughnut chart
        data: {
            labels: ['January', 'February', 'March', 'April', 'May', 'June'],
            datasets: [{
                label: 'Membership Trends',
                data: [5, 15, 25, 35, 45, 55],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                    'rgba(255, 159, 64, 0.2)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    var paymentHistoryCtx = document.getElementById('paymentHistoryChart').getContext('2d');
    var paymentHistoryChart = new Chart(paymentHistoryCtx, {
        type: 'polarArea', // Polar area chart
        data: {
            labels: ['January', 'February', 'March', 'April', 'May', 'June'],
            datasets: [{
                label: 'Payment History',
                data: [300, 600, 900, 1200, 1500, 1800],
                backgroundColor: [
                    'rgba(173, 216, 230, 0.8)',
                    'rgba(152, 251, 152, 0.8)',
                    'rgba(240, 128, 128, 0.8)',
                    'rgba(250, 250, 210, 0.8)',
                    'rgba(255, 182, 193, 0.8)',
                    'rgba(119, 136, 153, 0.8)'
                ],
                borderColor: [
                    'rgba(173, 216, 230, 0.8)',
                    'rgba(152, 251, 152, 0.8)',
                    'rgba(240, 128, 128, 0.8)',
                    'rgba(250, 250, 210, 0.8)',
                    'rgba(255, 182, 193, 0.8)',
                    'rgba(119, 136, 153, 0.8)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    beginAtZero: true
                }
            }
        }
    });

    // New Charts Data

    // Membership Retention Rate Data
    var retentionRateCtx = document.getElementById('retentionRateChart').getContext('2d');
    var retentionRateChart = new Chart(retentionRateCtx, {
        type: 'line', // Line chart
        data: {
            labels: ['January', 'February', 'March', 'April', 'May', 'June'],
            datasets: [{
                label: 'Retention Rate',
                data: [85, 88, 90, 87, 85, 89], // Sample data, replace with actual retention rates
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                fill: true
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            return tooltipItem.raw + '%';
                        }
                    }
                }
            }
        }
    });

    // New vs Returning Users Data
    var newReturningUsersCtx = document.getElementById('newReturningUsersChart').getContext('2d');
    var newReturningUsersChart = new Chart(newReturningUsersCtx, {
        type: 'bar', // Bar chart
        data: {
            labels: ['January', 'February', 'March', 'April', 'May', 'June'],
            datasets: [{
                label: 'New Users',
                data: [120, 150, 130, 170, 180, 190], // Sample data, replace with actual data
                backgroundColor: 'rgba(75, 192, 192, 0.8)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }, {
                label: 'Returning Users',
                data: [80, 90, 100, 110, 120, 130], // Sample data, replace with actual data
                backgroundColor: 'rgba(153, 102, 255, 0.8)',
                borderColor: 'rgba(153, 102, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Additional Charts

    // Total Classes Data
    var classesCtx = document.getElementById('classesChart').getContext('2d');
    var classesChart = new Chart(classesCtx, {
        type: 'bar', // Bar chart
        data: {
            labels: ['January', 'February', 'March', 'April', 'May', 'June'],
            datasets: [{
                label: 'Total Classes',
                data: [10, 20, 30, 40, 50, 60], // Replace with actual data
                backgroundColor: 'rgba(255, 206, 86, 0.2)',
                borderColor: 'rgba(255, 206, 86, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Total Trainers Data
    var trainersCtx = document.getElementById('trainersChart').getContext('2d');
    var trainersChart = new Chart(trainersCtx, {
        type: 'line', // Line chart
        data: {
            labels: ['January', 'February', 'March', 'April', 'May', 'June'],
            datasets: [{
                label: 'Total Trainers',
                data: [5, 10, 15, 20, 25, 30], // Replace with actual data
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Equipment Count Data
    var equipmentCtx = document.getElementById('equipmentChart').getContext('2d');
    var equipmentChart = new Chart(equipmentCtx, {
        type: 'pie', // Pie chart
        data: {
            labels: ['Cardio Machines', 'Weight Machines', 'Free Weights', 'Mats'],
            datasets: [{
                label: 'Equipment Count',
                data: [50, 30, 20, 15], // Replace with actual data
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Modal script
    var modal = document.getElementById("myModal");
    var img = document.getElementById("profileImage");
    var span = document.getElementsByClassName("close")[0];

    img.onclick = function() {
        modal.style.display = "block";
    }

    span.onclick = function() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Display login alert
    $(document).ready(function() {
        $("#loginAlert").fadeIn();
        setTimeout(function() {
            $("#loginAlert").fadeOut();
        }, 5000); // Display alert for 5 seconds

        // Display notification alert if there are new notifications
        <?php if (!empty($new_notifications)): ?>
        $("#notificationAlert").fadeIn();
        setTimeout(function() {
            $("#notificationAlert").fadeOut();
        }, 10000); // Display alert for 10 seconds
        <?php endif; ?>
    });
</script>
</body>
</html>
