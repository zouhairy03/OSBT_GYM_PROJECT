<?php
session_start();
include 'config.php';

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Initialize messages
$error_message = '';
$success_message = '';

// Generate a new CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = 'Invalid CSRF token.';
    } else {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $title = trim($_POST['title']);
        $message = trim($_POST['message']);
        $recipient_role = $_POST['recipient_role'];

        if (empty($title) || empty($message) || empty($recipient_role)) {
            $error_message = 'Please fill out all fields.';
        } else {
            $stmt = $conn->prepare("INSERT INTO notifications (title, message, recipient_role, created_at) VALUES (?, ?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param('sss', $title, $message, $recipient_role);
                if ($stmt->execute()) {
                    $success_message = "Notification sent successfully!";
                } else {
                    $error_message = "Failed to send notification.";
                }
                $stmt->close();
            } else {
                $error_message = "Failed to prepare the statement.";
            }
        }
    }
}

// Fetch existing notifications
$notifications = [];
$result = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC");
if ($result && $result->num_rows > 0) {
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Notification - Gym Management System</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f2f5;
        }
        .main-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .btn-custom {
            background-color: #007bff;
            color: #fff;
            border-radius: 25px;
        }
        .btn-custom:hover {
            background-color: #0056b3;
        }
        .table-container {
            margin-top: 30px;
        }
        .modal-header {
            background-color: #007bff;
            color: #fff;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .modal-footer {
            border-top: none;
        }
        .breadcrumb {
            background-color: transparent;
            text-align: center;
        }
        .breadcrumb-item a {
            color: #007bff;
            text-decoration: none;
        }
        .breadcrumb-item a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb justify-content-center">
    <li class="breadcrumb-item">
      <a href="admin_dashboard.php">
        <i class="fas fa-home"></i> Dashboard
      </a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">
      <i class="fas fa-bell"></i> Send Notification
    </li>
  </ol>
</nav>

<div class="main-container">
    <h2 class="text-center mb-4"><i class="fas fa-bell"></i> Send Notification</h2>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <!-- Send Notification Button -->
    <div class="text-center">
        <button type="button" class="btn btn-custom" data-toggle="modal" data-target="#sendNotificationModal">
            <i class="fas fa-paper-plane"></i> Send Notification
        </button>
    </div>

    <!-- Notifications Table -->
    <div class="table-container mt-5">
        <h4 class="mb-3"><i class="fas fa-list"></i> Sent Notifications</h4>
        <div class="input-group mb-3">
            <input type="text" class="form-control" id="search" placeholder="Search notifications">
            <div class="input-group-append">
                <button class="btn btn-secondary" type="button"><i class="fas fa-search"></i></button>
            </div>
        </div>
        <table class="table table-hover table-bordered rounded">
            <thead class="thead-dark">
                <tr>
                    <th>Title</th>
                    <th>Message</th>
                    <th>Recipient</th>
                    <th>Sent At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notifications as $notification): ?>
                <tr>
                    <td><?php echo htmlspecialchars($notification['title']); ?></td>
                    <td><?php echo htmlspecialchars($notification['message']); ?></td>
                    <td><?php echo htmlspecialchars($notification['recipient_role']); ?></td>
                    <td><?php echo htmlspecialchars($notification['created_at']); ?></td>
                    <td>
                        <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#editModal<?php echo $notification['id']; ?>"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-danger btn-sm" onclick="deleteNotification(<?php echo $notification['id']; ?>)"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>

                <!-- Edit Modal -->
                <div class="modal fade" id="editModal<?php echo $notification['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel<?php echo $notification['id']; ?>" aria-hidden="true">
                  <div class="modal-dialog" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel<?php echo $notification['id']; ?>">Edit Notification</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                      <div class="modal-body">
                        <form action="edit_notification.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="id" value="<?php echo $notification['id']; ?>">

                            <div class="form-group">
                                <label for="edit_title">Title</label>
                                <input type="text" class="form-control" id="edit_title" name="title" value="<?php echo htmlspecialchars($notification['title']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_message">Message</label>
                                <textarea class="form-control" id="edit_message" name="message" rows="4" required><?php echo htmlspecialchars($notification['message']); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="edit_recipient_role">Send To</label>
                                <select class="form-control" id="edit_recipient_role" name="recipient_role" required>
                                    <option value="user" <?php if ($notification['recipient_role'] == 'user') echo 'selected'; ?>>Users</option>
                                    <option value="trainer" <?php if ($notification['recipient_role'] == 'trainer') echo 'selected'; ?>>Trainers</option>
                                    <option value="all" <?php if ($notification['recipient_role'] == 'all') echo 'selected'; ?>>All</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Send Notification Modal -->
<div class="modal fade" id="sendNotificationModal" tabindex="-1" role="dialog" aria-labelledby="sendNotificationModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="sendNotificationModalLabel">Send New Notification</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form action="send_notification.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" class="form-control" id="title" name="title" placeholder="Enter notification title" required>
            </div>
            <div class="form-group">
                <label for="message">Message</label>
                <textarea class="form-control" id="message" name="message" rows="4" placeholder="Enter notification message" required></textarea>
            </div>
            <div class="form-group">
                <label for="recipient_role">Send To</label>
                <select class="form-control" id="recipient_role" name="recipient_role" required>
                    <option value="user">Users</option>
                    <option value="trainer">Trainers</option>
                    <option value="all">All</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-paper-plane"></i> Send Notification</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<!-- Custom JS for search -->
<script>
function deleteNotification(id) {
    if (confirm('Are you sure you want to delete this notification?')) {
        window.location.href = 'delete_notification.php?id=' + id;
    }
}

document.getElementById('search').addEventListener('input', function () {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('tbody tr');

    rows.forEach(row => {
        let title = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
        let message = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
        let recipient = row.querySelector('td:nth-child(3)').textContent.toLowerCase();

        if (title.includes(filter) || message.includes(filter) || recipient.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>
</body>
</html>
