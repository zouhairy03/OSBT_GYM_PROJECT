<?php
session_start();
include 'config.php'; // Include the database connection file

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure the user is logged in and is a trainer
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'trainer') {
    header('Location: login.php');
    exit();
}

// Fetch trainer-specific statistics
if (isset($_SESSION['user']['user_id'])) {
    $trainer_id = $_SESSION['user']['user_id']; // Use the correct session variable

    // Query to count the number of clients associated with the trainer
    $total_clients_result = $conn->query("SELECT COUNT(*) as count FROM trainer_clients WHERE trainer_id = $trainer_id");
    if ($total_clients_result) {
        $total_clients = $total_clients_result->fetch_assoc()['count'];
    } else {
        $total_clients = 0; // Handle case where query fails
    }

    // Handle upcoming sessions
    $upcoming_sessions = 0; // Assuming sessions are not tracked

    // Handle average rating
    $average_rating_result = $conn->query("SELECT AVG(rating) as avg FROM feedback WHERE trainer_id = $trainer_id");
    if ($average_rating_result) {
        $average_rating = $average_rating_result->fetch_assoc()['avg'];
        // Ensure the value is numeric before rounding
        $average_rating = is_numeric($average_rating) ? round($average_rating, 2) : 'N/A';
    } else {
        $average_rating = 'N/A'; // Handle case where query fails
    }
} else {
    $error_message = "User ID not found in session.";
    exit($error_message);
}

// Get trainer's name and image URL
$trainer_name = $_SESSION['user']['name'];
$trainer_image = $_SESSION['user']['image'] ?? 'uploads/default_trainer_image.jpg'; // Ensure this path is correct

// Fetch notifications
$new_notifications = $conn->query("SELECT * FROM notifications WHERE user_id = $trainer_id AND status = 'unread' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Dashboard - Gym Management System</title>
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
            background-color: #343a40;
            padding-top: 20px;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        .sidebar a {
            padding: 15px 10px;
            text-decoration: none;
            font-size: 18px;
            color: #ddd;
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
        .navbar .nav-item .nav-link {
            color: #333;
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
        .quick-actions button {
            margin-right: 10px;
            margin-bottom: 10px;
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
    </style>
</head>
<body>

<div class="sidebar" id="mySidebar">
    <a href="javascript:void(0)" class="closebtn" onclick="toggleSidebar()">×</a>
    <a href="trainer_dashboard.php"><i class="fas fa-home"></i> Home</a>
    <a href="trainer_schedule.php"><i class="fas fa-calendar-alt"></i> My Schedule</a>
    <a href="client_progress.php"><i class="fas fa-chart-line"></i> Client Progress</a>
    <a href="trainer_feedback.php"><i class="fas fa-comments"></i> Feedback</a>
    <a href="trainer_reports.php"><i class="fas fa-file-alt"></i> Reports</a>
    <a href="trainer_resources.php"><i class="fas fa-book"></i> Resources</a>
    <a href="trainer_settings.php"><i class="fas fa-cogs"></i> Settings</a>
    <a href="trainer_notifications.php"><i class="fas fa-bell"></i> Notifications</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div id="main">
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Trainer Dashboard</a>
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
        <i class="fas fa-user"></i> Welcome, Trainer <?php echo $trainer_name; ?>
    </div>

    <div class="search-bar">
        <input type="text" placeholder="Search...">
        <button type="button"><i class="fas fa-search"></i> Search</button>
    </div>

    <div class="container-fluid">
        <!-- Quick Actions -->
        <div class="quick-actions">
            <button class="btn btn-primary"><i class="fas fa-calendar-plus"></i> Schedule Session</button>
            <button class="btn btn-success"><i class="fas fa-dumbbell"></i> Add Workout Plan</button>
            <button class="btn btn-info"><i class="fas fa-user-plus"></i> Add Client</button>
            <button class="btn btn-warning"><i class="fas fa-envelope"></i> Send Message</button>
            <button class="btn btn-secondary"><i class="fas fa-file-alt"></i> Generate Report</button>
        </div>

        <!-- Metrics Overview -->
        <div class="row mt-3">
            <div class="col-md-3">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-users"></i> Total Clients</h5>
                        <p class="card-text"><?php echo $total_clients; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-calendar-alt"></i> Upcoming Sessions</h5>
                        <p class="card-text"><?php echo $upcoming_sessions; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-star"></i> Average Rating</h5>
                        <p class="card-text"><?php echo $average_rating; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body chart-container">
                        <h5 class="card-title"><i class="fas fa-chart-line"></i> Client Progress</h5>
                        <canvas id="clientProgressChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body chart-container">
                        <h5 class="card-title"><i class="fas fa-chart-bar"></i> Session Popularity</h5>
                        <canvas id="sessionPopularityChart"></canvas>
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
                            <li class="list-group-item">Update client workout plans <span class="badge badge-warning float-right">Pending</span></li>
                            <li class="list-group-item">Review session feedback <span class="badge badge-success float-right">Completed</span></li>
                            <li class="list-group-item">Send session reminders <span class="badge badge-primary float-right">In Progress</span></li>
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
                        <button class="btn btn-link" onclick="window.location.href='trainer_notifications.php'">View More</button>
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
        <form action="trainer_dashboard.php" method="post" enctype="multipart/form-data">
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
    <strong>Welcome back, <?php echo $trainer_name; ?>!</strong> You have successfully logged in.
    <button type="button" class="btn btn-link">View More</button>
</div>
<?php endif; ?>

<!-- Notification Alert -->
<?php if (!empty($new_notifications)): ?>
<div class="alert alert-info" id="notificationAlert">
    <strong>New Notifications!</strong> You have new notifications.
    <button type="button" class="btn btn-link" onclick="window.location.href='trainer_notifications.php'">View More</button>
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
    var clientProgressCtx = document.getElementById('clientProgressChart').getContext('2d');
    var clientProgressChart = new Chart(clientProgressCtx, {
        type: 'line', // Line chart
        data: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            datasets: [{
                label: 'Client Progress',
                data: [20, 30, 40, 50], // Sample data, replace with actual progress data
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                fill: true
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

    var sessionPopularityCtx = document.getElementById('sessionPopularityChart').getContext('2d');
    var sessionPopularityChart = new Chart(sessionPopularityCtx, {
        type: 'bar', // Bar chart
        data: {
            labels: ['Yoga', 'HIIT', 'Zumba', 'Strength Training'],
            datasets: [{
                label: 'Session Popularity',
                data: [80, 100, 75, 90], // Sample data, replace with actual session data
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
