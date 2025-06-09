<?php
// organizer-dashboard/manage-events.php

// 1. Protect the page
require_once '../includes/session-organizer.php'; // Path to session-organizer.php
require_once '../includes/config.php';          // Path to config.php

$message = "";
$organizer_id = $_SESSION["user_id"]; // Get current organizer's ID from session

// --- 5. Delete Logic (at the top for processing before display) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_event_id'])) {
    $event_id_to_delete = trim($_POST['delete_event_id']);

    // Validate event ID
    if (!filter_var($event_id_to_delete, FILTER_VALIDATE_INT)) {
        $message = "<div class='error-msg'>Invalid event ID provided for deletion.</div>";
    } else {
        // Securely delete using prepared statements.
        // IMPORTANT: Verify that the event belongs to the current organizer BEFORE deleting.
        $sql = "DELETE FROM events WHERE id = ? AND organizer_id = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ii", $event_id_to_delete, $organizer_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $message = "<div class='success-msg'>Event deleted successfully.</div>";
                } else {
                    $message = "<div class='error-msg'>No event found with that ID under your account, or it has already been deleted.</div>";
                }
            } else {
                $message = "<div class='error-msg'>Error deleting event: " . $stmt->error . "</div>";
            }
            $stmt->close();
        } else {
            $message = "<div class='error-msg'>Database error: Could not prepare delete statement.</div>";
        }
    }
}

// --- 2. Fetch and display all events posted by the logged-in organizer ---
$events = [];
// Organizer ID is stored in $_SESSION['user_id']
$sql = "SELECT id, title, description, event_date, event_time, location, category, status FROM events WHERE organizer_id = ? ORDER BY event_date DESC, event_time DESC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $organizer_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
    } else {
        $message = "<div class='error-msg'>Error fetching events: " . $stmt->error . "</div>";
    }
    $stmt->close();
} else {
    $message = "<div class='error-msg'>Database error: Could not prepare fetch statement.</div>";
}

$conn->close(); // Close database connection after all operations
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 7. Set HTML title -->
    <title>Manage My Events - Mero Events</title>
    <!-- 6. Link external stylesheet -->
    <link rel="stylesheet" href="../assets/css/style.css"> 
    <style>
        /* 6. Basic styling for the event management page */
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
            flex-direction: column; /* Allow content to stack vertically */
            align-items: center; /* Center content horizontally */
        }

        .manage-events-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 960px; /* Wider container for table */
            margin: auto;
        }

        .manage-events-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 2.2em;
        }

        .action-links {
            text-align: right;
            margin-bottom: 20px;
        }

        .action-links .btn {
            margin-left: 10px;
        }

        /* 6. Table format */
        .events-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .events-table th, .events-table td {
            border: 1px solid #ddd;
            padding: 12px 15px;
            text-align: left;
            vertical-align: top; /* Align text to top in cells */
        }

        .events-table th {
            background-color: #f2f2f2;
            color: #333;
            font-weight: bold;
            white-space: nowrap; /* Prevent headers from wrapping too much */
        }

        .events-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .events-table tr:hover {
            background-color: #f1f1f1;
        }

        .events-table .actions {
            white-space: nowrap; /* Keep buttons on one line */
            text-align: center;
        }

        .events-table .actions .btn {
            padding: 8px 12px;
            margin: 0 5px;
            font-size: 0.9em;
        }

        /* Status colors */
        .status-pending { color: #f0ad4e; font-weight: bold; } /* Orange */
        .status-approved { color: #5cb85c; font-weight: bold; } /* Green */
        .status-rejected { color: #d9534f; font-weight: bold; } /* Red */

        /* Message styling */
        .message {
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }

        .success-msg {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error-msg {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .no-events-msg {
            text-align: center;
            padding: 30px;
            background-color: #e9f0f9;
            border-radius: 5px;
            color: #555;
            font-size: 1.1em;
            margin-top: 20px;
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
        
        .btn { /* Basic button style from style.css */
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
            margin-top: auto; /* Push footer to the bottom */
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
                <a href="../index.php" class="site-logo">Mero Events</a>
                <ul class="nav-links">
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="../events.php">Events</a></li>
                    <li><a href="../about.php">About</a></li>
                    <li><a href="../contact.php">Contact</a></li>
                    
                    <?php
                    // Dynamic links for logged-in organizer (reused from dashboard logic)
                    // Note: Since this page is protected, we are always logged in here as an organizer.
                    $dashboard_link = 'dashboard.php'; // Path to organizer dashboard from current location

                    echo '<li class="welcome-message">Welcome, ' . htmlspecialchars($_SESSION["user_name"]) . '</li>';
                    echo '<li><a href="' . htmlspecialchars($dashboard_link) . '" class="btn btn-primary">Dashboard</a></li>';
                    echo '<li><a href="../logout.php" class="btn btn-primary" style="background-color: #dc3545;">Logout</a></li>'; 
                    ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="manage-events-container">
            <h2>Manage Your Events</h2>
            
            <?php 
            // Display message if set
            if (!empty($message)) {
                echo $message;
            }
            ?>

            <div class="action-links">
                <a href="create-event.php" class="btn btn-secondary">Create New Event</a>
            </div>

            <?php if (!empty($events)): ?>
                <table class="events-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Location</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['title']); ?></td>
                                <td><?php echo htmlspecialchars($event['event_date']); ?></td>
                                <td><?php echo htmlspecialchars($event['event_time']); ?></td>
                                <td><?php echo htmlspecialchars($event['location']); ?></td>
                                <td><?php echo htmlspecialchars($event['category']); ?></td>
                                <td>
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
                                <td class="actions">
                                    <!-- 4. Edit button -->
                                    <a href="edit-event.php?id=<?php echo $event['id']; ?>" class="btn btn-primary">Edit</a>
                                    <!-- 5. Delete button inside a form -->
                                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this event? This action cannot be undone.');">
                                        <input type="hidden" name="delete_event_id" value="<?php echo $event['id']; ?>">
                                        <button type="submit" class="btn btn-primary" style="background-color: #dc3545;">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-events-msg">
                    <p>You haven't created any events yet. <a href="create-event.php">Create your first event!</a></p>
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