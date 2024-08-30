<?php
session_start();
include 'config.php'; // Include your database connection file

// Ensure the user is logged in and is an admin or trainer
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'trainer'])) {
    header('Location: login.php');
    exit();
}

// Initialize variables for report data
$activities = [];
$attendance_records = [];

// Fetch activities data
$activities_stmt = $conn->prepare("SELECT * FROM activities ORDER BY activity_date DESC");
$activities_stmt->execute();
$activities_result = $activities_stmt->get_result();

while ($activity = $activities_result->fetch_assoc()) {
    $activities[] = $activity;
}

// Fetch attendance data
$attendance_stmt = $conn->prepare("SELECT a.attendance_id, u.full_name, c.class_name, a.attendance_date 
                                    FROM attendance a 
                                    JOIN users u ON a.user_id = u.id
                                    JOIN classes c ON a.class_id = c.id
                                    ORDER BY a.attendance_date DESC");
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();

while ($record = $attendance_result->fetch_assoc()) {
    $attendance_records[] = $record;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Report - Gym Management System</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .main-container {
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table th, .table td {
            text-align: center;
        }
    </style>
</head>
<body>

<div class="main-container">
    <h2 class="text-center mb-4">Generate Report</h2>

    <h4>Activities Report</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Description</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($activities as $activity): ?>
                <tr>
                    <td><?php echo $activity['activity_id']; ?></td>
                    <td><?php echo htmlspecialchars($activity['activity_description']); ?></td>
                    <td><?php echo $activity['activity_date']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4 class="mt-5">Attendance Report</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Class Name</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($attendance_records as $record): ?>
                <tr>
                    <td><?php echo $record['attendance_id']; ?></td>
                    <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($record['class_name']); ?></td>
                    <td><?php echo $record['attendance_date']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Bootstrap and jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
