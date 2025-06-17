<?php
// Use session protection to ensure only logged-in users can access this dashboard
require_once '../includes/session-user.php';
// Use includes/config.php for DB connection
require_once '../includes/config.php';

$message = ""; // For displaying feedback messages to the user
// Get logged-in user's user_id from session
$user_id = $_SESSION['user_id'];

$bookings = []; // Array to store fetched booking history

// Fetch bookings from ticket_bookings table for this user
// Join with events table to get event details
$sql = "SELECT 
            tb.num_tickets, 
            tb.booking_date,
            e.title AS event_title,
            e.event_date,
            e.event_time,
            e.location,
            e.category
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
            // If user has no bookings yet
            $message = "<div class='info-msg'>You have not booked any events yet.</div>";
        }
        $result->free(); // Free result set
    } else {
        // Include error handling if database queries fail
        $message = "<div class='error-msg'>Error retrieving booking history: " . $stmt->error . "</div>";
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
    <title>Booking History - Mero Events</title>
    <!-- Use simple clean Bootstrap or responsive CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Specific styling for booking-history.php */
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
            align-items: flex-start; /* Align content to the top */
        }

        .booking-history-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 1000px; /* Adjust width for table content */
            margin: auto;
        }

        .booking-history-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 2.5em;
        }

        /* Clean Bootstrap table format (mimicked with custom CSS) */
        .bookings-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }

        .bookings-table th, .bookings-table td {
            border: 1px solid #ddd;
            padding: 12px 15px;
            text-align: left;
            vertical-align: top;
        }

        .bookings-table th {
            background-color: #f2f2f2;
            color: #333;
            font-weight: bold;
            white-space: nowrap;
        }

        .bookings-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .bookings-table tr:hover {
            background-color: #f1f1f1;
        }

        /* Message styling (reused from other pages) */
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

        /* Header/Footer (consistent with other pages) */
        .main-header {
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 15px 0;
        }
        .main-nav { display: flex; justify-content: space-between; align-items: center; }
        .site-logo { font-size: 1.8em; font-weight: bold; color: #333; margin-right: 20px; text-decoration: none; }
        .site-logo:hover { color: #007bff; }
        .nav-links { list-style: none; display: flex; align-items: center; margin: 0; padding: 0; }
        .nav-links li { margin-left: 25px; }
        .nav-links a { color: #555; font-weight: 500; padding: 5px 0; transition: color 0.3s ease; text-decoration: none; }
        .nav-links a:not(.btn):hover { color: #007bff; }
        .welcome-message { color: #555; font-weight: 500; margin-right: 15px; white-space: nowrap; }
        .btn { display: inline-block; padding: 10px 20px; border-radius: 5px; font-weight: bold; text-align: center; transition: background-color 0.3s ease; text-decoration: none; }
        .btn-primary { background-color: #007bff; color: #fff; border: 1px solid #007bff; }
        .btn-primary:hover { background-color: #0056b3; border-color: #0056b3; }
        .main-footer { background-color: #333; color: #fff; text-align: center; padding: 25px 0; font-size: 0.9em; margin-top: auto; width: 100%; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
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
                    // Include navigation/header consistent with user-dashboard
                    // Since this page is protected, we are always logged in here as a user.
                    $dashboard_link = 'dashboard.php'; // Path to user dashboard from current location

                    echo '<li class="welcome-message">Welcome, ' . htmlspecialchars($_SESSION["user_name"]) . '</li>';
                    echo '<li><a href="' . htmlspecialchars($dashboard_link) . '" class="btn btn-primary">Dashboard</a></li>';
                    echo '<li><a href="../logout.php" class="btn btn-primary" style="background-color: #dc3545;">Logout</a></li>'; 
                    ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="booking-history-container">
            <h2>Your Booking History</h2>
            
            <?php 
            // Display message if set (e.g., no bookings found, or database error)
            if (!empty($message)) {
                echo "<div class='message'>" . $message . "</div>";
            }
            ?>

            <?php if (!empty($bookings)): ?>
                <table class="bookings-table">
                    <thead>
                        <tr>
                            <th>Event Title</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Location</th>
                            <th>Category</th>
                            <th>Tickets Booked</th>
                            <th>Booking Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['event_title']); ?></td>
                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($booking['event_date']))); ?></td>
                                <td><?php echo htmlspecialchars(date('h:i A', strtotime($booking['event_time']))); ?></td>
                                <td><?php echo htmlspecialchars($booking['location']); ?></td>
                                <td><?php echo htmlspecialchars($booking['category']); ?></td>
                                <td><?php echo htmlspecialchars($booking['num_tickets']); ?></td>
                                <td><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($booking['booking_date']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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