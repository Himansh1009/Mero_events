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
    <style>
        /* 5. Styling: Use clean CSS for styling (reusing and adding specific classes) */
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

        .admin-events-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 1200px; /* Wider for event table */
            margin: auto;
        }

        .admin-events-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 2.5em;
        }

        /* 5. Styling: Clean table layout with buttons */
        .events-admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }

        .events-admin-table th, .events-admin-table td {
            border: 1px solid #ddd;
            padding: 12px 15px;
            text-align: left;
            vertical-align: top;
        }

        .events-admin-table th {
            background-color: #f2f2f2;
            color: #333;
            font-weight: bold;
            white-space: nowrap;
        }

        .events-admin-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .events-admin-table tr:hover {
            background-color: #f1f1f1;
        }

        .events-admin-table .actions {
            white-space: nowrap; /* Keep buttons on one line */
            text-align: center;
        }
        .events-admin-table .actions form {
            display: inline-block;
            margin: 0 3px; /* Small space between buttons */
        }
        .events-admin-table .actions button {
            padding: 8px 12px;
            font-size: 0.9em;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            color: #fff;
            transition: background-color 0.3s ease;
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

        /* Status (Pending, Approved, Rejected) - colored labels */
        .status-pending { color: #f0ad4e; font-weight: bold; } /* Yellow/Orange */
        .status-approved { color: #5cb85c; font-weight: bold; } /* Green */
        .status-rejected { color: #d9534f; font-weight: bold; } /* Red */
        
        /* Message styling (reused from other pages) */
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

        .info-msg {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        /* Header/Footer (consistent with other pages) */
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
                <a href="../index.php" class="site-logo">Mero Events (Admin)</a>
                <ul class="nav-links">
                    <!-- Link to admin dashboard -->
                    <li><a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a></li> 
                    <li><a href="../admin-logout.php" class="btn btn-primary" style="background-color: #dc3545;">Logout</a></li>
                </ul>
            </nav>
        </div>
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
                                <td><?php echo htmlspecialchars($event['title']); ?></td>
                                <td><?php echo htmlspecialchars($event['category']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars(date('M d, Y', strtotime($event['event_date']))); ?><br>
                                    <?php echo htmlspecialchars(date('h:i A', strtotime($event['event_time']))); ?>
                                </td>
                                <td><?php echo htmlspecialchars($event['location']); ?></td>
                                <td><?php echo htmlspecialchars($event['organizer_name']); ?></td>
                                <td>
                                    <?php 
                                        // Colored status labels
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
                                    <?php if ($event['status'] == 'pending'): ?>
                                        <!-- 4. For events with status `pending`, show: "Approve" button & "Reject" button -->
                                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" style="display:inline-block;">
                                            <input type="hidden" name="approve_event_id" value="<?php echo $event['id']; ?>">
                                            <button type="submit" class="btn-approve">Approve</button>
                                        </form>
                                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" style="display:inline-block;">
                                            <input type="hidden" name="reject_event_id" value="<?php echo $event['id']; ?>">
                                            <button type="submit" class="btn-reject">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <!-- If already approved/rejected, no action needed -->
                                        No actions
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <!-- This message is set in the PHP logic if $events is empty -->
                <?php 
                // Only show this specific message if no events were found and no other error message is present
                // (This handles the case where $message might already contain an error from DB query)
                if (strpos($message, 'No events found') !== false) {
                    echo $message;
                } elseif (empty($message)) { // Fallback if $message is completely empty for some reason
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