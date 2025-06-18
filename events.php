<?php
// events.php (or browse-events.php)

session_start(); // PHP session for navbar

// Use includes/config.php for database connection.
require_once 'includes/config.php';

$events = []; // Initialize an empty array to store fetched events
$message = ""; // To display any messages (e.g., no events found)

// Fetch Events:
// Query events where status = 'approved'
// JOIN organizers table for organizer name
// SELECT e.id (very important!)
// Order by event_date ASC
$sql = "SELECT 
            e.id,           /* VERY IMPORTANT: e.id included for links */
            e.title, 
            e.event_date, 
            e.event_time, 
            e.location, 
            e.category, 
            e.total_tickets, 
            e.tickets_booked,
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
        // If no events found, display: “No upcoming events found.”
        $message = "<div class='info-msg'>No upcoming events found.</div>";
    }
    $result->free(); // Free result set
} else {
    // Handle SQL errors gracefully.
    $message = "<div class='error-msg'>Error retrieving events from database: " . $conn->error . "</div>";
}

$conn->close(); // Close the database connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse All Events - Mero Events</title>
    <!-- Use Bootstrap or clean responsive CSS for nice layout -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Specific styling for the browse-events page */
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

        /* Responsive grid system for cards */
        .events-grid {
            display: flex;
            flex-wrap: wrap; /* Allow cards to wrap to the next line */
            gap: 30px; /* Space between cards */
            justify-content: center; /* Center cards horizontally */
            align-items: stretch; /* Make cards stretch to equal height */
        }

        /* Card-based layout for event listings. */
        .event-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            padding: 25px;
            flex: 1 1 calc(33.333% - 40px); /* Roughly 3 cards per row, accounting for gap */
            min-width: 280px; /* Minimum width for responsiveness */
            max-width: 380px; /* Max width to prevent cards from becoming too wide */
            display: flex;
            flex-direction: column;
            justify-content: space-between; /* Push action area to bottom */
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

        .event-info-block {
            font-size: 1em;
            color: #666;
            margin-bottom: 20px; /* Space before action area */
        }
        
        .event-info-block p {
            margin: 0 0 8px 0; /* Adjust margin for paragraphs within info block */
        }

        .event-info-block strong {
            color: #333;
        }
        
        .event-info-block .tickets-info {
            font-weight: bold;
        }
        .event-info-block .tickets-remaining-count {
            color: #28a745; /* Green for remaining */
        }
        .event-info-block .tickets-sold-out-count {
            color: #dc3545; /* Red for sold out */
        }

        .event-card .action-area {
            /* Ensures button is at the bottom, separate from info */
            padding-top: 15px; /* Space above the button */
            border-top: 1px solid #eee;
            text-align: center; /* Center the button within its area */
        }
        
        .event-card .book-button {
            display: inline-block;
            background-color: #007bff; /* Using btn-primary color */
            color: #fff;
            padding: 10px 20px; /* Standard button padding */
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .event-card .book-button:hover {
            background-color: #0056b3; /* Darker blue on hover */
        }
        
        .event-card .sold-out-badge {
            background-color: #dc3545; /* Red for sold out */
            color: #fff;
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.9em;
            text-transform: uppercase;
            display: inline-block; /* For proper padding and centering */
        }
        
        .event-card .book-button.disabled {
            background-color: #ccc;
            cursor: not-allowed;
            pointer-events: none; /* Disable click */
            opacity: 0.7; /* Make it look disabled */
        }

        /* Responsive adjustments for events grid */
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
        
        .btn { /* Basic .btn from style.css for consistent button styling */
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
            transition: background-color 0.3s ease;
            text-decoration: none;
        }

        .btn-primary { /* Primary button style */
            background-color: #007bff;
            color: #fff;
            border: 1px solid #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
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
                    // Dynamic Login/Dashboard/Logout links for the navbar
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
                <h1>Explore All Approved Events</h1>
                <p>Discover a variety of engaging educational programs, community gatherings, and student initiatives happening in Bharatpur, Nepal. Your next experience is just a click away!</p>
            </div>

            <?php 
            // Display messages (error or info for no events)
            if (!empty($message)) {
                echo "<div class='message'>" . $message . "</div>";
            }
            ?>

            <?php if (!empty($events)): ?>
                <div class="events-grid">
                    <?php foreach ($events as $event): 
                        // Calculate Tickets Remaining
                        $tickets_remaining = $event['total_tickets'] - $event['tickets_booked'];
                        $is_sold_out = ($tickets_remaining <= 0);
                    ?>
                        <div class="event-card">
                            <div>
                                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                <div class="event-info-block">
                                    <p><strong>Date:</strong> <?php echo htmlspecialchars(date('M d, Y', strtotime($event['event_date']))); ?></p>
                                    <p><strong>Time:</strong> <?php echo htmlspecialchars(date('h:i A', strtotime($event['event_time']))); ?></p>
                                    <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                                    <p><strong>Category:</strong> <?php echo htmlspecialchars($event['category']); ?></p>
                                    <p><strong>Organizer:</strong> <?php echo htmlspecialchars($event['organizer_name']); ?></p>
                                    <p class="tickets-info">
                                        <strong>Tickets:</strong> 
                                        <?php if ($is_sold_out): ?>
                                            <span class="tickets-sold-out-count">Sold Out</span> (<?php echo htmlspecialchars($event['tickets_booked']); ?>/<?php echo htmlspecialchars($event['total_tickets']); ?>)
                                        <?php else: ?>
                                            <span class="tickets-remaining-count"><?php echo htmlspecialchars($tickets_remaining); ?></span> remaining
                                            (<?php echo htmlspecialchars($event['tickets_booked']); ?>/<?php echo htmlspecialchars($event['total_tickets']); ?> booked)
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="action-area">
                                <?php if ($is_sold_out): ?>
                                    <!-- Display “Sold Out” badge and disable the Book Now button. -->
                                    <span class="sold-out-badge">Sold Out</span>
                                <?php else: ?>
                                    <!-- A "Book Now" button that links properly -->
                                    <!-- Note: Using 'id' parameter for consistency with event-details.php as previously generated. -->
                                    <a href="event-details.php?event_id=<?php echo htmlspecialchars($event['id']); ?>" class="btn btn-primary book-button">Book Now</a>
                                <?php endif; ?>
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