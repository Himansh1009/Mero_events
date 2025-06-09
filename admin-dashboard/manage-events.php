<?php
// admin-dashboard/manage-events.php

// Protect the page: Only accessible to logged-in admins.
require_once '../includes/session-admin.php';
// Connect to the database
require_once '../includes/config.php';

$message = ""; // To store success/error messages

// Check for messages from approve.php or reject.php redirects
if (isset($_GET['status_update'])) {
    if ($_GET['status_update'] == 'success') {
        $message = "<div class='success-msg'>Event status updated successfully!</div>";
    } elseif ($_GET['status_update'] == 'error') {
        $message = "<div class='error-msg'>Failed to update event status.</div>";
    } elseif ($_GET['status_update'] == 'invalid') {
        $message = "<div class='error-msg'>Invalid event ID or action.</div>";
    }
}


// Fetch all events from the events table
// Join with organizers table to get the organizer’s name
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
            e.created_at DESC"; // Order by creation date, newest first

if ($result = $conn->query($sql)) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
    } else {
        // Bonus: If there are no events, show a message
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
    <!-- External CSS: ../assets/css/style.css -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Admin dashboard specific styling */
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

        /* Table Styling */
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
        .events-admin-table .actions a {
            display: inline-block;
            padding: 8px 12px;
            font-size: 0.9em;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            color: #fff;
            text-decoration: none; /* Remove underline */
            transition: background-color 0.3s ease;
            margin: 0 3px; /* Small space between buttons */
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

        /* Status colors */
        .status-pending { color: #f0ad4e; font-weight: bold; } /* Yellow */
        .status-approved { color: #5cb85c; font-weight: bold; } /* Green */
        .status-rejected { color: #d9534f; font-weight: bold; } /* Red */
        
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
                                        <!-- Approve / Reject buttons -->
                                        <a href="approve.php?id=<?php echo $event['id']; ?>" class="btn-approve">Approve</a>
                                        <a href="reject.php?id=<?php echo $event['id']; ?>" class="btn-reject">Reject</a>
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
                <?php 
                // Message already handled in PHP block; just display it if there's no data
                if (!empty($message)) {
                    echo $message;
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