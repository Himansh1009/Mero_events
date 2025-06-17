<?php
// admin-dashboard/dashboard.php

// 1. Use includes/session-admin.php for session protection
require_once '../includes/session-admin.php';
// 1. Use includes/config.php for DB connection (though not strictly needed for this page, good practice for dashboard entry)
require_once '../includes/config.php'; 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
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
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 800px; /* Adjust width for the cards */
            text-align: center;
        }

        .admin-dashboard-container h2 {
            color: #333;
            margin-bottom: 30px;
            font-size: 2.8em;
        }

        /* 3. Clean responsive CSS for cards (using Flexbox) */
        .dashboard-cards {
            display: flex;
            flex-wrap: wrap; /* Allow cards to wrap on smaller screens */
            justify-content: center; /* Center cards horizontally */
            gap: 30px; /* Space between cards */
            margin-top: 30px;
        }

        .card {
            background-color: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            flex: 1 1 calc(50% - 30px); /* Two cards per row, accounting for gap */
            min-width: 280px; /* Minimum width for cards */
            text-decoration: none; /* Remove underline from card link */
            color: #333; /* Default text color */
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.12);
        }

        .card h3 {
            font-size: 1.8em;
            color: #007bff; /* Primary color for card titles */
            margin-bottom: 15px;
        }

        .card p {
            font-size: 1em;
            color: #666;
            margin-bottom: 0;
        }

        /* Header/Footer (consistent with other pages) */
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

        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
            transition: background-color 0.3s ease;
            text-decoration: none;
        }
        .btn-primary {
            background-color: #007bff;
            color: #fff;
            border: 1px solid #007bff;
        }
        .main-footer {
            background-color: #333;
            color: #fff;
            text-align: center;
            padding: 25px 0;
            font-size: 0.9em;
            margin-top: auto;
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
                <a href="../index.php" class="site-logo">Mero Events (Admin)</a>
                <ul class="nav-links">
                    <!-- 4. Simple navbar with logout option -->
                    <li><a href="../admin-logout.php" class="btn btn-primary" style="background-color: #dc3545;">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="admin-dashboard-container">
            <!-- 2. Header: "Admin Dashboard" -->
            <h2>Admin Dashboard</h2>
            <p>Welcome to the Mero Events Admin Panel. Select an option to manage content.</p>

            <div class="dashboard-cards">
                <!-- 2. "Approve Events" card -->
                <a href="manage-events.php" class="card">
                    <h3>Approve Events</h3>
                    <p>View and manage all event submissions. Approve or reject events to control what appears on the public site.</p>
                </a>

                <!-- 2. "Approve Organizers" card -->
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