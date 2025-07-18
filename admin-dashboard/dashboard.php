<?php
// admin-dashboard/dashboard.php

// 1. Use includes/session-admin.php for session protection
require_once '../includes/session-admin.php';
// 1. Use includes/config.php for DB connection (though not strictly needed for this page, good practice for dashboard entry)
require_once '../includes/config.php'; 

// Close the connection immediately if not used for direct queries on this page
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Mero Events</title>
    <!-- Link to your main CSS file -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Specific styling for the Admin Dashboard landing page */
        :root { /* Ensure these are defined for consistency */
            --navbar-bg: #ffffff;
            --navbar-border: #f0f0f0; 
            --navbar-logo-color: #2f3542; /* Dark text for logo */
            --navbar-link-color: #666666; 
            --navbar-dashboard-btn-bg: #4a90e2; 
            --navbar-logout-btn-bg: #e04444; 
            --navbar-btn-text-color: #ffffff; 
            --shadow-color: rgba(0,0,0,0.05); 
            --white: #ffffff;
            --text-color: #2f3542;
            --border-color: #ddd;
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

        main {
            flex-grow: 1;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center; /* Center content vertically */
        }

        .admin-dashboard-container {
            background-color: var(--white);
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-color);
            width: 100%;
            max-width: 800px; /* Adjust width for the cards */
            text-align: center;
        }

        .admin-dashboard-container h2 {
            color: var(--text-color);
            margin-bottom: 30px;
            font-size: 2.8em;
        }

        /* Clean responsive CSS for cards (using Flexbox) */
        .dashboard-cards {
            display: flex;
            flex-wrap: wrap; /* Allow cards to wrap on smaller screens */
            justify-content: center; /* Center cards horizontally */
            gap: 30px; /* Space between cards */
            margin-top: 30px;
        }

        .card {
            background-color: #f8f8f8;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 4px 8px var(--shadow-color);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            flex: 1 1 calc(50% - 30px); /* Two cards per row, accounting for gap */
            min-width: 280px; /* Minimum width for cards */
            text-decoration: none; /* Remove underline from card link */
            color: var(--text-color); /* Default text color */
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px var(--hover-shadow-color);
        }

        .card h3 {
            font-size: 1.8em;
            color: var(--navbar-dashboard-btn-bg); /* Primary color for card titles (reusing navbar blue) */
            margin-bottom: 15px;
        }

        .card p {
            font-size: 1em;
            color: var(--light-text-color);
            margin-bottom: 0;
        }

        /* --- Navbar (Consistent with site-wide theme and image) --- */
        .main-header {
            background-color: var(--navbar-bg);
            box-shadow: 0 2px 5px var(--shadow-color);
            border-bottom: 1px solid var(--navbar-border);
            padding: 15px 0;
        }
        .main-nav { 
            display: flex; justify-content: space-between; align-items: center; 
            max-width: 1200px; margin: 0 auto; padding: 0 20px; /* Centered and padded */
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
        .btn-navbar { 
            display: inline-block; padding: 8px 18px; border-radius: 8px; font-weight: bold; 
            font-size: 0.95em; text-align: center; text-decoration: none; 
            transition: background-color 0.2s ease, opacity 0.2s ease; 
            color: var(--navbar-btn-text-color); border: none; 
        }
        .btn-navbar.dashboard { background-color: var(--navbar-dashboard-btn-bg); margin-left: 10px; }
        .btn-navbar.manage { /* Style for the new "Manage Events" button */
            background-color: #1a73e8; /* Slightly different blue, common in admin UIs */
            margin-left: 10px;
        }
        .btn-navbar.logout { background-color: var(--navbar-logout-btn-bg); margin-left: 10px; }
        .btn-navbar:hover { opacity: 0.9; }

        /* --- Footer (Consistent with site-wide theme) --- */
        .main-footer {
            background-color: var(--text-color); 
            color: #e0e0e0; 
            text-align: center; padding: 25px 0; font-size: 0.9em; margin-top: auto; width: 100%; 
        }
        .main-footer .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .admin-dashboard-container { padding: 20px; }
            .dashboard-cards { gap: 20px; }
            .card { flex: 1 1 calc(50% - 20px); }
            /* Navbar adjustments for smaller screens */
            .main-nav { flex-direction: column; gap: 10px; align-items: flex-start; padding: 0 15px; }
            .site-logo { margin-bottom: 5px; }
            .nav-links { flex-wrap: wrap; justify-content: flex-start; gap: 10px; width: 100%; }
            .nav-links li { margin-left: 0; }
            .welcome-message { margin-right: 0; width: 100%; text-align: center; }
            .btn-navbar.dashboard, .btn-navbar.manage, .btn-navbar.logout { margin-left: 0; width: auto; flex-grow: 1; /* Make buttons grow on small screen */ }
        }

        @media (max-width: 768px) {
            .admin-dashboard-container h2 { font-size: 2.2em; }
            .card { flex: 1 1 100%; max-width: 400px; }
        }
        @media (max-width: 480px) {
            .admin-dashboard-container { padding: 15px; }
            .admin-dashboard-container h2 { font-size: 1.8em; }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <nav class="main-nav">
            <a href="../index.php" class="site-logo">Mero Events (Admin)</a>
            <ul class="nav-links">
                <!-- 2. Label the link as “Manage Events” -->
                <!-- Use a separate style for this specific admin menu link -->
                <li><a href="manage-events.php" class="btn-navbar manage">Manage Events</a></li> 
                
                <!-- This "Dashboard" link refers to the current page itself, it's already there -->
                <li><a href="dashboard.php" class="btn-navbar dashboard">Admin Dashboard</a></li> 
                
                <!-- Logout button that links to ../admin-logout.php -->
                <li><a href="../admin-logout.php" class="btn-navbar logout">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="admin-dashboard-container">
            <h2>Admin Dashboard</h2>
            <p>Welcome to the Mero Events Admin Panel. Select an option to manage content.</p>

            <div class="dashboard-cards">
                <!-- "Approve Events" card -->
                <!-- Note: This card also links to manage-events.php, which is where event approval happens. -->
                <a href="manage-events.php" class="card">
                    <h3>Approve Event Submissions</h3>
                    <p>Review and approve/reject new event listings from organizers.</p>
                </a>

                <!-- "Approve Organizers" card -->
                <a href="approve-organizers.php" class="card">
                    <h3>Approve Organizers</h3>
                    <p>Review and approve new organizer registrations by checking their provided ID proofs.</p>
                </a>
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