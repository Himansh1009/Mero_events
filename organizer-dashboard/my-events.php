<?php
// organizer-dashboard/my-events.php

// Use session protection to ensure only logged-in organizers can access this dashboard
require_once '../includes/session-organizer.php';
// Use includes/config.php for DB connection
require_once '../includes/config.php';

$message = ""; // For displaying feedback messages to the user
// Get logged-in organizer's user_id from session
$organizer_id = $_SESSION['user_id'];

// Check for messages from delete-event.php redirects (PHP LOGIC - UNCHANGED)
if (isset($_GET['deletion_status'])) {
    if ($_GET['deletion_status'] == 'success') {
        $message = "<div class='success-msg'>Event deleted successfully.</div>";
    } elseif ($_GET['deletion_status'] == 'error') {
        $message = "<div class='error-msg'>Failed to delete event. Please try again.</div>";
    } elseif ($_GET['deletion_status'] == 'unauthorized') {
        $message = "<div class='error-msg'>You are not authorized to delete that event.</div>";
    } elseif ($_GET['deletion_status'] == 'invalid_id') {
        $message = "<div class='error-msg'>Invalid event ID provided for deletion.</div>";
    }
}

$events = []; // Array to store fetched events

// Fetch events where organizer_id = current organizer ID from events table (PHP LOGIC - UNCHANGED)
$sql = "SELECT 
            id,
            title, 
            event_date,
            event_time,
            location,
            category,
            total_tickets,
            tickets_booked,
            status /* Include status to show in table */
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

$conn->close(); // Close database connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage My Events - Mero Events</title>
    <!-- Include Font Awesome for icons (e.g. for Edit button) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Define Color Palette for consistency with project theme */
        :root {
            /* General project colors (from previous prompts) */
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

            /* Navbar specific colors (from previous prompt's image) */
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
            padding: 40px 20px; /* Padding around the content area */
            display: flex;
            justify-content: center;
            align-items: flex-start; /* Align content to the top */
        }

        .my-events-container {
            background-color: var(--white); /* White background */
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-color); /* Subtle shadow */
            width: 100%;
            max-width: 1100px; /* Max width to match general layout */
            margin: auto; /* Center the container */
            box-sizing: border-box; /* Include padding in width */
        }

        .my-events-container h2 {
            text-align: center;
            color: var(--text-color);
            margin-bottom: 30px;
            font-size: 2.5em; /* Larger heading */
            font-weight: bold; /* Bold as per image */
        }

        /* --- Responsive Table Wrapper --- */
        /* This is the key fix for "out of the box" issue */
        .table-responsive-wrapper {
            width: 100%; /* Ensures it respects parent's width */
            overflow-x: auto; /* Enables horizontal scrolling if table content is too wide */
            -webkit-overflow-scrolling: touch; /* For smooth scrolling on iOS */
            border-radius: 8px; /* Match container rounding */
            box-shadow: 0 2px 5px rgba(0,0,0,0.03); /* Subtle shadow for the scrollable area */
            margin-top: 30px; /* Space from heading */
        }

        /* --- Table Styling (Matching Screenshot Precisely) --- */
        .events-table {
            width: 100%; /* Ensure table takes full width of its wrapper */
            min-width: 900px; /* Set a minimum width to prevent columns from scrunching too much before scroll kicks in */
            border-collapse: separate; /* Allows border-radius and spacing on cells */
            border-spacing: 0; /* Remove default spacing */
            background-color: var(--white); /* Table background */
        }

        .events-table th, .events-table td {
            padding: 15px 20px; /* Generous padding as per screenshot */
            text-align: left;
            vertical-align: middle; /* Center vertically in cells */
            font-size: 0.95em;
            color: var(--text-color);
            border-bottom: 1px solid #eeeeee; /* Lighter separator for rows */
            white-space: nowrap; /* Keep content on single line as seen in screenshot */
        }

        .events-table th {
            background-color: #f8f8f8; /* Light gray header background */
            color: var(--text-color);
            font-weight: bold;
            border-bottom: 1px solid var(--border-color); /* Separator line for header */
        }
        /* Specific rounding for table headers */
        .events-table thead th:first-child { border-top-left-radius: 8px; }
        .events-table thead th:last-child { border-top-right-radius: 8px; }

        .events-table tbody tr:last-child td { /* No bottom border for last row */
            border-bottom: none;
        }

        .events-table tr:hover {
            background-color: #f9f9f9; /* Subtle hover effect */
        }

        /* --- Status Badges (Matching Screenshot) --- */
        .status-approved {
            background-color: #e6ffe6; /* Very light green background */
            color: #28a745; /* Green text */
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.85em;
            white-space: nowrap;
        }
        .status-pending {
            background-color: #fff3cd; /* Light yellow background */
            color: #856404; /* Dark yellow text */
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.85em;
            white-space: nowrap;
        }
        .status-rejected {
            background-color: #f8d7da; /* Light red background */
            color: #721c24; /* Dark red text */
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.85em;
            white-space: nowrap;
        }

        /* --- Action Buttons (Matching Screenshot) --- */
        .events-table .actions {
            white-space: nowrap;
            text-align: center;
            display: flex; /* Use flex to align buttons */
            gap: 8px; /* Space between buttons */
            justify-content: center; /* Center buttons within cell */
        }

        .events-table .actions a.btn-edit,
        .events-table .actions form button.btn-delete {
            display: flex; /* Make buttons flex to center content/icon */
            align-items: center;
            justify-content: center;
            padding: 0; /* Remove default padding, set via width/height */
            height: 32px; /* Fixed height to match screenshot */
            font-size: 0.9em;
            font-weight: bold;
            border-radius: 8px; /* Rounded corners as in image */
            cursor: pointer;
            border: none;
            color: var(--white);
            text-decoration: none; /* For link button */
            transition: background-color 0.2s ease, opacity 0.2s ease;
            box-sizing: border-box; /* Ensure padding/border don't affect size */
        }

        /* Edit Button (Blue Square) */
        .events-table .actions a.btn-edit {
            background-color: #4a90e2; /* Blue from screenshot */
            width: 32px; /* Fixed width for square */
        }
        .events-table .actions a.btn-edit:hover {
            background-color: #357bd8; /* Darker blue on hover */
        }
        /* No specific icon color needed if Font Awesome is correctly set up */

        /* Delete Button (Red Rectangle) */
        .events-table .actions form button.btn-delete {
            background-color: #e04444; /* Red from screenshot */
            width: 80px; /* Fixed width to match text length */
        }
        .events-table .actions form button.btn-delete:hover {
            background-color: #cc3939; /* Darker red on hover */
        }
        .events-table .actions form { /* Adjust form display within cell for flex context */
            display: contents; /* Makes form content directly participate in flex layout */
        }
        .events-table .actions form button { /* Specific button styles if they don't inherit */
            white-space: nowrap; /* Important for "Delete" text */
        }


        /* Message styling (reused) */
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

        /* --- Header/Footer (Consistent with new site-wide theme) --- */
        .main-header {
            background-color: var(--navbar-bg);
            box-shadow: 0 2px 5px var(--shadow-color);
            border-bottom: 1px solid var(--navbar-border);
            padding: 15px 0;
        }
        .main-nav { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .site-logo { font-size: 1.8em; font-weight: bold; color: var(--text-color); /* Matches screenshot logo color */ margin-right: 20px; text-decoration: none; flex-shrink: 0; }
        .site-logo:hover { color: var(--text-color); opacity: 0.9; } /* Subtle hover, no color change */
        .nav-links { list-style: none; display: flex; align-items: center; margin: 0; padding: 0; gap: 25px; }
        .nav-links li { margin-left: 0; }
        .nav-links a { color: var(--light-text-color); font-weight: 500; padding: 5px 0; transition: color 0.2s ease; text-decoration: none; }
        .nav-links a:hover:not(.btn-navbar) { color: var(--text-color); } /* Slightly darker on hover */
        .welcome-message { color: var(--light-text-color); font-weight: 500; margin-right: 15px; white-space: nowrap; }
        .btn-navbar { display: inline-block; padding: 8px 18px; border-radius: 8px; font-weight: bold; font-size: 0.95em; text-align: center; text-decoration: none; transition: background-color 0.2s ease, opacity 0.2s ease; color: var(--navbar-btn-text-color); border: none; }
        .btn-navbar.dashboard { background-color: var(--navbar-dashboard-btn-bg); margin-left: 10px; }
        .btn-navbar.logout { background-color: var(--navbar-logout-btn-bg); margin-left: 10px; }
        .btn-navbar:hover { opacity: 0.9; }

        .main-footer { 
            background-color: #2f3542; /* Use theme's text-color for dark footer */ 
            color: #e0e0e0; /* Light gray for general text */
            text-align: center; padding: 25px 0; font-size: 0.9em; margin-top: auto; width: 100%; 
        }
        .main-footer .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

        /* Responsive Adjustments for table */
        @media (max-width: 992px) {
            .my-events-container { padding: 20px; max-width: 95%; }
            .events-table { min-width: 700px; /* Adjust min-width if columns still scrunch */ }
            .events-table th, .events-table td { padding: 12px 15px; font-size: 0.9em; }
            .events-table .actions { gap: 5px; } /* Reduce gap */
            .events-table .actions a.btn-edit, .events-table .actions form button.btn-delete { height: 30px; font-size: 0.85em; }
            .events-table .actions a.btn-edit { width: 30px; }
            .events-table .actions form button.btn-delete { width: 70px; }
            
            /* Navbar adjustments */
            .main-nav { flex-direction: column; gap: 10px; align-items: flex-start; }
            .nav-links { flex-wrap: wrap; justify-content: flex-start; gap: 10px; }
            .nav-links li { margin-left: 0; }
            .welcome-message { margin-right: 0; width: 100%; text-align: center; }
            .btn-navbar.dashboard, .btn-navbar.logout { margin-left: 0; }
        }

        @media (max-width: 768px) {
            .my-events-container h2 { font-size: 2em; }
            /* Hide headers and stack cells for true mobile responsiveness */
            .events-table { /* Keep display: block; overflow-x: auto */ }
            .events-table thead, .events-table tbody, .events-table th, .events-table td, .events-table tr { display: block; }
            .events-table thead tr { position: absolute; top: -9999px; left: -9999px; } /* Hide original headers */
            .events-table tr { margin-bottom: 15px; border: 1px solid #eee; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
            .events-table td { border: none; position: relative; padding-left: 50%; text-align: right; }
            .events-table td:before { /* Data labels for stacked view */
                content: attr(data-label);
                position: absolute;
                left: 10px; /* Adjust padding-left for label */
                width: 45%;
                white-space: nowrap;
                text-align: left;
                font-weight: bold;
                color: var(--text-color);
            }
            .events-table .actions { justify-content: flex-end; padding-top: 10px; border-top: 1px solid #eee; margin-top: 10px; gap: 8px;}
        }
        @media (max-width: 480px) {
            .my-events-container { padding: 15px; }
            .events-table th, .events-table td { padding: 8px 10px; }
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
                $dashboard_link = 'dashboard.php';
                echo '<li class="welcome-message">Welcome, ' . htmlspecialchars($_SESSION["user_name"]) . '</li>';
                echo '<li><a href="' . htmlspecialchars($dashboard_link) . '" class="btn-navbar dashboard">Dashboard</a></li>';
                echo '<li><a href="../logout.php" class="btn-navbar logout">Logout</a></li>'; 
                ?>
            </ul>
        </nav>
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
                <div class="table-responsive-wrapper"> <!-- Added Wrapper for Horizontal Scrolling -->
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
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td data-label="Event Title"><?php echo htmlspecialchars($event['title']); ?></td>
                                    <td data-label="Date"><?php echo htmlspecialchars(date('M d, Y', strtotime($event['event_date']))); ?></td>
                                    <td data-label="Time"><?php echo htmlspecialchars(date('h:i A', strtotime($event['event_time']))); ?></td>
                                    <td data-label="Location"><?php echo htmlspecialchars($event['location']); ?></td>
                                    <td data-label="Category"><?php echo htmlspecialchars($event['category']); ?></td>
                                    <td data-label="Total Tickets"><?php echo htmlspecialchars($event['total_tickets']); ?></td>
                                    <td data-label="Tickets Booked"><?php echo htmlspecialchars($event['tickets_booked']); ?></td>
                                    <td data-label="Tickets Remaining">
                                        <?php 
                                        $tickets_remaining = $event['total_tickets'] - $event['tickets_booked'];
                                        echo htmlspecialchars($tickets_remaining);
                                        ?>
                                    </td>
                                    <td data-label="Status">
                                        <?php 
                                            $status_class = '';
                                            switch ($event['status']) {
                                                case 'pending': $status_class = 'status-pending'; break;
                                                case 'approved': $status_class = 'status-approved'; break;
                                                case 'rejected': $status_class = 'status-rejected'; break;
                                            }
                                        ?>
                                        <span class="<?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars(ucfirst($event['status'])); ?>
                                        </span>
                                    </td>
                                    <td data-label="Actions" class="actions">
                                        <!-- Edit Button (Blue Square) -->
                                        <a href="edit-event.php?event_id=<?php echo htmlspecialchars($event['id']); ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                        
                                        <!-- Delete Button (Red Rectangle) -->
                                        <form action="delete-event.php" method="post" onsubmit="return confirm('Are you sure you want to delete the event: \'<?php echo htmlspecialchars($event['title']); ?>\'? This action cannot be undone.');">
                                            <input type="hidden" name="event_id_to_delete" value="<?php echo htmlspecialchars($event['id']); ?>">
                                            <button type="submit" class="btn-delete">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div> <!-- End .table-responsive-wrapper -->
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