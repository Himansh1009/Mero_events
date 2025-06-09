<?php
// user-dashboard/dashboard.php

// 1. Include session protection to ensure only logged-in users can access this dashboard
require_once '../includes/session-user.php';

// At this point, $_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'],
// and $_SESSION['user_type'] are guaranteed to be set and 'user_type' is 'user'.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 6. Title Tag -->
    <title>User Dashboard - Mero Events</title>
    <!-- 7. Link to external stylesheet -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* 5. Basic styling (clean, centered, dashboard layout) */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh; /* Ensure body takes full viewport height for sticky footer */
        }

        main {
            flex-grow: 1; /* Allows main content to fill available space */
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center; /* Vertically center the dashboard content */
        }

        .dashboard-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px; /* Adjust width as needed */
            text-align: center;
        }

        .dashboard-container h2 {
            color: #333;
            margin-bottom: 25px;
            font-size: 2.2em;
        }

        .user-info p {
            font-size: 1.1em;
            color: #555;
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .user-info strong {
            color: #007bff;
        }
        
        .user-info span.account-type {
            display: inline-block;
            background-color: #e2f0cb; /* Light green background */
            color: #3c763d; /* Dark green text */
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9em;
            margin-top: 15px;
            margin-bottom: 25px;
        }

        .dashboard-actions {
            margin-top: 30px;
            display: flex;
            flex-wrap: wrap; /* Allow buttons to wrap on smaller screens */
            justify-content: center;
            gap: 20px; /* Space between buttons */
        }

        .dashboard-actions .btn {
            min-width: 150px; /* Ensure buttons have a minimum width */
            padding: 12px 20px;
            font-size: 1em;
            text-decoration: none; /* Remove underline from links */
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        /* Re-using .btn-primary and .btn-secondary from style.css */
        .btn-primary {
            background-color: #007bff;
            color: #fff;
            border: 1px solid #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #28a745; /* A pleasant green */
            color: #fff;
            border: 1px solid #28a745;
        }

        .btn-secondary:hover {
            background-color: #218838;
            border-color: #1e7e34;
            transform: translateY(-2px);
        }

        /* Logout button specific style */
        .btn-logout {
            background-color: #dc3545; /* Red for logout */
            border-color: #dc3545;
            color: #fff;
        }
        .btn-logout:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }

        /* Header/Footer (assuming these are included and styled site-wide) */
        .main-header {
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 15px 0;
        }

        .main-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .site-logo {
            font-size: 1.8em;
            font-weight: bold;
            color: #333;
            margin-right: 20px;
            text-decoration: none;
        }

        .site-logo:hover {
            color: #007bff;
        }

        .nav-links {
            list-style: none;
            display: flex;
            align-items: center;
            margin: 0;
            padding: 0;
        }

        .nav-links li {
            margin-left: 25px;
        }

        .nav-links a {
            color: #555;
            font-weight: 500;
            padding: 5px 0;
            transition: color 0.3s ease;
            text-decoration: none;
        }

        .nav-links a:not(.btn):hover {
            color: #007bff;
        }

        .welcome-message {
            color: #555;
            font-weight: 500;
            margin-right: 15px;
            white-space: nowrap;
        }

        .main-footer {
            background-color: #333;
            color: #fff;
            text-align: center;
            padding: 25px 0;
            font-size: 0.9em;
            margin-top: auto; /* Push footer to the bottom */
            width: 100%;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="container">
            <nav class="main-nav">
                <a href="../index.php" class="site-logo">Mero Events</a>
                <ul class="nav-links">
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="../events.php">Events</a></li>
                    <li><a href="../about.php">About</a></li>
                    <li><a href="../contact.php">Contact</a></li>
                    
                    <?php
                    // Dynamic Login/Dashboard/Logout links (reused from index.php logic)
                    // Note: Since this page is protected, we are always logged in here.
                    // The '..' is crucial for paths from this subdirectory.
                    $dashboard_link = ($_SESSION["user_type"] == "organizer") ? 'dashboard.php' : 'dashboard.php'; // Correct path for same-level dashboard
                    // The dashboard link from the header should be specific to the type in this dashboard
                    // For user-dashboard, if they are user, dashboard is current page. If somehow organizer, redirect to their dashboard.
                    if ($_SESSION["user_type"] == "organizer") {
                        $dashboard_link = '../organizer-dashboard/dashboard.php';
                    } elseif ($_SESSION["user_type"] == "user") {
                        $dashboard_link = 'dashboard.php'; // Current page
                    } else {
                        $dashboard_link = '../auth.php?action=login&error=session_error'; // Fallback
                    }

                    echo '<li class="welcome-message">Welcome, ' . htmlspecialchars($_SESSION["user_name"]) . '</li>';
                    echo '<li><a href="' . htmlspecialchars($dashboard_link) . '" class="btn btn-primary">Dashboard</a></li>';
                    echo '<li><a href="../logout.php" class="btn btn-primary" style="background-color: #dc3545;">Logout</a></li>'; 
                    ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="dashboard-container">
            <!-- 2. Display Welcome Message -->
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
            
            <div class="user-info">
                <!-- 2. Display User's full name and email -->
                <p>Full Name: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></p>
                <p>Email: <strong><?php echo htmlspecialchars($_SESSION['user_email']); ?></strong></p>
                <!-- 2. Display Account Type -->
                <p>Account Type: <span class="account-type"><?php echo htmlspecialchars(ucfirst($_SESSION['user_type'])); ?></span></p>
            </div>

            <div class="dashboard-actions">
                <!-- 3. Buttons/links -->
                <a href="../events.php" class="btn btn-secondary">Browse Events</a>
                <a href="../logout.php" class="btn btn-primary btn-logout">Logout</a>
            </div>
        </div>
    </main>

    <footer class="main-footer">
        <div class="container">
            <p>© 2023 Mero Events. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>