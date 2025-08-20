<?php
// user-dashboard/dashboard.php

// 1. Ensure user is logged in as a "user" and that includes/config.php is loaded once
// The includes/session-user.php script should handle session_start() and require_once '../includes/config.php';
require_once '../includes/session-user.php'; 

// REMOVED THE REDUNDANT require_once '../includes/config.php';
// REMOVED THE $conn->close(); LINE.
// The database connection ($conn) is managed by session-user.php and will be
// automatically closed by PHP at the end of this script's execution.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Mero Events</title>
    <!-- Include Font Awesome for icons if needed -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Link to your main CSS file -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Define Color Palette for consistency with project theme */
        :root {
            /* General project colors */
            --primary-color: #ff6b6b;   /* Reddish-orange */
            --secondary-color: #1dd1a1; /* Teal green */
            --accent-color: #feca57;    /* Yellow-orange */
            --background-color: #f1f2f6;
            --text-color: #2f3542;      /* Dark text */
            --light-text-color: #666666; /* Lighter gray for secondary text */
            --white: #ffffff;
            --border-color: #ddd;
            --shadow-color: rgba(0,0,0,0.05);
            --hover-shadow-color: rgba(0,0,0,0.1);

            /* Navbar specific colors (from recent prompts) */
            --navbar-bg: #ffffff;
            --navbar-border: #f0f0f0; 
            --navbar-logo-color: #6A5ACD; /* Blue-purple from index.php's navbar image */
            --navbar-link-color: #666666; 
            --navbar-dashboard-btn-bg: #4a90e2; 
            --navbar-logout-btn-bg: #e04444; 
            --navbar-btn-text-color: #ffffff; 
        }

        body {
            font-family: Arial, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* --- Page Container --- */
        main {
            flex-grow: 1;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center; /* Center content vertically */
        }

        .user-dashboard-container { /* Renamed from .dashboard-container for clarity */
            background-color: var(--white);
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-color);
            width: 100%;
            max-width: 600px; /* Adjust width as needed */
            text-align: center;
        }

        .user-dashboard-container h2 {
            color: var(--text-color);
            margin-bottom: 25px;
            font-size: 2.5em; /* Larger heading */
            font-weight: bold;
        }

        .user-info p {
            font-size: 1.1em;
            color: var(--light-text-color);
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .user-info strong {
            color: var(--text-color);
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

        /* Dashboard Action Buttons Container */
        .dashboard-actions {
            margin-top: 30px;
            display: flex;
            flex-wrap: wrap; /* Allow buttons to wrap on smaller screens */
            justify-content: center;
            gap: 20px; /* Space between buttons */
        }

        /* Styles for .btn-common.secondary, .btn-common.primary, .btn-common.logout */
        .btn-common { /* General button style, not specific to navbar */
            display: inline-block;
            padding: 12px 25px; /* More padding for larger buttons */
            border-radius: 8px; /* Slightly more rounded */
            font-weight: bold;
            font-size: 1.1em; /* Larger font size */
            text-align: center;
            text-decoration: none;
            transition: background-color 0.2s ease, transform 0.2s ease;
            cursor: pointer;
            border: none;
        }

        .btn-common.secondary { /* For "Browse Events" */
            background-color: var(--secondary-color);
            color: var(--white);
        }
        .btn-common.secondary:hover {
            background-color: #17b38c; /* Darker secondary */
            transform: translateY(-2px);
        }

        .btn-common.primary { /* For "My Bookings" */
            background-color: var(--primary-color);
            color: var(--white);
        }
        .btn-common.primary:hover {
            background-color: #e65a5a; /* Darker primary */
            transform: translateY(-2px);
        }

        .btn-common.logout { /* For "Logout" */
            background-color: var(--navbar-logout-btn-bg); 
            color: var(--white);
        }
        .btn-common.logout:hover {
            background-color: #cc3939; /* Darker red */
            transform: translateY(-2px);
        }


        /* --- Header/Footer (Consistent with site-wide theme) --- */
        .main-header {
            background-color: var(--navbar-bg);
            box-shadow: 0 2px 5px var(--shadow-color);
            border-bottom: 1px solid var(--navbar-border);
            padding: 15px 0;
        }
        .main-nav { 
            display: flex; justify-content: space-between; align-items: center; 
            max-width: 1200px; margin: 0 auto; padding: 0 20px; 
        }
        .site-logo { 
            font-size: 1.8em; font-weight: bold; color: var(--navbar-logo-color); 
            margin-right: 20px; text-decoration: none; flex-shrink: 0; 
        }
        .site-logo:hover { color: var(--navbar-logo-color); opacity: 0.9; }
        .nav-links { 
            list-style: none; display: flex; align-items: center; margin: 0; padding: 0; gap: 25px; 
        }
        .nav-links li { margin-left: 0; }
        .nav-links a { 
            color: var(--navbar-link-color); font-weight: 500; padding: 5px 0; text-decoration: none; 
            transition: color 0.2s ease; 
        }
        .nav-links a:hover:not(.btn-navbar) { color: var(--navbar-logo-color); }
        .welcome-message { color: var(--navbar-link-color); font-weight: 500; margin-right: 15px; white-space: nowrap; }
        .btn-navbar { /* Navbar-specific buttons, smaller */
            display: inline-block; padding: 8px 18px; border-radius: 8px; font-weight: bold; 
            font-size: 0.95em; text-align: center; text-decoration: none; 
            transition: background-color 0.2s ease, opacity 0.2s ease; 
            color: var(--navbar-btn-text-color); border: none; 
        }
        .btn-navbar.dashboard { background-color: var(--navbar-dashboard-btn-bg); margin-left: 10px; }
        .btn-navbar.logout { background-color: var(--navbar-logout-btn-bg); margin-left: 10px; }
        .btn-navbar:hover { opacity: 0.9; }

        .main-footer { 
            background-color: #2f3542; 
            color: #e0e0e0; 
            text-align: center; padding: 25px 0; font-size: 0.9em; margin-top: auto; width: 100%; 
        }
        .main-footer .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .user-dashboard-container { padding: 30px; max-width: 95%; }
            .user-dashboard-container h2 { font-size: 2em; }
            .dashboard-actions { flex-direction: column; gap: 15px; }
            .btn-common { width: 100%; max-width: 300px; } /* Full width buttons on small screens */
            /* Navbar adjustments */
            .main-nav { flex-direction: column; gap: 10px; align-items: flex-start; padding: 0 15px; }
            .site-logo { margin-bottom: 5px; }
            .nav-links { flex-wrap: wrap; justify-content: flex-start; gap: 10px; width: 100%; }
            .nav-links li { margin-left: 0; }
            .welcome-message { margin-right: 0; width: 100%; text-align: center; }
            .btn-navbar.dashboard, .btn-navbar.logout { margin-left: 0; width: auto; flex-grow: 1; }
        }
        @media (max-width: 480px) {
            .user-dashboard-container { padding: 20px; }
            .user-dashboard-container h2 { font-size: 1.8em; }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <nav class="main-nav">
            <a href="../index.php" class="site-logo">Mero Events</a>
            <ul class="nav-links">
                <li><a href="../index.php">Home</a></li>
                <li><a href="../events.php">Events</a></li>
                <li><a href="../about.php">About</a></li>
                <li><a href="../contact.php">Contact</a></li>
                
                <?php
                // Dynamic Dashboard/Logout links for the navbar
                // Since this page is protected, user is always logged in here as a "user" type.
                $dashboard_link = 'dashboard.php'; // Link to current dashboard
                
                echo '<li class="welcome-message">Welcome, ' . htmlspecialchars($_SESSION["user_name"]) . '</li>';
                echo '<li><a href="' . htmlspecialchars($dashboard_link) . '" class="btn-navbar dashboard">Dashboard</a></li>';
                echo '<li><a href="../logout.php" class="btn-navbar logout">Logout</a></li>'; 
                ?>
            </ul>
        </nav>
    </header>

    <main>
        <div class="user-dashboard-container">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
            
            <div class="user-info">
                <p>Account Type: <span class="account-type">User</span></p>
                <p>Email: <strong><?php echo htmlspecialchars($_SESSION['user_email']); ?></strong></p>
            </div>

            <!-- Dashboard Action Buttons -->
            <div class="dashboard-actions">
                <a href="../events.php" class="btn-common secondary">Browse Events</a>
                <a href="my-bookings.php" class="btn-common primary">My Bookings</a> 
                <a href="../logout.php" class="btn-common logout">Logout</a>
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