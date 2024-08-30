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
            $equipment_result = $conn->prepare("SELECT * FROM equipment WHERE name LIKE ? OR type LIKE ? ORDER BY name");
            $equipment_result->bind_param('ss', $search_query, $search_query);
            $equipment_result->execute();
            $equipment_result = $equipment_result->get_result();
        } else {
            // Handle add equipment functionality
            $name = trim($_POST['name']);
            $type = trim($_POST['type']);
            $quantity = (int)$_POST['quantity'];
            $purchase_date = $_POST['purchase_date'] ?: null;
            $condition = trim($_POST['condition']) ?: 'Good';
            $last_maintenance_date = $_POST['last_maintenance_date'] ?: null;
            $purchase_price = !empty($_POST['purchase_price']) ? (float)$_POST['purchase_price'] : null;
            $warranty_expiration_date = $_POST['warranty_expiration_date'] ?: null;
            $vendor = trim($_POST['vendor']) ?: null;
            $status = $_POST['status'] ?: 'Active';
            $location = trim($_POST['location']) ?: null;
            $depreciation_value = !empty($_POST['depreciation_value']) ? (float)$_POST['depreciation_value'] : null;
            $serial_number = trim($_POST['serial_number']) ?: null;
            $notes = trim($_POST['notes']) ?: null;

            if (empty($name) || empty($type) || $quantity <= 0) {
                $error_message = 'Please fill out all required fields and ensure quantity is a positive number.';
            } else {
                // Insert the equipment into the database
                $stmt = $conn->prepare("INSERT INTO equipment (name, type, quantity, purchase_date, `condition`, last_maintenance_date, purchase_price, warranty_expiration_date, vendor, `status`, location, depreciation_value, serial_number, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param('ssisssdsssdsds', $name, $type, $quantity, $purchase_date, $condition, $last_maintenance_date, $purchase_price, $warranty_expiration_date, $vendor, $status, $location, $depreciation_value, $serial_number, $notes);
                    if ($stmt->execute()) {
                        $success_message = "Equipment added successfully!";
                    } else {
                        $error_message = "Failed to add equipment.";
                    }
                    $stmt->close();
                } else {
                    $error_message = "Failed to prepare the statement.";
                }
            }
        }
    }
} else {
    // Fetch all equipment for display if no search or add operation was performed
    $equipment_result = $conn->query("SELECT * FROM equipment ORDER BY name");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Equipment - Gym Management System</title>
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
      <i class="fas fa-plus-circle"></i> Add Equipment
    </li>
  </ol>
</nav>

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-center mb-4"><i class="fas fa-dumbbell"></i> Equipment Management</h2>
        <form class="form-inline search-bar" action="add_equipment.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="input-group">
                <input type="text" class="form-control" name="search_query" placeholder="Search Equipment" value="<?php echo htmlspecialchars($search_query ?? ''); ?>">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </div>
        </form>
        <button class="btn btn-info" data-toggle="modal" data-target="#addEquipmentModal"><i class="fas fa-plus-circle"></i> Add New Equipment</button>
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
                    <th>Name</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Condition</th>
                    <th>Price (USD)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($equipment_result && $equipment_result->num_rows > 0): ?>
                    <?php while ($equipment = $equipment_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($equipment['name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($equipment['type'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($equipment['quantity'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($equipment['condition'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($equipment['purchase_price'] !== null && $equipment['purchase_price'] !== '' ? number_format($equipment['purchase_price'], 2) . ' USD' : 'Not Specified'); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#viewEquipmentModal<?php echo $equipment['id']; ?>"><i class="fas fa-eye"></i> View</button>
                                    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editEquipmentModal<?php echo $equipment['id']; ?>"><i class="fas fa-edit"></i> Edit</button>
                                    <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteEquipmentModal<?php echo $equipment['id']; ?>"><i class="fas fa-trash"></i> Delete</button>
                                </div>
                            </td>
                        </tr>

                        <!-- View Equipment Modal -->
                        <div class="modal fade" id="viewEquipmentModal<?php echo $equipment['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewEquipmentLabel<?php echo $equipment['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="viewEquipmentLabel<?php echo $equipment['id']; ?>"><i class="fas fa-info-circle"></i> Equipment Details</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Name:</strong> <?php echo htmlspecialchars($equipment['name'] ?? ''); ?></p>
                                        <p><strong>Type:</strong> <?php echo htmlspecialchars($equipment['type'] ?? ''); ?></p>
                                        <p><strong>Quantity:</strong> <?php echo htmlspecialchars($equipment['quantity'] ?? ''); ?></p>
                                        <p><strong>Condition:</strong> <?php echo htmlspecialchars($equipment['condition'] ?? ''); ?></p>
                                        <p><strong>Price:</strong> <?php echo htmlspecialchars($equipment['purchase_price'] !== null && $equipment['purchase_price'] !== '' ? number_format($equipment['purchase_price'], 2) . ' USD' : 'Not Specified'); ?></p>
                                        <p><strong>Purchase Date:</strong> <?php echo htmlspecialchars($equipment['purchase_date'] ?? ''); ?></p>
                                        <p><strong>Last Maintenance Date:</strong> <?php echo htmlspecialchars($equipment['last_maintenance_date'] ?? ''); ?></p>
                                        <p><strong>Warranty Expiration:</strong> <?php echo htmlspecialchars($equipment['warranty_expiration_date'] ?? ''); ?></p>
                                        <p><strong>Vendor:</strong> <?php echo htmlspecialchars($equipment['vendor'] ?? ''); ?></p>
                                        <p><strong>Depreciation Value:</strong> <?php echo htmlspecialchars($equipment['depreciation_value'] ?? ''); ?></p>
                                        <p><strong>Serial Number:</strong> <?php echo htmlspecialchars($equipment['serial_number'] ?? ''); ?></p>
                                        <p><strong>Notes:</strong> <?php echo htmlspecialchars($equipment['notes'] ?? ''); ?></p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Equipment Modal -->
                        <div class="modal fade" id="editEquipmentModal<?php echo $equipment['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editEquipmentLabel<?php echo $equipment['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editEquipmentLabel<?php echo $equipment['id']; ?>"><i class="fas fa-edit"></i> Edit Equipment Details</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <form action="edit_equipment.php" method="post">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($equipment['id'] ?? ''); ?>">
                                            <div class="form-group">
                                                <label for="name">Name</label>
                                                <input type="text" class="form-control" name="name" id="name" value="<?php echo htmlspecialchars($equipment['name'] ?? ''); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="type">Type</label>
                                                <input type="text" class="form-control" name="type" id="type" value="<?php echo htmlspecialchars($equipment['type'] ?? ''); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="quantity">Quantity</label>
                                                <input type="number" class="form-control" name="quantity" id="quantity" value="<?php echo htmlspecialchars($equipment['quantity'] ?? ''); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="condition">Condition</label>
                                                <input type="text" class="form-control" name="condition" id="condition" value="<?php echo htmlspecialchars($equipment['condition'] ?? ''); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="purchase_price">Price</label>
                                                <input type="number" step="0.01" class="form-control" name="purchase_price" id="purchase_price" value="<?php echo htmlspecialchars($equipment['purchase_price'] ?? ''); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="purchase_date">Purchase Date</label>
                                                <input type="date" class="form-control" name="purchase_date" id="purchase_date" value="<?php echo htmlspecialchars($equipment['purchase_date'] ?? ''); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="last_maintenance_date">Last Maintenance Date</label>
                                                <input type="date" class="form-control" name="last_maintenance_date" id="last_maintenance_date" value="<?php echo htmlspecialchars($equipment['last_maintenance_date'] ?? ''); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="warranty_expiration_date">Warranty Expiration Date</label>
                                                <input type="date" class="form-control" name="warranty_expiration_date" id="warranty_expiration_date" value="<?php echo htmlspecialchars($equipment['warranty_expiration_date'] ?? ''); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="vendor">Vendor</label>
                                                <input type="text" class="form-control" name="vendor" id="vendor" value="<?php echo htmlspecialchars($equipment['vendor'] ?? ''); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="depreciation_value">Depreciation Value</label>
                                                <input type="number" step="0.01" class="form-control" name="depreciation_value" id="depreciation_value" value="<?php echo htmlspecialchars($equipment['depreciation_value'] ?? ''); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="serial_number">Serial Number</label>
                                                <input type="text" class="form-control" name="serial_number" id="serial_number" value="<?php echo htmlspecialchars($equipment['serial_number'] ?? ''); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="notes">Notes</label>
                                                <textarea class="form-control" name="notes" id="notes"><?php echo htmlspecialchars($equipment['notes'] ?? ''); ?></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Delete Equipment Modal -->
                        <div class="modal fade" id="deleteEquipmentModal<?php echo $equipment['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteEquipmentLabel<?php echo $equipment['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="deleteEquipmentLabel<?php echo $equipment['id']; ?>"><i class="fas fa-trash"></i> Delete Equipment</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Are you sure you want to delete the equipment <strong><?php echo htmlspecialchars($equipment['name'] ?? ''); ?></strong>?</p>
                                    </div>
                                    <div class="modal-footer">
                                        <form action="delete_equipment.php" method="post">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($equipment['id'] ?? ''); ?>">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6">No equipment found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Equipment Modal -->
<div class="modal fade" id="addEquipmentModal" tabindex="-1" role="dialog" aria-labelledby="addEquipmentLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEquipmentLabel"><i class="fas fa-plus-circle"></i> Add New Equipment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="add_equipment.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="form-group">
                        <label for="name">Equipment Name</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Enter equipment name" required>
                    </div>
                    <div class="form-group">
                        <label for="type">Type</label>
                        <input type="text" class="form-control" id="type" name="type" placeholder="Enter equipment type" required>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" placeholder="Enter quantity" required>
                    </div>
                    <div class="form-group">
                        <label for="purchase_date">Purchase Date</label>
                        <input type="date" class="form-control" id="purchase_date" name="purchase_date">
                    </div>
                    <div class="form-group">
                        <label for="condition">Condition</label>
                        <input type="text" class="form-control" id="condition" name="condition" placeholder="Enter equipment condition">
                    </div>
                    <div class="form-group">
                        <label for="last_maintenance_date">Last Maintenance Date</label>
                        <input type="date" class="form-control" id="last_maintenance_date" name="last_maintenance_date">
                    </div>
                    <div class="form-group">
                        <label for="purchase_price">Price</label>
                        <input type="number" step="0.01" class="form-control" id="purchase_price" name="purchase_price" placeholder="Enter purchase price">
                    </div>
                    <div class="form-group">
                        <label for="warranty_expiration_date">Warranty Expiration Date</label>
                        <input type="date" class="form-control" id="warranty_expiration_date" name="warranty_expiration_date">
                    </div>
                    <div class="form-group">
                        <label for="vendor">Vendor</label>
                        <input type="text" class="form-control" id="vendor" name="vendor" placeholder="Enter vendor name">
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Under Maintenance">Under Maintenance</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" class="form-control" id="location" name="location" placeholder="Enter location within the gym">
                    </div>
                    <div class="form-group">
                        <label for="depreciation_value">Depreciation Value</label>
                        <input type="number" step="0.01" class="form-control" id="depreciation_value" name="depreciation_value" placeholder="Enter depreciation value">
                    </div>
                    <div class="form-group">
                        <label for="serial_number">Serial Number</label>
                        <input type="text" class="form-control" id="serial_number" name="serial_number" placeholder="Enter serial number">
                    </div>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" placeholder="Enter any additional notes"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-plus-circle"></i> Add Equipment</button>
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
