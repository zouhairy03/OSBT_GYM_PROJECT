<?php
session_start();
include 'config.php'; // Include the database connection file

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Handle mark as read/unread
if (isset($_POST['bulk_action']) && isset($_POST['selected_notifications'])) {
    $selected_ids = implode(",", array_map('intval', $_POST['selected_notifications']));
    if ($_POST['bulk_action'] == 'mark_read') {
        $conn->query("UPDATE notifications SET status = 'read' WHERE id IN ($selected_ids)");
    } elseif ($_POST['bulk_action'] == 'mark_unread') {
        $conn->query("UPDATE notifications SET status = 'unread' WHERE id IN ($selected_ids)");
    } elseif ($_POST['bulk_action'] == 'delete') {
        $conn->query("DELETE FROM notifications WHERE id IN ($selected_ids)");
    }
    header("Location: notifications.php");
    exit();
}

// Fetch all notifications
$notifications = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Notifications - Gym Management System</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 50px;
            max-width: 900px;
        }
        .rounded-card {
            border-radius: 8px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            padding: 15px;
            background-color: #ffffff;
            margin-bottom: 20px;
        }
        .table {
            border-radius: 8px;
            overflow: hidden;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .action-icons .fa {
            margin-right: 10px;
            cursor: pointer;
        }
        .bulk-actions, .search-bar {
            margin-bottom: 20px;
        }
        .btn-primary, .btn-danger, .btn-warning, .btn-success, .btn-info {
            border-radius: 50px;
            padding: 5px 15px;
        }
        .table th, .table td {
            border-color: #ddd;
        }
        .table th {
            background-color: #007bff;
            color: white;
        }
        .table-hover tbody tr:hover {
            background-color: #f1f1f1;
        }
        .pagination {
            justify-content: center;
        }
        .form-control {
            border-radius: 50px;
        }
        .checkbox-round {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="mb-4 text-center">
            <h2><i class="fas fa-bell"></i> Notifications</h2>
        </div>

        <!-- Bulk Actions -->
        <form method="POST" action="">
            <div class="rounded-card bulk-actions d-flex justify-content-between align-items-center">
                <div class="d-flex">
                    <select name="bulk_action" class="form-control d-inline w-auto">
                        <option value="mark_read">Mark as Read</option>
                        <option value="mark_unread">Mark as Unread</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="submit" class="btn btn-primary ml-3"><i class="fas fa-check"></i> Apply</button>
                </div>
                <!-- Search Bar -->
                <div class="search-bar">
                    <input type="text" id="searchBar" class="form-control" placeholder="Search notifications..." onkeyup="filterNotifications()">
                </div>
            </div>

            <!-- Notifications Table -->
            <div class="rounded-card">
                <table class="table table-bordered table-hover" id="notificationTable">
                    <thead>
                        <tr>
                            <th scope="col"><input type="checkbox" id="selectAll" class="checkbox-round"></th>
                            <th scope="col">Message</th>
                            <th scope="col">Date</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notifications as $notification): ?>
                            <tr>
                                <td><input type="checkbox" name="selected_notifications[]" value="<?php echo $notification['id']; ?>" class="checkbox-round"></td>
                                <td><?php echo $notification['message']; ?></td>
                                <td><?php echo $notification['created_at']; ?></td>
                                <td><?php echo ucfirst($notification['status']); ?></td>
                                <td class="text-center">
                                    <div class="action-icons">
                                        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#viewModal-<?php echo $notification['id']; ?>"><i class="fas fa-eye"></i> View</button>
                                        <?php if ($notification['status'] == 'unread'): ?>
                                            <a href="?mark_read=<?php echo $notification['id']; ?>" title="Mark as Read"><i class="fas fa-envelope-open text-success"></i></a>
                                        <?php else: ?>
                                            <a href="?mark_unread=<?php echo $notification['id']; ?>" title="Mark as Unread"><i class="fas fa-envelope text-warning"></i></a>
                                        <?php endif; ?>
                                        <a href="#" onclick="confirmDelete(<?php echo $notification['id']; ?>)" title="Delete"><i class="fas fa-trash text-danger"></i></a>
                                    </div>
                                </td>
                            </tr>

                            <!-- View Modal -->
                            <div class="modal fade" id="viewModal-<?php echo $notification['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel-<?php echo $notification['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="viewModalLabel-<?php echo $notification['id']; ?>"><i class="fas fa-bell"></i> Notification Details</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Message:</strong> <?php echo $notification['message']; ?></p>
                                            <p><strong>Date:</strong> <?php echo $notification['created_at']; ?></p>
                                            <p><strong>Status:</strong> <?php echo ucfirst($notification['status']); ?></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>

        <a href="admin_dashboard.php" class="btn btn-primary mt-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Select/Deselect All Checkboxes
        document.getElementById('selectAll').onclick = function() {
            var checkboxes = document.querySelectorAll('input[type="checkbox"]');
            for (var checkbox of checkboxes) {
                checkbox.checked = this.checked;
            }
        };

        // JavaScript for filtering notifications
        function filterNotifications() {
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById('searchBar');
            filter = input.value.toLowerCase();
            table = document.getElementById("notificationTable");
            tr = table.getElementsByTagName("tr");

            for (i = 1; i < tr.length; i++) {
                tr[i].style.display = "none";
                td = tr[i].getElementsByTagName("td");
                for (var j = 1; j < td.length; j++) { // start loop at 1 to skip checkboxes
                    if (td[j]) {
                        txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toLowerCase().indexOf(filter) > -1) {
                            tr[i].style.display = "";
                            break;
                        }
                    }
                }
            }
        }

        // JavaScript for delete confirmation
        function confirmDelete(notificationId) {
            if (confirm('Are you sure you want to delete this notification?')) {
                window.location.href = 'notifications.php?delete=' + notificationId;
            }
        }
    </script>
</body>
</html>
