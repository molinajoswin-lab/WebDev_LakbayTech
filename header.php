<?php require_once 'auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LakBay Tech - Job Finder</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
<div class="obj-width">
    <nav id="navBar">
        <div class="logo">
            <a href="index.php" style="text-decoration: none;"><h1>LakBay Tech</h1></a>
        </div>
        <div class="nav-right">
            <ul id="menu">
                <li><a href="index.php">Home</a></li>
                <?php if (isLoggedIn()): ?>
                    <?php if ($_SESSION['role'] == 'user'): ?>
                        <li><a href="jobs.php">Find Jobs</a></li>
                        <li><a href="applications.php">Applications</a></li>
                        <li><a href="profile.php">Profile</a></li>
                        <li><a href="notifications.php">Notifications</a></li>
                    <?php elseif ($_SESSION['role'] == 'employer'): ?>
                        <li><a href="post_job.php">Post Job</a></li>
                        <li><a href="manage_jobs.php">Manage Jobs</a></li>
                        <li><a href="employer_profile.php">Company</a></li>
                        <li><a href="notifications.php">Notifications</a></li>
                    <?php elseif ($_SESSION['role'] == 'admin'): ?>
                        <li><a href="admin_dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php" class="nav-pill-btn logout-link js-logout-link">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php" id="w-btn" class="nav-pill-btn create-account-link">Create Account</a></li>
                <?php endif; ?>
            </ul>
            <i class="fa-solid fa-bars" id="bar"></i>
        </div>
    </nav>
</div>

<main class="main-content">
