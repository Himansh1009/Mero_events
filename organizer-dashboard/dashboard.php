<?php
// organizer-dashboard/dashboard.php

// Use includes/session-organizer.php for session protection.
require_once '../includes/session-organizer.php';
// Use includes/config.php for database connection.
// Although this specific dashboard page might not directly query the DB,
// it's good practice to include it if subsequent pages linked from here will.
require_once '../includes/config.php'; 

// Close the connection immediately if not used for direct queries on this page
// It's safer to not close it here, and let it auto-close at script end,
// or be explicitly closed in files that perform queries.
// $conn->close(); // REMOVED: Do not close here prematurely
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

            /* Navbar specific colors */
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

        .organizer-dashboard-container {
            background-color: var(--white); /* Use var */
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-color); /* Use var */
            width: 100%;
            max-width: 800px; 
            text-align: center;
        }

        .organizer-dashboard-container h2 {
            color: var(--text-color); /* Use var */
            margin-bottom: 25px;
            font-size: 2.5em; 
            font-weight: bold;
        }

        /* Clean responsive CSS for cards (using Flexbox) */
        .dashboard-cards {
            display: flex;
            flex-wrap: wrap; 
            justify-content: center;
            gap: 30px; 
            margin-top: 30px;
        }

        .card {
            background-color: #f8f8f8;
            border: 1px solid var(--border-color); /* Use var */
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 4px 8px var(--shadow-color); /* Use var */
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            flex: 1 1 calc(33.333% - 30px); /* Adjusted for 3 cards per row */
            min-width: 200px; /* Adjusted min-width */
            text-decoration: none; 
            color: var(--text-color); /* Use var */
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px var(--hover-shadow-color); /* Use var */
        }

        .card h3 {
            font-size: 1.5em; /* Adjusted font size */
            color: var(--navbar-dashboard-btn-bg); /* Use var for consistency */
            margin-bottom: 15px;
        }

        .card p {
            font-size: 0.95em; /* Adjusted font size */
            color: var(--light-text-color); /* Use var */
            margin-bottom: 0;
        }
        
        /* Consistent styles for header/footer and buttons */
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
        .btn-navbar { 
            display: inline-block; padding: 8px 18px; border-radius: 8px; font-weight: bold; 
            font-size: 0.95em; text-align: center; text-decoration: none; 
            transition: background-color 0.2s ease, opacity 0.2s ease; 
            color: var(--navbar-btn-text-color); border: none; 
        }
        .btn-navbar.dashboard { background-color: var(--navbar-dashboard-btn-bg); margin-left: 10px; }
        .btn-navbar.logout { background-color: var(--navbar-logout-btn-bg); margin-left: 10px; }
        .btn-navbar:hover { opacity: 0.9; }

        .main-footer { 
            background-color: var(--text-color); 
            color: var(--white); 
            text-align: center; padding: 25px 0; font-size: 0.9em; margin-top: auto; width: 100%; 
        }
        .main-footer .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .organizer-dashboard-container { padding: 30px; max-width: 95%; }
            .dashboard-cards { gap: 20px; }
            .card { flex: 1 1 calc(50% - 20px); max-width: unset; } /* Two cards per row */
            /* Navbar adjustments */
            .main-nav { flex-direction: column; gap: 10px; align-items: flex-start; padding: 0 15px; }
            .site-logo { margin-bottom: 5px; }
            .nav-links { flex-wrap: wrap; justify-content: flex-start; gap: 10px; width: 100%; }
            .nav-links li { margin-left: 0; }
            .welcome-message { margin-right: 0; width: 100%; text-align: center; }
            .btn-navbar.dashboard, .btn-navbar.logout { margin-left: 0; width: auto; flex-grow: 1; }
        }

        @media (max-width: 768px) {
            .organizer-dashboard-container h2 { font-size: 2em; }
            .dashboard-cards { flex-direction: column; align-items: center; gap: 20px; }
            .card { flex: 1 1 100%; max-width: 350px; } /* Single column on small screens */
        }
        @media (max-width: 480px) {
            .organizer-dashboard-container { padding: 20px; }
            .organizer-dashboard-container h2 { font-size: 1.8em; }
            .card { padding: 25px; }
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
                // Display Welcome message and Logout link
                echo '<li class="welcome-message">Welcome, ' . htmlspecialchars($_SESSION["user_name"]) . '</li>';
                echo '<li><a href="dashboard.php" class="btn-navbar dashboard">Dashboard</a></li>'; // Link to current dashboard
                echo '<li><a href="../logout.php" class="btn-navbar logout">Logout</a></li>'; 
                ?>
            </ul>
        </nav>
    </header>

    <main>
        <div class="organizer-dashboard-container">
            <h2>Organizer Dashboard</h2>
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

                <!-- NEW: View Registrations Card -->
                <a href="view-registrations.php" class="card">
                    <h3>View Registrations</h3>
                    <p>See who has booked tickets for your events and download registration lists.</p>
                </a>
            </div>
        </main>

    <footer class="main-footer">
        <div class="container">
            <p>© 2023 Mero Events. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>