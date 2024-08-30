<!DOCTYPE html>
<html>
<head>
    <title>Gym Management System - Home</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #007bff;
        }
        .navbar-brand {
            color: #fff;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .nav-link {
            color: #fff;
            border-radius: 30px;
            margin-right: 10px;
            display: flex;
            align-items: center;
        }
        .nav-link i {
            margin-right: 5px;
        }
        .nav-link:hover {
            background-color: #0056b3;
            color: #fff;
        }
        .hero-section {
            text-align: center;
            padding: 100px 20px;
            background: url('your-background-image.jpg') no-repeat center center;
            background-size: cover;
            color: #fff;
        }
        .hero-section h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        .btn-custom {
            border-radius: 30px;
            padding: 10px 20px;
            font-size: 14px;
        }
        .footer {
            background-color: #007bff;
            color: #fff;
            padding: 10px;
            text-align: center;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light fixed-top">
    <a class="navbar-brand" href="#">Gym Management System</a>
    <div class="collapse navbar-collapse">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link btn btn-primary btn-custom text-white" href="login.php">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            </li>
            <li class="nav-item ml-2">
                <a class="nav-link btn btn-secondary btn-custom text-white" href="register.php">
                    <i class="fas fa-user-plus"></i> Register
                </a>
            </li>
        </ul>
    </div>
</nav>

<section class="hero-section">
    <h1>Welcome to the Gym</h1>
    <p>Get started by logging in or registering to manage your gym operations.</p>
    <a href="register.php" class="btn btn-primary btn-custom">Get Started</a>
</section>

<footer class="footer">
    <p>&copy; 2024 Gym Management System. All rights reserved.</p>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
