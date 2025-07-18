<?php
// organizer-dashboard/manage-events.php

// Enable full error reporting at the top
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Use includes/session-organizer.php for session protection.
require_once '../includes/session-organizer.php';
// Use includes/config.php for database connection.
require_once '../includes/config.php';

// Initialize variables for form data and messages
$message = "";

// Check for messages from delete-event.php redirect
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'deleted_success') {
        $message = "<div class='success-msg'>Event deleted successfully!</div>";
    } elseif ($_GET['status'] == 'deleted_error') {
        $message = "<div class='error-msg'>Failed to delete event. Please try again.</div>";
    } elseif ($_GET['status'] == 'unauthorized') {
        $message = "<div class='error-msg'>You are not authorized to delete that event.</div>";
    } elseif ($_GET['status'] == 'invalid_id') {
        $message = "<div class='error-msg'>Invalid event ID provided for deletion.</div>";
    }
}


// Get the organizer's ID from the session (guaranteed to be set by session-organizer.php)
$organizer_id = $_SESSION["user_id"];

// Fetch events where organizer_id = current organizer ID from events table
$events = [];
$sql = "SELECT 
            id,
            title, 
            event_date,
            event_time,
            location,
            category,
            total_tickets,
            tickets_booked
        FROM 
            events 
        WHERE 
            organizer_id = ?
        ORDER BY 
            event_date DESC, event_time DESC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $organizer_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $events[] = $row;
            }
        } else {
            $message = "<div class='info-msg'>You have not created any events yet.</div>";
        }
        $result->free();
    } else {
        $message = "<div class='error-msg'>Error retrieving your events: " . $stmt->error . "</div>";
    }
    $stmt->close();
} else {
    $message = "<div class='error-msg'>Database error: Could not prepare statement.</div>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Events - Mero Events</title>
    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Define Color Palette for consistency */
        :root {
            --primary-color: #ff6b6b;   /* Reddish-orange */
            --secondary-color: #1dd1a1; /* Teal green */
            --accent-color: #feca57;    /* Yellow-orange */
            --background-color: #f1f2f6;
            --text-color: #2f3542;      /* Dark text */
            --light-text-color: #666666; /* Lighter gray for secondary text */
            --white: #ffffff;
            --border-color: #ddd;
            --shadow-color: rgba(0,0,0,0.05);

            /* Navbar specific colors */
            --navbar-bg: #ffffff;
            --navbar-border: #f0f0f0; 
            --navbar-logo-color: #6A5ACD; /* Blue-purple from image */
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

        main {
            flex-grow: 1;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .my-events-container {
            background-color: var(--white);
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-color);
            width: 100%;
            max-width: 1100px;
            margin: auto;
        }

        .my-events-container h2 {
            text-align: center;
            color: var(--text-color);
            margin-bottom: 30px;
            font-size: 2.5em;
        }

        .events-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }

        .events-table th, .events-table td {
            border: 1px solid var(--border-color);
            padding: 12px 15px;
            text-align: left;
            vertical-align: top;
        }

        .events-table th {
            background-color: var(--background-color);
            color: var(--text-color);
            font-weight: bold;
            white-space: nowrap;
        }

        .events-table tr:nth-child(even) {
            background-color: var(--white); /* Ensure white background for even rows on white table */
        }

        .events-table tr:hover {
            background-color: rgba(0,0,0,0.02); /* Very subtle hover */
        }

        /* Styles for action buttons */
        .events-table .actions {
            white-space: nowrap; /* Keep buttons on one line */
            text-align: center;
        }
        .events-table .actions a.btn,
        .events-table .actions button.btn {
            display: inline-block;
            padding: 8px 12px;
            font-size: 0.9em;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.2s ease;
            margin: 0 3px; /* Space between buttons */
        }

        /* Re-apply btn-primary for Edit button */
        .events-table .actions a.btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
            border: 1px solid var(--primary-color);
        }
        .events-table .actions a.btn-primary:hover {
            background-color: #e65a5a;
            border-color: #e65a5a;
        }

        /* Style for Delete button (danger/red) */
        .events-table .actions button.btn-danger {
            background-color: var(--navbar-logout-btn-bg); /* Red */
            color: var(--white);
            border: 1px solid var(--navbar-logout-btn-bg);
        }
        .events-table .actions button.btn-danger:hover {
            background-color: #c82333; /* Darker red */
            border-color: #c82333;
        }


        /* Message styling (consistent) */
        .message {
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        .success-msg { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-msg { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info-msg { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

        /* Header/Footer (consistent with global theme) */
        .main-header {
            background-color: var(--navbar-bg);
            box-shadow: 0 2px 5px var(--shadow-color);
            border-bottom: 1px solid var(--navbar-border);
            padding: 15px 0;
        }
        .main-nav { display: flex; justify-content: space-between; align-items: center; }
        .site-logo { font-size: 1.8em; font-weight: bold; color: var(--navbar-logo-color); margin-right: 20px; text-decoration: none; }
        .site-logo:hover { color: var(--navbar-logo-color); opacity: 0.9; }
        .nav-links { list-style: none; display: flex; align-items: center; margin: 0; padding: 0; }
        .nav-links li { margin-left: 25px; }
        .nav-links a { color: var(--navbar-link-color); font-weight: 500; padding: 5px 0; text-decoration: none; transition: color 0.2s ease; }
        .nav-links a:not(.btn-navbar):hover { color: var(--navbar-logo-color); }
        .welcome-message { color: var(--navbar-link-color); font-weight: 500; margin-right: 15px; white-space: nowrap; }
        .btn-navbar { display: inline-block; padding: 8px 18px; border-radius: 8px; font-weight: bold; font-size: 0.95em; text-align: center; text-decoration: none; transition: background-color 0.2s ease, opacity 0.2s ease; color: var(--navbar-btn-text-color); border: none; }
        .btn-navbar.dashboard { background-color: var(--navbar-dashboard-btn-bg); }
        .btn-navbar.logout { background-color: var(--navbar-logout-btn-bg); }
        .btn-navbar:hover { opacity: 0.9; }

        .main-footer {
            background-color: var(--text-color);
            color: var(--white);
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

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .my-events-container { padding: 20px; }
            .my-events-container h2 { font-size: 2em; }
            .events-table th, .events-table td { padding: 8px 10px; font-size: 0.9em; }
            .events-table .actions a.btn, .events-table .actions button.btn { padding: 6px 8px; font-size: 0.8em; margin: 0 2px; }
            /* Force table to scroll horizontally */
            .events-table-wrapper {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch; /* for smooth scrolling on iOS */
            }
            .events-table {
                width: 700px; /* Minimum width to prevent crushing content */
            }
        }
        @media (max-width: 480px) {
            .my-events-container { padding: 15px; }
            .my-events-container h2 { font-size: 1.8em; }
            .main-nav { flex-direction: column; align-items: flex-start; gap: 10px; }
            .site-logo { margin-bottom: 10px; }
            .nav-links { flex-wrap: wrap; gap: 8px; }
            .nav-links li { margin-left: 0; }
            .welcome-message { display: block; text-align: center; width: 100%; }
            .btn-navbar { margin-left: 0 !important; width: 48%; /* Adjust for 2 buttons per row */ }
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
                    $dashboard_link = 'dashboard.php';
                    echo '<li class="welcome-message">Welcome, ' . htmlspecialchars($_SESSION["user_name"]) . '</li>';
                    echo '<li><a href="' . htmlspecialchars($dashboard_link) . '" class="btn-navbar dashboard">Dashboard</a></li>';
                    echo '<li><a href="../logout.php" class="btn-navbar logout">Logout</a></li>'; 
                    ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="my-events-container">
            <h2>Your Event Overview & Sales</h2>
            
            <?php 
            if (!empty($message)) {
                echo "<div class='message'>" . $message . "</div>";
            }
            ?>

            <?php if (!empty($events)): ?>
                <div class="events-table-wrapper"> <!-- Added wrapper for horizontal scrolling on small screens -->
                    <table class="events-table">
                        <thead>
                            <tr>
                                <th>Event Title</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Location</th>
                                <th>Category</th>
                                <th>Total Tickets</th>
                                <th>Tickets Booked</th>
                                <th>Tickets Remaining</th>
                                <th>Actions</th> <!-- Added "Actions" column -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                    <td><?php echo htmlspecialchars(date('M d, Y', strtotime($event['event_date']))); ?></td>
                                    <td><?php echo htmlspecialchars(date('h:i A', strtotime($event['event_time']))); ?></td>
                                    <td><?php echo htmlspecialchars($event['location']); ?></td>
                                    <td><?php echo htmlspecialchars($event['category']); ?></td>
                                    <td><?php echo htmlspecialchars($event['total_tickets']); ?></td>
                                    <td><?php echo htmlspecialchars($event['tickets_booked']); ?></td>
                                    <td>
                                        <?php 
                                        $tickets_remaining = $event['total_tickets'] - $event['tickets_booked'];
                                        echo htmlspecialchars($tickets_remaining);
                                        ?>
                                    </td>
                                    <td class="actions">
                                        <!-- 1. Add an “Edit” button -->
                                        <a href="edit-event.php?event_id=<?php echo htmlspecialchars($event['id']); ?>" class="btn btn-primary">Edit</a>
                                        <!-- 2. Add a “Delete” button -->
                                        <form action="delete-event.php" method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this event? This action cannot be undone and will delete all associated bookings.');">
                                            <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event['id']); ?>">
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="info-msg">
                    <p>You have not created any events yet.</p>
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