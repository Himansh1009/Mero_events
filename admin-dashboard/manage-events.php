<?php
// admin-dashboard/manage-events.php

// 1. Basic Setup: Enable full error reporting at the top
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Basic Setup: Use includes/session-admin.php for session protection
require_once '../includes/session-admin.php';
// 1. Basic Setup: Use includes/config.php for DB connection
require_once '../includes/config.php';

$message = ""; // To store success or error messages

// --- 4. Approve/Reject Actions: Handle POST request ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['approve_event_id']) || isset($_POST['reject_event_id']))) {
    $event_id = null;
    $new_status = null;

    if (isset($_POST['approve_event_id'])) {
        $event_id = trim($_POST['approve_event_id']);
        $new_status = 'approved';
    } elseif (isset($_POST['reject_event_id'])) {
        $event_id = trim($_POST['reject_event_id']);
        $new_status = 'rejected';
    }

    // Validate event ID
    if (!filter_var($event_id, FILTER_VALIDATE_INT)) {
        $message = "<div class='error-msg'>Invalid event ID provided for action.</div>";
    } else {
        // Update `status` in `events` table to either `approved` or `rejected`
        $sql = "UPDATE events SET status = ? WHERE id = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("si", $new_status, $event_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    // Display a success message
                    $message = "<div class='success-msg'>Event (ID: " . htmlspecialchars($event_id) . ") has been " . htmlspecialchars($new_status) . ".</div>";
                } else {
                    $message = "<div class='info-msg'>No changes made or event not found.</div>";
                }
            } else {
                // Display a success or error message
                $message = "<div class='error-msg'>Error updating event status: " . $stmt->error . "</div>";
            }
            $stmt->close();
        } else {
            $message = "<div class='error-msg'>Database error: Could not prepare update statement.</div>";
        }
    }
}

// --- 3. Fetch Data: Fetch all events from events table & Join with organizers table ---
$events = [];
$sql = "SELECT 
            e.id, 
            e.title, 
            e.description, 
            e.event_date, 
            e.event_time, 
            e.location, 
            e.category, 
            e.status,
            o.name AS organizer_name 
        FROM 
            events e
        JOIN 
            organizers o ON e.organizer_id = o.id
        ORDER BY 
        e.event_date DESC, e.event_time DESC"; // Order by creation date, newest first

if ($result = $conn->query($sql)) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
    } else {
        // 6. Edge Handling: Display "No events found" message if no events exist
        $message = "<div class='info-msg'>No events found in the database.</div>";
    }
    $result->free(); // Free result set
} else {
    $message = "<div class='error-msg'>Error retrieving events from database: " . $conn->error . "</div>";
}

$conn->close(); // Close database connection after all operations
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - Mero Events (Admin)</title>
    <!-- Link to your main CSS file for consistent styling -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Include Font Awesome for icons if used (e.g. for Edit button if it becomes an icon) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

        .admin-events-container {
            background-color: var(--white); /* White background */
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-color); /* Subtle shadow */
            width: 100%;
            max-width: 1100px; /* Max width to match general layout */
            margin: auto; /* Center the container */
            box-sizing: border-box; /* Include padding in width */
        }

        .admin-events-container h2 {
            text-align: center;
            color: var(--text-color);
            margin-bottom: 30px;
            font-size: 2.5em; /* Larger heading */
            font-weight: bold; /* Bold as per image */
        }

        /* --- Responsive Table Wrapper --- */
        .table-responsive-wrapper {
            width: 100%; /* Ensures it respects parent's width */
            overflow-x: auto; /* Enables horizontal scrolling if table content is too wide */
            -webkit-overflow-scrolling: touch; /* For smooth scrolling on iOS */
            border-radius: 8px; /* Match container rounding */
            box-shadow: 0 2px 5px rgba(0,0,0,0.03); /* Subtle shadow for the scrollable area */
            margin-top: 30px; /* Space from heading */
        }

        /* --- Table Styling (Matching Screenshot Precisely) --- */
        .events-admin-table {
            width: 100%; /* Ensure table takes full width of its wrapper */
            min-width: 900px; /* Set a minimum width to prevent columns from scrunching too much before scroll kicks in */
            border-collapse: separate; /* Allows border-radius and spacing on cells */
            border-spacing: 0; /* Remove default spacing */
            background-color: var(--white); /* Table background */
        }

        .events-admin-table th, .events-admin-table td {
            padding: 15px 20px; /* Generous padding as per screenshot */
            text-align: left;
            vertical-align: middle; /* Center vertically in cells */
            font-size: 0.95em;
            color: var(--text-color);
            border-bottom: 1px solid #eeeeee; /* Lighter separator for rows */
            white-space: nowrap; /* Keep content on single line as seen in screenshot */
        }

        .events-admin-table th {
            background-color: #f8f8f8; /* Light gray header background */
            color: var(--text-color);
            font-weight: bold;
            border-bottom: 1px solid var(--border-color); /* Separator line for header */
        }
        /* Specific rounding for table headers */
        .events-admin-table thead th:first-child { border-top-left-radius: 8px; }
        .events-admin-table thead th:last-child { border-top-right-radius: 8px; }

        .events-admin-table tbody tr:last-child td { /* No bottom border for last row */
            border-bottom: none;
        }

        .events-admin-table tr:hover {
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
        .events-admin-table .actions {
            white-space: nowrap;
            text-align: center;
            display: flex; /* Use flex to align buttons */
            gap: 8px; /* Space between buttons */
            justify-content: center; /* Center buttons within cell */
        }

        /* Approve/Reject Buttons */
        .events-admin-table .actions form {
            display: inline-flex; /* Use inline-flex for form to be next to others and center its button */
            align-items: center;
            justify-content: center;
            margin: 0; /* Remove default form margin */
        }
        .events-admin-table .actions button.btn-approve,
        .events-admin-table .actions button.btn-reject {
            padding: 8px 12px;
            font-size: 0.9em;
            font-weight: bold;
            border-radius: 8px; /* Rounded corners */
            cursor: pointer;
            border: none;
            color: var(--white);
            transition: background-color 0.2s ease;
        }
        .btn-approve {
            background-color: #28a745; /* Green */
        }
        .btn-approve:hover {
            background-color: #218838;
        }
        .btn-reject {
            background-color: #dc3545; /* Red */
        }
        .btn-reject:hover {
            background-color: #c82333;
        }

        /* New Delete Permanently Button (from snippet) */
        .events-admin-table .actions form button.btn-delete-permanent {
            background-color: #f44336; /* Red color as in snippet */
            color: var(--white);
            border: none;
            padding: 8px 18px; /* Larger padding to fit text */
            border-radius: 8px;
            font-size: 0.9em;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s ease;
            white-space: nowrap; /* Keep text on one line */
            margin-left: 10px; /* Space from other action buttons */
        }
        .events-admin-table .actions form button.btn-delete-permanent:hover {
            background-color: #da190b; /* Darker red on hover */
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
        .site-logo { font-size: 1.8em; font-weight: bold; color: var(--navbar-logo-color); margin-right: 20px; text-decoration: none; flex-shrink: 0; }
        .site-logo:hover { color: var(--navbar-logo-color); opacity: 0.9; }
        .nav-links { list-style: none; display: flex; align-items: center; margin: 0; padding: 0; gap: 25px; }
        .nav-links li { margin-left: 0; }
        .nav-links a { color: var(--navbar-link-color); font-weight: 500; padding: 5px 0; transition: color 0.2s ease; text-decoration: none; }
        .nav-links a:hover:not(.btn-navbar) { color: var(--navbar-logo-color); }
        .welcome-message { color: var(--navbar-link-color); font-weight: 500; margin-right: 15px; white-space: nowrap; }
        .btn-navbar { display: inline-block; padding: 8px 18px; border-radius: 8px; font-weight: bold; font-size: 0.95em; text-align: center; text-decoration: none; transition: background-color 0.2s ease, opacity 0.2s ease; color: var(--navbar-btn-text-color); border: none; }
        .btn-navbar.dashboard { background-color: var(--navbar-dashboard-btn-bg); margin-left: 10px; }
        .btn-navbar.logout { background-color: var(--navbar-logout-btn-bg); margin-left: 10px; }
        .btn-navbar:hover { opacity: 0.9; }

        .main-footer { 
            background-color: #2f3542; 
            color: #e0e0e0; 
            text-align: center; padding: 25px 0; font-size: 0.9em; margin-top: auto; width: 100%; 
        }
        .main-footer .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

        /* Responsive Adjustments for table */
        @media (max-width: 992px) {
            .admin-events-container { padding: 20px; max-width: 95%; }
            .events-admin-table { min-width: 700px; /* Ensure horizontal scroll kicks in if needed */ }
            .events-admin-table th, .events-admin-table td { padding: 10px 15px; font-size: 0.9em; }
            .events-admin-table .actions { gap: 5px; } 
            .events-admin-table .actions button { padding: 6px 10px; font-size: 0.8em; border-radius: 6px; }
            .events-admin-table .actions button.btn-approve, .events-admin-table .actions button.btn-reject { width: auto; } /* Let content define width */
            .events-admin-table .actions button.btn-delete-permanent { width: auto; margin-left: 5px; } /* Adjust margin */

            /* Navbar adjustments for smaller screens */
            .main-nav { flex-direction: column; gap: 10px; align-items: flex-start; padding: 0 15px; }
            .site-logo { margin-bottom: 5px; }
            .nav-links { flex-wrap: wrap; justify-content: flex-start; gap: 10px; width: 100%; }
            .nav-links li { margin-left: 0; }
            .welcome-message { margin-right: 0; width: 100%; text-align: center; }
            .btn-navbar.dashboard, .btn-navbar.logout { margin-left: 0; width: auto; flex-grow: 1; }
        }

        @media (max-width: 768px) {
            .admin-events-container h2 { font-size: 2em; }
            /* Mobile table stacking */
            .table-responsive-wrapper {
                /* No need for min-width here, let content dictate scroll */
                border-radius: 0; /* Remove rounding for full width on mobile */
                box-shadow: none; /* No shadow when stacked */
            }
            .events-admin-table { 
                min-width: unset; /* Remove min-width to allow full stacking */
                width: 100%;
            }
            .events-admin-table thead, .events-admin-table tbody, .events-admin-table th, .events-admin-table td, .events-admin-table tr { 
                display: block; 
            }
            .events-admin-table thead tr { 
                position: absolute; top: -9999px; left: -9999px; /* Hide original headers */
            } 
            .events-admin-table tr { 
                margin-bottom: 15px; border: 1px solid #eee; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); 
            }
            .events-admin-table td { 
                border: none; position: relative; padding-left: 50%; text-align: right; 
                white-space: normal; /* Allow content to wrap */
            }
            .events-admin-table td:before { /* Data labels for stacked view */
                content: attr(data-label);
                position: absolute;
                left: 10px; 
                width: 45%;
                white-space: nowrap;
                text-align: left;
                font-weight: bold;
                color: var(--text-color);
            }
            .events-admin-table .actions { 
                justify-content: flex-end; padding-top: 10px; border-top: 1px solid #eee; margin-top: 10px; gap: 8px;
            }
            .events-admin-table .actions button {
                width: auto; /* Allow buttons to size based on content */
                flex-grow: 1; /* Make them fill space if needed */
            }
            .events-admin-table .actions button.btn-delete-permanent {
                 margin-left: 0; /* Remove specific margin for better stacking */
            }
        }
        @media (max-width: 480px) {
            .admin-events-container { padding: 15px; }
            .events-admin-table th, .events-admin-table td { padding: 8px 10px; }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <nav class="main-nav">
            <a href="../index.php" class="site-logo">Mero Events (Admin)</a>
            <ul class="nav-links">
                <!-- Link to admin dashboard -->
                <li><a href="dashboard.php" class="btn-navbar dashboard">Back to Dashboard</a></li> 
                <li><a href="../admin-logout.php" class="btn-navbar logout">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="admin-events-container">
            <h2>Manage All Events</h2>
            
            <?php 
            // Display message if set
            if (!empty($message)) {
                echo $message;
            }
            ?>

            <?php if (!empty($events)): ?>
                <div class="table-responsive-wrapper"> <!-- Wrapper for Horizontal Scrolling -->
                    <table class="events-admin-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Date & Time</th>
                                <th>Location</th>
                                <th>Organizer Name</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td data-label="Title"><?php echo htmlspecialchars($event['title']); ?></td>
                                    <td data-label="Category"><?php echo htmlspecialchars($event['category']); ?></td>
                                    <td data-label="Date & Time">
                                        <?php echo htmlspecialchars(date('M d, Y', strtotime($event['event_date']))); ?><br>
                                        <?php echo htmlspecialchars(date('h:i A', strtotime($event['event_time']))); ?>
                                    </td>
                                    <td data-label="Location"><?php echo htmlspecialchars($event['location']); ?></td>
                                    <td data-label="Organizer Name"><?php echo htmlspecialchars($event['organizer_name']); ?></td>
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
                                        <?php if ($event['status'] == 'pending'): ?>
                                            <!-- Approve Button -->
                                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                                                <input type="hidden" name="approve_event_id" value="<?php echo $event['id']; ?>">
                                                <button type="submit" class="btn-approve">Approve</button>
                                            </form>
                                            <!-- Reject Button -->
                                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                                                <input type="hidden" name="reject_event_id" value="<?php echo $event['id']; ?>">
                                                <button type="submit" class="btn-reject">Reject</button>
                                            </form>
                                        <?php else: ?>
                                            <!-- If already approved/rejected, no action needed for approval/rejection -->
                                            <!-- You could add a "View Details" button here if desired -->
                                        <?php endif; ?>
                                        
                                        <!-- Admin Delete Permanently Button (Always visible for admin) -->
                                        <form action="delete-event.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete event \'<?php echo htmlspecialchars($event['title']); ?>\'? This will also delete ALL associated bookings!');">
                                            <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event['id']); ?>">
                                            <button type="submit" class="btn-delete-permanent">Delete Permanently</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div> <!-- End .table-responsive-wrapper -->
            <?php else: ?>
                <!-- This message is set in the PHP logic if $events is empty -->
                <?php 
                if (strpos($message, 'No events found') !== false) {
                    echo $message;
                } elseif (empty($message)) {
                    echo "<div class='info-msg'>No events found at the moment.</div>";
                }
                ?>
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