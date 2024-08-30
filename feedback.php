<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $feedback = $_POST['feedback'];
    // Save the feedback to the database or send it via email
    // Example: $stmt = $conn->prepare("INSERT INTO feedback (feedback) VALUES (?)");
    // $stmt->bind_param("s", $feedback);
    // $stmt->execute();
    
    // Redirect to a thank you page or back to the logout page
    header('Location: thank_you.php');
    exit();
}
?>
