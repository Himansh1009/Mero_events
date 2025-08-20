<?php
// user-dashboard/my-bookings.php

// Enable full error reporting at the very top for debugging HTTP ERROR 500
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure user is logged in as a "user"
require_once '../includes/session-user.php';
// Use includes/config.php for database connection
require_once '../includes/config.php';

$message = ""; // For displaying feedback messages to the user
$user_id = $_SESSION['user_id']; // Get logged-in user's user_id from session

// Check for status messages from cancel-booking.php redirection
if (isset($_GET['cancel_status'])) {
    if ($_GET['cancel_status'] == 'success') {
        $message = "<div class='success-msg'>Booking canceled successfully!</div>";
    } elseif ($_GET['cancel_status'] == 'error') {
        $message = "<div class='error-msg'>Failed to cancel booking. An unexpected error occurred.</div>";
    } elseif ($_GET['cancel_status'] == 'error_specific' && isset($_SESSION['cancel_error_message'])) {
        $message = "<div class='error-msg'>Cancellation failed: " . htmlspecialchars($_SESSION['cancel_error_message']) . "</div>";
        unset($_SESSION['cancel_error_message']); // Clear the specific message
    } elseif ($_GET['cancel_status'] == 'invalid_id') {
        $message = "<div class='error-msg'>Invalid booking ID.</div>";
    }
}

$bookings = []; // Array to store fetched booking history

try {
    // Fetch bookings for the logged-in user, joining with events table
    $sql = "SELECT 
                tb.id AS booking_id, 
                tb.num_tickets,      /* Quantity of Tickets Booked */
                tb.booking_date,
                tb.status AS booking_status, /* Status (Booked, Cancelled, etc.) */
                e.title AS event_title,      /* Event Title */
                e.event_date,                /* Event Date */
                e.event_time,                /* Event Time (for checking upcoming) */
                e.location,                  /* Location */
                e.category                   /* Category (optional, but good for display) */
            FROM 
                ticket_bookings tb
            JOIN 
                events e ON tb.event_id = e.id
            WHERE 
                tb.user_id = ?
            ORDER BY 
                tb.booking_date DESC"; // Order by most recent booking

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $bookings[] = $row;
                }
            } else {
                $message = "<div class='info-msg'>You have not booked any events yet.</div>";
            }
            $result->free();
        } else {
            throw new Exception("Error executing query: " . $stmt->error);
        }
        $stmt->close();
    } else {
        throw new Exception("Database error preparing statement: " . $conn->error);
    }
} catch (Exception $e) {
    // Catch database or query preparation errors
    $message = "<div class='error-msg'>A server error occurred while loading bookings. Please try again.</div>";
    error_log("my-bookings.php DB Error for user " . $user_id . ": " . $e->getMessage());
}

$conn->close(); // Close database connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Mero Events</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Define Color Palette for consistency with project theme */
        :root {
            --background-color: #f1f2f6;
            --text-color: #2f3542;
            --white: #ffffff;
            --shadow-color: rgba(0,0,0,0.05);
            --border-color: #ddd;

            /* Navbar specific colors */
            --navbar-bg: #ffffff;
            --navbar-border: #f0f0f0; 
            --navbar-logo-color: #6A5ACD; 
            --navbar-link-color: #666666; 
            --navbar-dashboard-btn-bg: #4a90e2; 
            --navbar-logout-btn-bg: #e04444; 
            --navbar-btn-text-color: #ffffff; 

            /* Status badge colors */
            --status-booked-bg: #d1ecf1; /* Info blue */
            --status-booked-text: #0c5460;
            --status-cancelled-bg: #f8d7da; /* Danger red */
            --status-cancelled-text: #721c24;
            --status-completed-bg: #d4edda; /* Success green */
            --status-completed-text: #155724;
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
            align-items: flex-start;
        }

        .booking-history-container {
            background-color: var(--white);
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-color);
            width: 100%;
            max-width: 1100px;
            margin: auto;
            box-sizing: border-box;
        }

        .booking-history-container h2 {
            text-align: center;
            color: var(--text-color);
            margin-bottom: 30px;
            font-size: 2.5em;
            font-weight: bold;
        }

        /* --- Responsive Table Wrapper --- */
        .table-responsive-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
            margin-top: 30px;
        }

        /* --- Table Styling --- */
        .bookings-table {
            width: 100%;
            min-width: 900px; /* Adjusted min-width for all columns */
            border-collapse: separate;
            border-spacing: 0;
            background-color: var(--white);
        }

        .bookings-table th, .bookings-table td {
            padding: 15px 20px;
            text-align: left;
            vertical-align: middle;
            font-size: 0.95em;
            color: var(--text-color);
            border-bottom: 1px solid #eeeeee;
            white-space: nowrap;
        }

        .bookings-table th {
            background-color: #f8f8f8;
            color: var(--text-color);
            font-weight: bold;
            border-bottom: 1px solid var(--border-color);
        }
        .bookings-table thead th:first-child { border-top-left-radius: 8px; }
        .bookings-table thead th:last-child { border-top-right-radius: 8px; }

        .bookings-table tbody tr:last-child td { border-bottom: none; }

        .bookings-table tr:hover { background-color: #f9f9f9; }

        /* --- Status Badges --- */
        .status-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.85em;
            white-space: nowrap;
            display: inline-block;
        }
        .status-booked {
            background-color: var(--status-booked-bg);
            color: var(--status-booked-text);
        }
        .status-cancelled {
            background-color: var(--status-cancelled-bg);
            color: var(--status-cancelled-text);
        }
        .status-completed {
            background-color: var(--status-completed-bg);
            color: var(--status-completed-text);
        }

        /* --- Action Button --- */
        .bookings-table .actions {
            white-space: nowrap;
            text-align: center;
        }
        .bookings-table .actions form {
            display: inline-block;
            margin: 0;
        }
        .bookings-table .actions button {
            background-color: var(--navbar-logout-btn-bg); /* Red for cancel */
            color: var(--white);
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 0.9em;
            font-weight: bold;
            cursor: pointer;
            border: none;
            transition: background-color 0.2s ease;
        }
        .bookings-table .actions button:hover {
            background-color: #cc3939; /* Darker red */
        }
        .bookings-table .actions button:disabled {
            background-color: #cccccc; /* Gray out disabled buttons */
            cursor: not-allowed;
            opacity: 0.7;
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

        /* --- Header/Footer (Consistent with site-wide theme) --- */
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
        .nav-links a { color: var(--navbar-link-color); font-weight: 500; padding: 5px 0; text-decoration: none; transition: color 0.2s ease; }
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

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .booking-history-container { padding: 20px; max-width: 95%; }
            .bookings-table { min-width: 700px; }
            .bookings-table th, .bookings-table td { padding: 10px 15px; font-size: 0.9em; }
            /* Navbar adjustments */
            .main-nav { flex-direction: column; gap: 10px; align-items: flex-start; padding: 0 15px; }
            .site-logo { margin-bottom: 5px; }
            .nav-links { flex-wrap: wrap; justify-content: flex-start; gap: 10px; width: 100%; }
            .nav-links li { margin-left: 0; }
            .welcome-message { margin-right: 0; width: 100%; text-align: center; }
            .btn-navbar.dashboard, .btn-navbar.logout { margin-left: 0; width: auto; flex-grow: 1; }
        }

        @media (max-width: 768px) {
            .booking-history-container h2 { font-size: 2em; }
            .table-responsive-wrapper { border-radius: 0; box-shadow: none; }
            .bookings-table { min-width: unset; }
            .bookings-table thead, .bookings-table tbody, .bookings-table th, .bookings-table td, .bookings-table tr { display: block; }
            .bookings-table thead tr { position: absolute; top: -9999px; left: -9999px; }
            .bookings-table tr { margin-bottom: 15px; border: 1px solid #eee; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
            .bookings-table td { border: none; position: relative; padding-left: 50%; text-align: right; white-space: normal; }
            .bookings-table td:before {
                content: attr(data-label);
                position: absolute; left: 10px; width: 45%; white-space: nowrap; text-align: left; font-weight: bold; color: var(--text-color);
            }
            .bookings-table .actions { justify-content: flex-end; padding-top: 10px; border-top: 1px solid #eee; margin-top: 10px; gap: 8px;}
        }
        @media (max-width: 480px) {
            .booking-history-container { padding: 15px; }
            .bookings-table th, .bookings-table td { padding: 8px 10px; }
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
                // Include navigation/header consistent with user-dashboard
                $dashboard_link = 'dashboard.php'; 

                echo '<li class="welcome-message">Welcome, ' . htmlspecialchars($_SESSION["user_name"]) . '</li>';
                echo '<li><a href="' . htmlspecialchars($dashboard_link) . '" class="btn-navbar dashboard">Dashboard</a></li>';
                echo '<li><a href="../logout.php" class="btn-navbar logout">Logout</a></li>'; 
                ?>
            </ul>
        </nav>
    </header>

    <main>
        <div class="booking-history-container">
            <h2>My Bookings</h2>
            <?php
            // Display any status/info/error messages
            if (!empty($message)) {
                echo $message;
            }
            // Show bookings table if bookings exist
            if (!empty($bookings)) {
                echo '<div class="table-responsive-wrapper">';
                echo '<table class="bookings-table">';
                echo '<thead><tr><th>Event</th><th>Date</th><th>Location</th><th>Tickets</th><th>Status</th><th>Actions</th></tr></thead>';
                echo '<tbody>';
                foreach ($bookings as $booking) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($booking['event_title']) . '</td>';
                    echo '<td>' . htmlspecialchars($booking['event_date']) . '</td>';
                    echo '<td>' . htmlspecialchars($booking['location']) . '</td>';
                    echo '<td>' . htmlspecialchars($booking['num_tickets']) . '</td>';
                    // Show booking status
                    $status_class = 'status-booked';
                    if ($booking['booking_status'] === 'Cancelled') $status_class = 'status-cancelled';
                    elseif ($booking['booking_status'] === 'Completed') $status_class = 'status-completed';
                    echo '<td><span class="status-badge ' . $status_class . '">' . htmlspecialchars($booking['booking_status']) . '</span></td>';
                    // Action: Cancel only if not already cancelled
                    echo '<td class="actions">';
                    if ($booking['booking_status'] === 'Booked') {
                        echo '<form method="POST" action="cancel-booking.php">';
                        echo '<input type="hidden" name="booking_id" value="' . (int)$booking['booking_id'] . '">';
                        echo '<button type="submit">Cancel</button>';
                        echo '</form>';
                    } else {
                        echo '&mdash;';
                    }
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table></div>';
            }
            ?>
        </div>
    </main>

    <footer class="main-footer">
        <div class="container">
            <p>© 2023 Mero Events. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>