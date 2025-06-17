<?php
// admin-dashboard/approve-organizers.php

// 2. Use includes/session-admin.php for session protection
require_once '../includes/session-admin.php';
// 1. Use includes/config.php for DB connection
require_once '../includes/config.php';

$message = ""; // For displaying success or error messages

// --- 6. On clicking approve: Use POST, Update `is_approved` to 1 ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['approve_organizer_id'])) {
    $organizer_id_to_approve = trim($_POST['approve_organizer_id']);

    // Validate organizer ID
    if (!filter_var($organizer_id_to_approve, FILTER_VALIDATE_INT)) {
        $message = "<div class='error-msg'>Invalid organizer ID provided.</div>";
    } else {
        // Update that organizer’s is_approved to 1 using prepared statements
        $sql = "UPDATE organizers SET is_approved = 1 WHERE id = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $organizer_id_to_approve);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    // Show a success message
                    $message = "<div class='success-msg'>Organizer (ID: " . htmlspecialchars($organizer_id_to_approve) . ") approved successfully!</div>";
                } else {
                    $message = "<div class='info-msg'>Organizer not found or already approved.</div>";
                }
            } else {
                $message = "<div class='error-msg'>Error approving organizer: " . $stmt->error . "</div>";
            }
            $stmt->close();
        } else {
            $message = "<div class='error-msg'>Database error: Could not prepare update statement.</div>";
        }
    }
}

// --- 3. Fetch all organizers where is_approved = 0 ---
$pending_organizers = [];
// Assuming 'created_at' for ordering, if not present, remove from ORDER BY
$sql = "SELECT id, name, email, id_proof FROM organizers WHERE is_approved = 0 ORDER BY created_at ASC"; 

if ($result = $conn->query($sql)) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $pending_organizers[] = $row;
        }
    } else {
        // 7. Display "No pending organizers" message if list is empty
        $message = "<div class='info-msg'>No pending organizers found for approval.</div>";
    }
    $result->free(); // Free result set
} else {
    $message = "<div class='error-msg'>Error retrieving pending organizers: " . $conn->error . "</div>";
}

$conn->close(); // Close database connection after all operations
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Organizers - Mero Events (Admin)</title>
    <!-- Link to your main CSS file for consistent styling -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* 8. Use simple CSS for clean design */
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

        .admin-approval-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 1000px; /* Wider for table content */
            margin: auto;
        }

        .admin-approval-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 2.5em;
        }

        /* Clean HTML table layout */
        .organizers-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }

        .organizers-table th, .organizers-table td {
            border: 1px solid #ddd;
            padding: 12px 15px;
            text-align: left;
            vertical-align: top;
        }

        .organizers-table th {
            background-color: #f2f2f2;
            color: #333;
            font-weight: bold;
            white-space: nowrap;
        }

        .organizers-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .organizers-table tr:hover {
            background-color: #f1f1f1;
        }

        .organizers-table .actions {
            white-space: nowrap; /* Keep buttons on one line */
            text-align: center;
        }
        .organizers-table .actions form {
            display: inline-block;
            margin: 0; /* Remove default form margin */
        }
        .organizers-table .actions button {
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

        /* ID Proof link styling */
        .id-proof-link {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        .id-proof-link:hover {
            text-decoration: underline;
        }
        
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
        <div class="admin-approval-container">
            <h2>Approve Organizers</h2>
            
            <?php 
            // Display message if set
            if (!empty($message)) {
                echo $message;
            }
            ?>

            <?php if (!empty($pending_organizers)): ?>
                <table class="organizers-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>ID Proof</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_organizers as $organizer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($organizer['name']); ?></td>
                                <td><?php echo htmlspecialchars($organizer['email']); ?></td>
                                <td>
                                    <?php if (!empty($organizer['id_proof'])): ?>
                                        <!-- 4. Display ID proof file as clickable link -->
                                        <a href="../<?php echo htmlspecialchars($organizer['id_proof']); ?>" target="_blank" class="id-proof-link">View Proof</a>
                                    <?php else: ?>
                                        No proof provided
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <!-- 5. Have an "Approve" button beside each organizer -->
                                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" onsubmit="return confirm('Are you sure you want to approve <?php echo htmlspecialchars($organizer['name']); ?>?');">
                                        <input type="hidden" name="approve_organizer_id" value="<?php echo $organizer['id']; ?>">
                                        <button type="submit" class="btn-approve">Approve</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <!-- This message is set in the PHP logic if $pending_organizers is empty -->
                <?php 
                // Only show this specific message if no organizers were found and no other error message is present
                // (This handles the case where $message might already contain an error from DB query)
                if (strpos($message, 'No pending organizers') !== false) {
                    echo $message;
                } elseif (empty($message)) { // Fallback if $message is completely empty for some reason
                    echo "<div class='info-msg'>No pending organizers found for approval.</div>";
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