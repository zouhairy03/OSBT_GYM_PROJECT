<?php
session_start();
if (isset($_POST['confirm'])) {
    // Destroy session and redirect to login page
    session_destroy();
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logout - Gym Management System</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .logout-container {
            max-width: 400px;
            margin: 0 auto;
            margin-top: 100px;
            text-align: center;
        }
        .btn {
            border-radius: 30px;
        }
        .logout-icon {
            font-size: 100px;
            color: #ff0000;
            margin-bottom: 20px;
        }
        .timer {
            font-size: 20px;
            margin-top: 20px;
        }
        .progress-bar {
            height: 20px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="logout-container">
    <i class="fas fa-sign-out-alt logout-icon"></i>
    <h2 id="logout-message">Are you sure you want to logout?</h2>
    <p id="custom-message"></p>
    <form method="POST" action="logout.php" id="logoutForm">
        <input type="hidden" name="confirm" value="1">
        <button type="submit" class="btn btn-danger btn-block">Yes, Logout</button>
        <a href="javascript:void(0);" onclick="cancelLogout()" class="btn btn-secondary btn-block">Cancel</a>
    </form>
    <div class="timer" id="logoutTimer">Auto logout in <span id="countdown">30</span> seconds...</div>
    <div class="progress">
        <div class="progress-bar" role="progressbar" style="width: 100%;" id="progressBar"></div>
    </div>
</div>
<script>
    function cancelLogout() {
        window.history.back();
    }

    function displayCustomMessage() {
        var now = new Date();
        var hours = now.getHours();
        var message;
        if (hours < 12) {
            message = "Have a great day ahead!";
        } else if (hours < 18) {
            message = "Enjoy your afternoon!";
        } else {
            message = "Good evening! See you again soon!";
        }
        document.getElementById('custom-message').textContent = message;
    }

    displayCustomMessage();

    var countdownElement = document.getElementById('countdown');
    var progressBarElement = document.getElementById('progressBar');
    var countdown = 30;
    var countdownInterval = setInterval(function() {
        countdown--;
        countdownElement.textContent = countdown;
        progressBarElement.style.width = (countdown / 30) * 100 + '%';
        if (countdown <= 0) {
            clearInterval(countdownInterval);
            console.log('Submitting form');  // Debugging message
            document.getElementById('logoutForm').submit();
        }
    }, 1000);
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
