<?php
session_start();
include 'config.php'; // Include the database connection file

// Ensure the user is logged in and is an admin or trainer
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'trainer'])) {
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = 'Invalid CSRF token.';
    } else {
        // Regenerate the CSRF token after a valid submission to prevent reuse
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Handle form submissions for adding, editing, or deleting classes
        if (isset($_POST['add_class'])) {
            // Add a new class
            $class_name = trim($_POST['class_name']);
            $trainer_id = (int)$_POST['trainer_id'];
            $day = $_POST['day'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            $max_participants = (int)$_POST['max_participants'];

            $stmt = $conn->prepare("INSERT INTO class_schedules (class_name, trainer_id, day, start_time, end_time, max_participants) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('sisssi', $class_name, $trainer_id, $day, $start_time, $end_time, $max_participants);
                if ($stmt->execute()) {
                    $success_message = "Class scheduled successfully!";
                } else {
                    $error_message = "Failed to schedule the class.";
                }
                $stmt->close();
            } else {
                $error_message = "Failed to prepare the statement.";
            }
        } elseif (isset($_POST['edit_class'])) {
            // Edit an existing class
            if (!isset($_POST['class_id'], $_POST['trainer_id'])) {
                $error_message = "Required data missing.";
            } else {
                $class_id = (int)$_POST['class_id'];
                $class_name = trim($_POST['class_name']);
                $trainer_id = (int)$_POST['trainer_id'];
                $day = $_POST['day'];
                $start_time = $_POST['start_time'];
                $end_time = $_POST['end_time'];
                $max_participants = (int)$_POST['max_participants'];

                $stmt = $conn->prepare("UPDATE class_schedules SET class_name = ?, trainer_id = ?, day = ?, start_time = ?, end_time = ?, max_participants = ? WHERE class_id = ?");
                if ($stmt) {
                    $stmt->bind_param('sisssii', $class_name, $trainer_id, $day, $start_time, $end_time, $max_participants, $class_id);
                    if ($stmt->execute()) {
                        $success_message = "Class updated successfully!";
                    } else {
                        $error_message = "Failed to update the class.";
                    }
                    $stmt->close();
                } else {
                    $error_message = "Failed to prepare the statement.";
                }
            }
        } elseif (isset($_POST['delete_class'])) {
            // Delete a class
            if (!isset($_POST['class_id'])) {
                $error_message = "Class ID is missing.";
            } else {
                $class_id = (int)$_POST['class_id'];

                $stmt = $conn->prepare("DELETE FROM class_schedules WHERE class_id = ?");
                if ($stmt) {
                    $stmt->bind_param('i', $class_id);
                    if ($stmt->execute()) {
                        $success_message = "Class deleted successfully!";
                    } else {
                        $error_message = "Failed to delete the class.";
                    }
                    $stmt->close();
                } else {
                    $error_message = "Failed to prepare the statement.";
                }
            }
        }
    }
}

// Handle the search functionality
$search_query = '';
$search_sql = '';
if (isset($_GET['search_query']) && !empty($_GET['search_query'])) {
    $search_query = trim($_GET['search_query']);
    $search_sql = " AND (cs.class_name LIKE '%" . $conn->real_escape_string($search_query) . "%' OR u.name LIKE '%" . $conn->real_escape_string($search_query) . "%')";
}

// Fetch all class schedules for display with optional search
$schedules_result = $conn->query("SELECT cs.class_id, cs.class_name, u.name as trainer_name, cs.day, cs.start_time, cs.end_time, cs.max_participants, cs.trainer_id FROM class_schedules cs JOIN users u ON cs.trainer_id = u.user_id WHERE 1=1 $search_sql ORDER BY cs.day, cs.start_time");

// Fetch all trainers for the dropdown menu
$trainers_result = $conn->query("SELECT user_id, name FROM users WHERE role = 'trainer'");

// Convert the result set to an array to reuse in the edit modal
$trainers = [];
while ($row = $trainers_result->fetch_assoc()) {
    $trainers[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Classes - Gym Management System</title>
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
      <i class="fas fa-calendar-alt"></i> Schedule Classes
    </li>
  </ol>
</nav>

<div class="main-container">
    <h2 class="text-center mb-4">Schedule Classes</h2>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <!-- Search Bar -->
    <form class="form-inline search-bar" action="schedule_class.php" method="get">
        <div class="input-group">
            <input type="text" class="form-control" name="search_query" placeholder="Search Classes" value="<?php echo htmlspecialchars($search_query); ?>">
            <div class="input-group-append">
                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
            </div>
        </div>
    </form>

    <button class="btn btn-info mb-4" data-toggle="modal" data-target="#addClassModal"><i class="fas fa-plus"></i> Add New Class</button>

    <div class="table-container">
        <table class="table table-hover table-bordered">
            <thead class="thead-dark" >
                <tr>
                    <th>Class Name</th>
                    <th>Trainer</th>
                    <th>Day</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Max Participants</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($schedules_result && $schedules_result->num_rows > 0): ?>
                    <?php while ($schedule = $schedules_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($schedule['class_name']); ?></td>
                            <td><?php echo htmlspecialchars($schedule['trainer_name']); ?></td>
                            <td><?php echo htmlspecialchars($schedule['day']); ?></td>
                            <td><?php echo date('h:i A', strtotime($schedule['start_time'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($schedule['end_time'])); ?></td>
                            <td><?php echo htmlspecialchars($schedule['max_participants']); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#viewClassModal<?php echo $schedule['class_id']; ?>"><i class="fas fa-eye"></i> View</button>
                                    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editClassModal<?php echo $schedule['class_id']; ?>"><i class="fas fa-edit"></i> Edit</button>
                                    <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteClassModal<?php echo $schedule['class_id']; ?>"><i class="fas fa-trash"></i> Delete</button>
                                </div>
                            </td>
                        </tr>

                        <!-- View Class Modal -->
                        <div class="modal fade" id="viewClassModal<?php echo $schedule['class_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewClassLabel<?php echo $schedule['class_id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="viewClassLabel<?php echo $schedule['class_id']; ?>">Class Details</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Class Name:</strong> <?php echo htmlspecialchars($schedule['class_name']); ?></p>
                                        <p><strong>Trainer:</strong> <?php echo htmlspecialchars($schedule['trainer_name']); ?></p>
                                        <p><strong>Day:</strong> <?php echo htmlspecialchars($schedule['day']); ?></p>
                                        <p><strong>Start Time:</strong> <?php echo date('h:i A', strtotime($schedule['start_time'])); ?></p>
                                        <p><strong>End Time:</strong> <?php echo date('h:i A', strtotime($schedule['end_time'])); ?></p>
                                        <p><strong>Max Participants:</strong> <?php echo htmlspecialchars($schedule['max_participants']); ?></p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Class Modal -->
                        <div class="modal fade" id="editClassModal<?php echo $schedule['class_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editClassLabel<?php echo $schedule['class_id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editClassLabel<?php echo $schedule['class_id']; ?>">Edit Class Details</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <form action="schedule_class.php" method="post">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="class_id" value="<?php echo $schedule['class_id']; ?>">
                                            <div class="form-group">
                                                <label>Class Name</label>
                                                <input type="text" class="form-control" name="class_name" value="<?php echo htmlspecialchars($schedule['class_name']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Trainer</label>
                                                <select name="trainer_id" class="form-control" required>
                                                    <?php foreach ($trainers as $trainer): ?>
                                                        <option value="<?php echo $trainer['user_id']; ?>" <?php if ($trainer['user_id'] == $schedule['trainer_id']) echo 'selected'; ?>><?php echo htmlspecialchars($trainer['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Day</label>
                                                <select name="day" class="form-control" required>
                                                    <option value="Monday" <?php if ($schedule['day'] == 'Monday') echo 'selected'; ?>>Monday</option>
                                                    <option value="Tuesday" <?php if ($schedule['day'] == 'Tuesday') echo 'selected'; ?>>Tuesday</option>
                                                    <option value="Wednesday" <?php if ($schedule['day'] == 'Wednesday') echo 'selected'; ?>>Wednesday</option>
                                                    <option value="Thursday" <?php if ($schedule['day'] == 'Thursday') echo 'selected'; ?>>Thursday</option>
                                                    <option value="Friday" <?php if ($schedule['day'] == 'Friday') echo 'selected'; ?>>Friday</option>
                                                    <option value="Saturday" <?php if ($schedule['day'] == 'Saturday') echo 'selected'; ?>>Saturday</option>
                                                    <option value="Sunday" <?php if ($schedule['day'] == 'Sunday') echo 'selected'; ?>>Sunday</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Start Time</label>
                                                <input type="time" class="form-control" name="start_time" value="<?php echo htmlspecialchars($schedule['start_time']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>End Time</label>
                                                <input type="time" class="form-control" name="end_time" value="<?php echo htmlspecialchars($schedule['end_time']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Max Participants</label>
                                                <input type="number" class="form-control" name="max_participants" value="<?php echo htmlspecialchars($schedule['max_participants']); ?>" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary" name="edit_class">Save Changes</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Delete Class Modal -->
                        <div class="modal fade" id="deleteClassModal<?php echo $schedule['class_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteClassLabel<?php echo $schedule['class_id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="deleteClassLabel<?php echo $schedule['class_id']; ?>">Delete Class</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Are you sure you want to delete the class <strong><?php echo htmlspecialchars($schedule['class_name']); ?></strong>?</p>
                                    </div>
                                    <div class="modal-footer">
                                        <form action="schedule_class.php" method="post">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="class_id" value="<?php echo $schedule['class_id']; ?>">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger" name="delete_class">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7">No classes scheduled.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Class Modal -->
<div class="modal fade" id="addClassModal" tabindex="-1" role="dialog" aria-labelledby="addClassLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addClassLabel">Add New Class</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="schedule_class.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="form-group">
                        <label>Class Name</label>
                        <input type="text" class="form-control" name="class_name" placeholder="Enter class name" required>
                    </div>
                    <div class="form-group">
                        <label>Trainer</label>
                        <select name="trainer_id" class="form-control" required>
                            <?php foreach ($trainers as $trainer): ?>
                                <option value="<?php echo $trainer['user_id']; ?>"><?php echo htmlspecialchars($trainer['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Day</label>
                        <select name="day" class="form-control" required>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                            <option value="Sunday">Sunday</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" class="form-control" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="time" class="form-control" name="end_time" required>
                    </div>
                    <div class="form-group">
                        <label>Max Participants</label>
                        <input type="number" class="form-control" name="max_participants" placeholder="Enter maximum participants" required>
                    </div>
                    <button type="submit" class="btn btn-primary" name="add_class">Add Class</button>
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
