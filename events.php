<?php
// events.php

// 5. Connect to the MySQL database
require_once 'includes/config.php';

$events = []; // Initialize an empty array to store fetched events
$message = ""; // To display any messages (e.g., no events found)

// 2. Fetch events from the 'events' table where status = 'approved'
// Join with 'organizers' table to get the organizer’s name
$sql = "SELECT 
            e.id, 
            e.title, 
            e.description, 
            e.event_date, 
            e.event_time, 
            e.location, 
            e.category, 
            o.name AS organizer_name 
        FROM 
            events e
        JOIN 
            organizers o ON e.organizer_id = o.id
        WHERE 
            e.status = 'approved'
        ORDER BY 
            e.event_date ASC, e.event_time ASC"; 

if ($result = $conn->query($sql)) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
    } else {
        $message = "<div class='info-msg'>No approved events found at the moment. Please check back later!</div>";
    }
    $result->free(); // Free result set
} else {
    $message = "<div class='error-msg'>Error retrieving events: " . $conn->error . "</div>";
}

$conn->close(); // Close the database connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 6. Title of the page -->
    <title>Browse Events - Mero Events</title>
    <!-- 4. Link to external stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Specific styling for the events page */
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
        }

        .events-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .events-header h1 {
            font-size: 2.8em;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .events-header p {
            font-size: 1.1em;
            color: #666;
            max-width: 700px;
            margin: 0 auto;
        }

        /* 4. Use flexbox to show events responsively */
        .events-grid {
            display: flex;
            flex-wrap: wrap; /* Allow cards to wrap to the next line */
            gap: 30px;
            justify-content: center; /* Center cards horizontally */
            align-items: stretch; /* Make cards stretch to equal height */
        }

        .event-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            padding: 25px;
            flex: 1 1 calc(33% - 40px); /* Approx 3 cards per row, accounting for gap */
            min-width: 280px; 
            max-width: 380px; /* Max width to prevent cards from becoming too wide */
            display: flex;
            flex-direction: column;
            justify-content: space-between; /* Push footer info to bottom */
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.12);
        }

        .event-card h3 {
            font-size: 1.8em;
            color: #007bff;
            margin-bottom: 15px;
            line-height: 1.3;
        }

        .event-card p {
            font-size: 1em;
            color: #666;
            margin-bottom: 10px;
            line-height: 1.6;
        }
        
        .event-card p strong {
            color: #333;
        }

        .event-card .description {
            font-size: 0.95em;
            color: #444;
            margin-bottom: 20px;
        }

        .event-meta {
            font-size: 0.9em;
            color: #888;
            border-top: 1px solid #eee;
            padding-top: 15px;
            margin-top: 15px;
        }
        .event-meta p {
            margin-bottom: 5px;
        }
        .event-meta .organizer-name {
            font-weight: bold;
            color: #555;
        }

       
        @media (max-width: 992px) {
            .event-card {
                flex: 1 1 calc(50% - 40px); /* 2 cards per row on medium screens */
            }
        }

        @media (max-width: 768px) {
            .events-header h1 {
                font-size: 2.2em;
            }
            .events-header p {
                font-size: 1em;
            }
            .event-card {
                flex: 1 1 100%; /* 1 card per row on small screens */
                max-width: 450px; /* Max width for single column card */
            }
        }
        @media (max-width: 480px) {
            main {
                padding: 20px 15px;
            }
        }

        /* Message styling (reused from other pages) */
        .message {
            margin: 20px auto; /* Center the message */
            padding: 12px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            max-width: 600px;
        }
        .info-msg {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .error-msg {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Header/Footer styles (consistent with other pages) */
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

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .btn-secondary {
            background-color: #28a745;
            color: #fff;
            border: 1px solid #28a745;
        }

        .btn-secondary:hover {
            background-color: #218838;
            border-color: #1e7e34;
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
                <a href="index.php" class="site-logo">Mero Events</a>
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="events.php">Events</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    
                    <?php
                    // Dynamic Login/Dashboard/Logout links (reused from index.php logic)
                    // This section is public, so check session status
                    session_start(); // Ensure session is started for this check
                    if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
                        $dashboard_link = '#'; // Default fallback
                        
                        if (isset($_SESSION["user_type"])) {
                            if ($_SESSION["user_type"] == "organizer") {
                                $dashboard_link = 'organizer-dashboard/dashboard.php';
                            } elseif ($_SESSION["user_type"] == "user") {
                                $dashboard_link = 'user-dashboard/dashboard.php';
                            }
                        }

                        echo '<li class="welcome-message">Welcome, ' . htmlspecialchars($_SESSION["user_name"]) . '</li>';
                        echo '<li><a href="' . htmlspecialchars($dashboard_link) . '" class="btn btn-primary">Dashboard</a></li>';
                        echo '<li><a href="logout.php" class="btn btn-primary" style="background-color: #dc3545;">Logout</a></li>'; 
                    } else {
                        echo '<li><a href="auth.php" class="btn btn-primary">Login/Register</a></li>';
                    }
                    ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="events-header">
                <h1>Upcoming Events in Bharatpur</h1>
                <p>Explore a variety of educational, community, and student-focused events happening in your local region. Find programs that match your interests and empower your participation!</p>
            </div>

            <?php 
            // Display messages (error or info for no events)
            if (!empty($message)) {
                echo "<div class='message'>" . $message . "</div>";
            }
            ?>

            <?php if (!empty($events)): ?>
                <div class="events-grid">
                    <?php foreach ($events as $event): ?>
                        <div class="event-card">
                            <div>
                                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                <p class="description">
                                    <?php 
                                        // Display a brief description (e.g., first 150 characters)
                                        $short_description = htmlspecialchars($event['description']);
                                        if (strlen($short_description) > 150) {
                                            $short_description = substr($short_description, 0, 150) . '...';
                                        }
                                        echo $short_description;
                                    ?>
                                </p>
                            </div>
                            <div class="event-meta">
                                <p><strong>Date:</strong> <?php echo htmlspecialchars(date('M d, Y', strtotime($event['event_date']))); ?></p>
                                <p><strong>Time:</strong> <?php echo htmlspecialchars(date('h:i A', strtotime($event['event_time']))); ?></p>
                                <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                                <p><strong>Category:</strong> <?php echo htmlspecialchars($event['category']); ?></p>
                                <p><strong>Organizer:</strong> <span class="organizer-name"><?php echo htmlspecialchars($event['organizer_name']); ?></span></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="main-footer">
        <div class="container">
            <p>© 2023 Mero Events. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>