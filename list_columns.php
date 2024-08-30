<?php
include 'config.php'; // Include the database connection file

$query = "SHOW COLUMNS FROM users";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo $row['Field'] . "<br>";
    }
    
} else {
    echo "Error: " . mysqli_error($conn);
}


mysqli_close($conn);

?>
