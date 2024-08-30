<?php
include 'config.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=users_list.xls");

echo "Name\tEmail\tPassword\tPhone\n";

$result = $conn->query("SELECT name, email, password, phone FROM users WHERE role = 'user'");
while ($row = $result->fetch_assoc()) {
    echo $row['name'] . "\t" . $row['email'] . "\t" . $row['password'] . "\t" . $row['phone'] . "\n";
}
?>
