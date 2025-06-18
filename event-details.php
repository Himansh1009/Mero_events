<?php
session_start(); // Start the session to access $_SESSION variables

// Use includes/config.php for DB connection.
require_once 'includes/config.php';

$event = null; // Variable to store event details
$message = ""; // For displaying feedback messages to the user
$event_id_to_fetch = null; // To store event_id from GET or POST for consistent fetching

// Check if user is logged in as a 'user'
$is_logged_in_user = (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'user');
$user_id = $is_logged_in_user ? $_SESSION['user_id'] : null;

$has_booked_already = false; // Flag to check if the current user has already booked this event

// --- Handle Ticket Booking Form Submission (POST request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_tickets_submit'])) {
    $booking_event_id = trim($_POST['event_id']);
    $num_tickets_requested = trim($_POST['num_tickets']);

    // Keep the event_id for re-fetching and sticky form fields after POST
    $event_id_to_fetch = $booking_event_id;

    // Validate initial inputs
    if (empty($booking_event_id) || !is_numeric($booking_event_id) || empty($num_tickets_requested) || !is_numeric($num_tickets_requested) || $num_tickets_requested <= 0) {
        $message = "<div class='error-msg'>Invalid booking request. Please specify a positive number of tickets.</div>";
    } elseif (!$is_logged_in_user) {
        // 1. If not logged in, show message
        $message = "<div class='error-msg'>Please login to book tickets.</div>";
    } else {
        // Start a transaction for atomic operations (either all succeed or all fail)
        $conn->begin_transaction();

        try {
            // 2. Prevent duplicate booking (user should only book once per event).
            // Check ticket_bookings table if user_id and event_id already exist.
            $sql_check_duplicate = "SELECT id FROM ticket_bookings WHERE user_id = ? AND event_id = ? FOR UPDATE"; // FOR UPDATE for transactional integrity
            if ($stmt_duplicate = $conn->prepare($sql_check_duplicate)) {
                $stmt_duplicate->bind_param("ii", $user_id, $booking_event_id);
                $stmt_duplicate->execute();
                $stmt_duplicate->store_result();
                if ($stmt_duplicate->num_rows > 0) {
                    // If already booked, show: "You have already booked this event."
                    throw new Exception("You have already booked this event.");
                }
                $stmt_duplicate->close();
            } else {
                throw new Exception("Database error preparing duplicate booking check: " . $conn->error);
            }

            // Fetch current ticket availability and lock the event row
            $sql_check_tickets = "SELECT total_tickets, tickets_booked FROM events WHERE id = ? AND status = 'approved' FOR UPDATE"; 
            if ($stmt_check = $conn->prepare($sql_check_tickets)) {
                $stmt_check->bind_param("i", $booking_event_id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                
                if ($result_check->num_rows == 0) {
                    throw new Exception("Event not found or is not approved.");
                }
                
                $row_tickets = $result_check->fetch_assoc();
                $current_tickets_booked = $row_tickets['tickets_booked'];
                $total_tickets_available = $row_tickets['total_tickets'];
                $remaining_tickets = $total_tickets_available - $current_tickets_booked;

                // Validate that requested tickets are within availability
                if ($num_tickets_requested > $remaining_tickets) {
                    throw new Exception("Not enough tickets available. Only " . $remaining_tickets . " ticket(s) remaining.");
                }
                $stmt_check->close();

                // Insert booking into ticket_bookings table
                // Columns: user_id, event_id, tickets_booked (storing quantity for THIS booking)
                $sql_insert_booking = "INSERT INTO ticket_bookings (user_id, event_id, tickets_booked) VALUES (?, ?, ?)";
                if ($stmt_insert = $conn->prepare($sql_insert_booking)) {
                    $stmt_insert->bind_param("iii", $user_id, $booking_event_id, $num_tickets_requested);
                    if (!$stmt_insert->execute()) {
                        throw new Exception("Failed to record booking: " . $stmt_insert->error);
                    }
                    $stmt_insert->close();
                } else {
                    throw new Exception("Database error preparing booking insert: " . $conn->error);
                }

                // Update tickets_booked count in events table accordingly.
                $sql_update_event = "UPDATE events SET tickets_booked = tickets_booked + ? WHERE id = ?";
                if ($stmt_update = $conn->prepare($sql_update_event)) {
                    $stmt_update->bind_param("ii", $num_tickets_requested, $booking_event_id);
                    if (!$stmt_update->execute()) {
                        throw new Exception("Failed to update event ticket count: " . $stmt_update->error);
                    }
                    $stmt_update->close();
                } else {
                    throw new Exception("Database error preparing event update: " . $conn->error);
                }

                $conn->commit(); // Commit the transaction
                $message = "<div class='success-msg'>Successfully booked " . htmlspecialchars($num_tickets_requested) . " ticket(s)!</div>";
                
                // Set the flag as user has just successfully booked
                $has_booked_already = true; 
                // Event data will be re-fetched by the GET logic below to show updated state.

            } else {
                throw new Exception("Database error checking tickets availability: " . $conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback(); // Rollback on error
            $message = "<div class='error-msg'>" . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}


// --- Event Fetch Logic (GET request for initial load OR after POST) ---
// Get event_id via GET parameter
if (isset($_GET['event_id']) && is_numeric($_GET['event_id'])) {
    $event_id_to_fetch = $_GET['event_id']; // Use the GET ID if no POST
}

if ($event_id_to_fetch !== null) {
    // Fetch full event details from events table, joining organizers table to get organizer name.
    // Only show event if status = ‘approved’.
    $sql = "SELECT 
                e.id, 
                e.title, 
                e.description, 
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
                e.id = ? AND e.status = 'approved'"; 

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $event_id_to_fetch);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $event = $result->fetch_assoc();

                // After fetching the event, if user is logged in, check for existing booking for DISPLAY
                if ($is_logged_in_user && !$has_booked_already) { // Only re-check if not already set to true by POST
                    $sql_check_existing_booking = "SELECT id FROM ticket_bookings WHERE user_id = ? AND event_id = ?";
                    if ($stmt_existing_booking = $conn->prepare($sql_check_existing_booking)) {
                        $stmt_existing_booking->bind_param("ii", $user_id, $event['id']);
                        $stmt_existing_booking->execute();
                        $stmt_existing_booking->store_result();
                        if ($stmt_existing_booking->num_rows > 0) {
                            $has_booked_already = true;
                        }
                        $stmt_existing_booking->close();
                    } else {
                        // This error might not block page load but log it
                        error_log("Database error checking existing booking: " . $conn->error);
                    }
                }

            } else {
                $message = "<div class='error-msg'>Event not found or is not approved.</div>";
            }
        } else {
            $message = "<div class='error-msg'>Error retrieving event details: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='error-msg'>Database error: Could not prepare statement.</div>";
    }
} else {
    $message = "<div class='error-msg'>No event ID provided or invalid ID.</div>";
}

$conn->close(); // Close the database connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details - Mero Events</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Use simple clean Bootstrap or responsive CSS. */
        /* Specific styling for event-details.php */
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

        .event-details-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 900px;
            margin: 40px auto;
            text-align: left;
        }

        .event-details-container h1 {
            font-size: 2.8em;
            color: #2c3e50;
            margin-bottom: 15px;
            line-height: 1.3;
        }

        .event-details-container .organizer-name {
            font-size: 1.1em;
            color: #666;
            margin-bottom: 25px;
            display: block; /* Make organizer name display on its own line */
        }
        .event-details-container .organizer-name strong {
            color: #007bff;
        }

        .event-details-container p {
            font-size: 1.1em;
            line-height: 1.8;
            color: #555;
            margin-bottom: 15px;
        }
        .event-details-container p strong {
            color: #333;
        }

        .event-details-container .meta-info {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
            padding: 15px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }
        .event-details-container .meta-item {
            flex: 1 1 auto; /* Allow items to grow/shrink */
            min-width: 200px; /* Minimum width for each meta item */
        }
        .event-details-container .meta-item strong {
            display: block;
            margin-bottom: 5px;
            color: #007bff;
            font-size: 0.9em;
            text-transform: uppercase;
        }
        .event-details-container .meta-item span {
            font-size: 1.1em;
            color: #333;
            display: block;
        }

        .description-full {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .description-full h2 {
            font-size: 1.8em;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        /* Ticket Booking Section */
        .booking-section {
            background-color: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 30px;
            margin-top: 40px;
            text-align: center;
        }
        .booking-section h3 {
            font-size: 1.6em;
            color: #007bff;
            margin-bottom: 20px;
        }
        .booking-section .tickets-remaining {
            font-size: 1.2em;
            font-weight: bold;
            color: #28a745; /* Green for available */
            margin-bottom: 20px;
        }
        .booking-section .tickets-sold-out {
            font-size: 1.2em;
            font-weight: bold;
            color: #dc3545; /* Red for sold out */
            margin-bottom: 20px;
        }
        .booking-section .form-group {
            max-width: 300px;
            margin: 0 auto 20px auto;
            text-align: left;
        }
        .booking-section input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box;
        }
        .booking-section button {
            background-color: #28a745;
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .booking-section button:hover {
            background-color: #218838;
        }
        .login-to-book, .already-booked-msg {
            font-size: 1.1em;
            color: #555;
            margin-top: 20px;
        }
        .login-to-book a {
            color: #007bff;
            font-weight: bold;
        }

        /* Message styling (reused from other pages) */
        .message {
            margin: 20px auto;
            padding: 12px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            max-width: 600px;
        }
        .success-msg { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-msg { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info-msg { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

        /* Header/Footer styles (consistent with other pages) */
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
            <?php 
            // Display general messages (e.g., event not found, database error, or booking success/error)
            if (!empty($message)) {
                echo "<div class='message'>" . $message . "</div>";
            }
            ?>

            <?php if ($event): // Only display event details if an event was successfully fetched ?>
                <div class="event-details-container">
                    <h1><?php echo htmlspecialchars($event['title']); ?></h1>
                    <span class="organizer-name">Organized by: <strong><?php echo htmlspecialchars($event['organizer_name']); ?></strong></span>

                    <div class="meta-info">
                        <div class="meta-item">
                            <strong>Date</strong>
                            <span><?php echo htmlspecialchars(date('M d, Y', strtotime($event['event_date']))); ?></span>
                        </div>
                        <div class="meta-item">
                            <strong>Time</strong>
                            <span><?php echo htmlspecialchars(date('h:i A', strtotime($event['event_time']))); ?></span>
                        </div>
                        <div class="meta-item">
                            <strong>Location</strong>
                            <span><?php echo htmlspecialchars($event['location']); ?></span>
                        </div>
                        <div class="meta-item">
                            <strong>Category</strong>
                            <span><?php echo htmlspecialchars($event['category']); ?></span>
                        </div>
                        <div class="meta-item">
                            <strong>Total Tickets</strong>
                            <span><?php echo htmlspecialchars($event['total_tickets']); ?></span>
                        </div>
                        <div class="meta-item">
                            <strong>Tickets Remaining</strong>
                            <span>
                                <?php 
                                $tickets_remaining = $event['total_tickets'] - $event['tickets_booked'];
                                echo htmlspecialchars($tickets_remaining);
                                ?>
                            </span>
                        </div>
                    </div>

                    <div class="description-full">
                        <h2>Event Description</h2>
                        <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                    </div>

                    <div class="booking-section">
                        <h3>Book Your Tickets</h3>
                        <?php if ($tickets_remaining > 0): // Show booking form if tickets are available ?>
                            <p class="tickets-remaining"><?php echo htmlspecialchars($tickets_remaining); ?> tickets remaining!</p>
                            
                            <?php if ($is_logged_in_user): // If user is logged in ?>
                                <?php if ($has_booked_already): // Check if user has already booked ?>
                                    <p class="already-booked-msg">You have already booked this event.</p>
                                <?php else: // User is logged in and hasn't booked yet ?>
                                    <form action="" method="post">
                                        <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event['id']); ?>">
                                        <div class="form-group">
                                            <label for="num_tickets">Number of Tickets:</label>
                                            <input type="number" id="num_tickets" name="num_tickets" min="1" max="<?php echo htmlspecialchars($tickets_remaining); ?>" value="1" required>
                                        </div>
                                        <button type="submit" name="book_tickets_submit">Book Now</button>
                                    </form>
                                <?php endif; ?>
                            <?php else: // If not logged in, show message ?>
                                <p class="login-to-book">Please <a href="auth.php?action=login">log in</a> as a user to book tickets.</p>
                            <?php endif; ?>

                        <?php else: // tickets_remaining is 0 or less ?>
                            <!-- If tickets sold out: Show "Sold Out" message and hide booking form. -->
                            <p class="tickets-sold-out">❗ This event is sold out.</p>
                        <?php endif; ?>
                    </div>
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