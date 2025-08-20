<?php
// admin-dashboard/event-details-admin.php

// Enable full error reporting at the top for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Security: Ensure admin session check is in place.
require_once '../includes/session-admin.php';
require_once '../includes/config.php'; // DB connection

$event_data = null; // To store fetched event details
$message = ""; // For displaying feedback messages

// Get event ID from URL parameter
$event_id = $_GET['id'] ?? null;

if (!filter_var($event_id, FILTER_VALIDATE_INT)) {
    $message = "<div class='error-msg'>Invalid Event ID provided.</div>";
} else {
    // Fetch event details
    // Query: events (title, description, date, time, location, category)
    // Join with organizers (name, email)
    // Join with ticket_bookings (to calculate total_tickets_booked)
    // Using SUM(tb.num_tickets) to get total tickets booked for this event
    $sql = "SELECT 
                e.id,
                e.title,
                e.description,
                e.event_date,
                e.event_time,
                e.location, /* Assuming this stores 'City, Province' string */
                e.category,
                e.total_tickets,
                e.tickets_booked,
                e.image_path,
                o.name AS organizer_name,
                o.email AS organizer_email,
                (SELECT SUM(tb.num_tickets) FROM ticket_bookings tb WHERE tb.event_id = e.id AND tb.status != 'cancelled') AS total_tickets_booked_actual /* Calculate actual booked tickets */
            FROM 
                events e
            JOIN 
                organizers o ON e.organizer_id = o.id
            WHERE 
                e.id = ?";

    try {
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $event_id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows == 1) {
                    $event_data = $result->fetch_assoc();
                    // If total_tickets_booked_actual is NULL (no bookings), set to 0
                    $event_data['total_tickets_booked_actual'] = $event_data['total_tickets_booked_actual'] ?? 0;

                } else {
                    $message = "<div class='error-msg'>Event not found.</div>";
                }
            } else {
                throw new Exception("Error executing query: " . $stmt->error);
            }
            $stmt->close();
        } else {
            throw new Exception("Database error preparing statement: " . $conn->error);
        }
    } catch (Exception $e) {
        $message = "<div class='error-msg'>A server error occurred while loading event details.</div>";
        error_log("admin-event-details.php DB Error (ID: " . $event_id . "): " . $e->getMessage());
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details (Admin) - Mero Events</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Shared admin dashboard styling base */
        :root {
            --background-color: #f1f2f6; --text-color: #2f3542; --white: #ffffff;
            --shadow-color: rgba(0,0,0,0.05); --border-color: #ddd;
            --navbar-bg: #ffffff; --navbar-border: #f0f0f0; --navbar-logo-color: #6A5ACD;
            --navbar-link-color: #666666; --navbar-dashboard-btn-bg: #4a90e2;
            --navbar-logout-btn-bg: #e04444; --navbar-btn-text-color: #ffffff;
            --primary-color: #ff6b6b; /* For general site consistency */
            --secondary-color: #1dd1a1;
        }
        body { font-family: Arial, sans-serif; background-color: var(--background-color); color: var(--text-color); margin: 0; display: flex; flex-direction: column; min-height: 100vh; }
        main { flex-grow: 1; padding: 40px 20px; display: flex; justify-content: center; align-items: flex-start; }
        
        .event-details-admin-container {
            background-color: var(--white);
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-color);
            width: 100%;
            max-width: 900px;
            margin: auto;
            box-sizing: border-box;
        }
        .event-details-admin-container h2 {
            text-align: center;
            color: var(--text-color);
            margin-bottom: 30px;
            font-size: 2.5em;
            font-weight: bold;
        }

        .event-details-card {
            background-color: #f8f8f8;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .event-details-card h3 {
            font-size: 2em;
            color: var(--primary-color);
            margin-top: 0;
            margin-bottom: 15px;
        }

        .event-details-card p {
            font-size: 1.1em;
            line-height: 1.6;
            margin-bottom: 10px;
            color: var(--light-text-color);
        }
        .event-details-card p strong {
            color: var(--text-color);
            margin-right: 5px;
        }

        .event-details-card .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        .event-details-card .meta-item {
            font-size: 1em;
            color: var(--light-text-color);
        }
        .event-details-card .meta-item strong {
            display: block;
            font-size: 0.9em;
            color: var(--navbar-dashboard-btn-bg);
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .event-details-card .description-block {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        .event-details-card .description-block h4 {
            font-size: 1.5em;
            color: var(--text-color);
            margin-bottom: 15px;
        }
        .event-details-card .description-block p {
            font-size: 1em;
            color: var(--light-text-color);
        }
        
        /* Event Image */
        .event-details-image {
            width: 100%;
            max-height: 350px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }


        /* Back button */
        .back-button {
            display: inline-block;
            background-color: var(--navbar-dashboard-btn-bg);
            color: var(--white);
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 30px;
            font-weight: bold;
            transition: background-color 0.2s ease;
        }
        .back-button:hover {
            background-color: #357bd8;
        }

        /* Message styling */
        .message { margin-bottom: 20px; padding: 12px; border-radius: 5px; text-align: center; font-weight: bold; }
        .info-msg { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .error-msg { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Header/Footer (Consistent with site-wide theme) */
        .main-header { background-color: var(--navbar-bg); box-shadow: 0 2px 5px var(--shadow-color); border-bottom: 1px solid var(--navbar-border); padding: 15px 0; }
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

        .main-footer { background-color: #2f3542; color: #e0e0e0; text-align: center; padding: 25px 0; font-size: 0.9em; margin-top: auto; width: 100%; }
        .main-footer .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .event-details-admin-container { padding: 20px; max-width: 95%; }
            .event-details-admin-container h2 { font-size: 2em; }
            .event-details-card { padding: 20px; }
            .event-details-card h3 { font-size: 1.6em; }
            .event-details-card p { font-size: 1em; }
            .event-details-card .meta-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .event-details-admin-container { padding: 15px; }
            .event-details-admin-container h2 { font-size: 1.8em; }
            .event-details-card { padding: 15px; }
            .event-details-card h3 { font-size: 1.4em; }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <nav class="main-nav">
            <a href="../index.php" class="site-logo">Mero Events (Admin)</a>
            <ul class="nav-links">
                <li><a href="dashboard.php" class="btn-navbar dashboard">Back to Dashboard</a></li> 
                <li><a href="manage-events.php" class="btn-navbar dashboard">Manage Events</a></li>
                <li><a href="../admin-logout.php" class="btn-navbar logout">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="event-details-admin-container">
            <h2>Event Details (Admin View)</h2>
            
            <?php 
            if (!empty($message)) {
                echo "<div class='message'>" . $message . "</div>";
            }
            ?>

            <?php if ($event_data): ?>
                <div class="event-details-card">
                    <?php 
                        // Display Event Image if available
                        $event_image_src = !empty($event_data['image_path']) ? htmlspecialchars($event_data['image_path']) : 'event-images/default.jpg';
                    ?>
                    <img src="../<?php echo $event_image_src; ?>" alt="<?php echo htmlspecialchars($event_data['title']); ?>" class="event-details-image">

                    <h3><?php echo htmlspecialchars($event_data['title']); ?></h3>
                    <p><strong>Organizer:</strong> <?php echo htmlspecialchars($event_data['organizer_name']); ?> (<?php echo htmlspecialchars($event_data['organizer_email']); ?>)</p>
                    
                    <div class="meta-grid">
                        <div class="meta-item">
                            <strong>Date</strong>
                            <span><?php echo htmlspecialchars(date('M d, Y', strtotime($event_data['event_date']))); ?></span>
                        </div>
                        <div class="meta-item">
                            <strong>Time</strong>
                            <span><?php echo htmlspecialchars(date('h:i A', strtotime($event_data['event_time']))); ?></span>
                        </div>
                        <div class="meta-item">
                            <strong>Location</strong>
                            <span><?php echo htmlspecialchars($event_data['location']); ?></span>
                        </div>
                        <div class="meta-item">
                            <strong>Category</strong>
                            <span><?php echo htmlspecialchars($event_data['category']); ?></span>
                        </div>
                        <div class="meta-item">
                            <strong>Total Tickets Available</strong>
                            <span><?php echo htmlspecialchars($event_data['total_tickets']); ?></span>
                        </div>
                        <div class="meta-item">
                            <strong>Total Tickets Booked</strong>
                            <span><?php echo htmlspecialchars($event_data['total_tickets_booked_actual']); ?></span>
                        </div>
                    </div>

                    <div class="description-block">
                        <h4>Description</h4>
                        <p><?php echo nl2br(htmlspecialchars($event_data['description'])); ?></p>
                    </div>

                    <a href="manage-events.php" class="back-button">Back to Manage Events</a>
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