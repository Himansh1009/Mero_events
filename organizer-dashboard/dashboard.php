<?php
// organizer-dashboard/dashboard.php

// 1. Use includes/session-organizer.php for session protection.
require_once '../includes/session-organizer.php';
// 1. Use includes/config.php for database connection.
// Although this specific dashboard page might not directly query the DB,
// it's good practice to include it if subsequent pages linked from here will.
require_once '../includes/config.php'; 

// Close the connection immediately if not used for direct queries on this page
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizer Dashboard - Mero Events</title>
    <!-- Link to your main CSS file -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Specific styling for the Organizer Dashboard landing page */
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

        .organizer-dashboard-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 800px; /* Adjust width for the cards */
            text-align: center;
        }

        .organizer-dashboard-container h2 {
            color: #333;
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
        
        /* Consistent styles for header/footer and buttons */
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
                <a href="../index.php" class="site-logo">Mero Events</a>
                <ul class="nav-links">
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="../events.php">Events</a></li>
                    <li><a href="../about.php">About</a></li>
                    <li><a href="../contact.php">Contact</a></li>
                    
                    <?php
                    // Display Welcome message and Logout link
                    echo '<li class="welcome-message">Welcome, ' . htmlspecialchars($_SESSION["user_name"]) . '</li>';
                    echo '<li><a href="dashboard.php" class="btn btn-primary">Dashboard</a></li>'; // Link to current dashboard
                    echo '<li><a href="../logout.php" class="btn btn-primary btn-logout">Logout</a></li>'; 
                    ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="organizer-dashboard-container">
            <!-- Header: "Admin Dashboard" -->
            <h2>Organizer Dashboard</h2>
            <!-- Welcome message showing organizer's name from session. -->
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! Manage your events and track ticket sales here.</p>

            <div class="dashboard-cards">
                <!-- "Create New Event" button -->
                <a href="create-event.php" class="card">
                    <h3>Create New Event</h3>
                    <p>Submit details for a new educational, community, or student-focused event.</p>
                </a>

                <!-- "View My Events" button -->
                <a href="my-events.php" class="card">
                    <h3>View My Events</h3>
                    <p>See all events you've created and track their approval status and ticket sales.</p>
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