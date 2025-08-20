<?php
// admin-dashboard/approve-organizers.php

// Include session check with session-admin.php
require_once '../includes/session-admin.php';
// Connect using includes/config.php
require_once '../includes/config.php';

$message = ""; // For displaying success or error messages

// --- Handle Approval Action ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['approve_organizer_id'])) {
    $organizer_id_to_approve = trim($_POST['approve_organizer_id']);

    // Validate organizer ID
    if (!filter_var($organizer_id_to_approve, FILTER_VALIDATE_INT)) {
        $message = "<div class='error-msg'>Invalid organizer ID provided.</div>";
    } else {
        // Update that organizer’s is_approved to 1
        $sql = "UPDATE organizers SET is_approved = 1 WHERE id = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $organizer_id_to_approve);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
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

// --- Fetch organizers where is_approved = 0 ---
$pending_organizers = [];
// Assuming 'created_at' for ordering, if not present, remove from ORDER BY
$sql = "SELECT id, name, email, id_proof FROM organizers WHERE is_approved = 0 ORDER BY created_at ASC"; 

if ($result = $conn->query($sql)) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $pending_organizers[] = $row;
        }
    } else {
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
    <!-- External CSS: ../assets/css/style.css -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Specific styling for the approve-organizers page */
        :root { /* Ensure colors are defined */
            --background-color: #f1f2f6;
            --text-color: #2f3542;
            --white: #ffffff;
            --shadow-color: rgba(0,0,0,0.05);
            --border-color: #ddd;
            --navbar-bg: #ffffff;
            --navbar-border: #f0f0f0; 
            --navbar-logo-color: #6A5ACD; 
            --navbar-link-color: #666666; 
            --navbar-dashboard-btn-bg: #4a90e2; 
            --navbar-logout-btn-bg: #e04444; 
            --navbar-btn-text-color: #ffffff; 
            --primary-color: #ff6b6b; /* Add primary for consistency */
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

        main {
            flex-grow: 1;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .admin-approval-container {
            background-color: var(--white);
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-color);
            width: 100%;
            max-width: 1000px;
            margin: auto;
            box-sizing: border-box;
        }

        .admin-approval-container h2 {
            text-align: center;
            color: var(--text-color);
            margin-bottom: 30px;
            font-size: 2.5em;
            font-weight: bold;
        }

        /* Table Styling */
        .organizers-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 30px;
            background-color: var(--white);
            border-radius: 8px;
            overflow: hidden;
        }

        .organizers-table th, .organizers-table td {
            padding: 15px 20px;
            text-align: left;
            vertical-align: middle;
            font-size: 0.95em;
            color: var(--text-color);
            border-bottom: 1px solid #eeeeee;
            white-space: nowrap;
        }

        .organizers-table th {
            background-color: #f8f8f8;
            color: var(--text-color);
            font-weight: bold;
            border-bottom: 1px solid var(--border-color);
        }
        .organizers-table thead th:first-child { border-top-left-radius: 8px; }
        .organizers-table thead th:last-child { border-top-right-radius: 8px; }

        .organizers-table tbody tr:last-child td { border-bottom: none; }

        .organizers-table tr:hover { background-color: #f9f9f9; }

        /* Action buttons container */
        .organizers-table .actions {
            white-space: nowrap;
            text-align: center;
            display: flex; /* Use flex for button alignment */
            gap: 8px; /* Space between buttons */
            justify-content: center;
        }
        .organizers-table .actions form {
            display: inline-block; /* Keep forms inline */
            margin: 0; 
        }
        .organizers-table .actions button,
        .organizers-table .actions a.btn-action { /* Combined styling for buttons/links */
            padding: 8px 12px;
            font-size: 0.9em;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            color: var(--white);
            text-decoration: none; /* For link-styled buttons */
            transition: background-color 0.2s ease, opacity 0.2s ease;
            box-sizing: border-box;
        }

        .btn-approve {
            background-color: #28a745; /* Green */
        }
        .btn-approve:hover {
            background-color: #218838;
        }
        /* New View Details Button */
        .btn-view-details {
            background-color: #4a90e2; /* Blue */
        }
        .btn-view-details:hover {
            background-color: #357bd8;
        }

        /* ID Proof link styling */
        .id-proof-link {
            color: var(--navbar-dashboard-btn-bg); /* Use blue from palette */
            text-decoration: none;
            font-weight: bold;
        }
        .id-proof-link:hover {
            text-decoration: underline;
        }
        
        /* Message styling */
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

        /* Header/Footer (Consistent with site-wide theme) */
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
            .admin-approval-container { padding: 20px; max-width: 95%; }
            .organizers-table { min-width: 700px; }
            .organizers-table th, .organizers-table td { padding: 10px 15px; font-size: 0.9em; }
            .organizers-table .actions { flex-wrap: wrap; justify-content: flex-start; }
            .organizers-table .actions button, .organizers-table .actions a.btn-action { margin-bottom: 5px; }
            /* Navbar adjustments */
            .main-nav { flex-direction: column; gap: 10px; align-items: flex-start; padding: 0 15px; }
            .site-logo { margin-bottom: 5px; }
            .nav-links { flex-wrap: wrap; justify-content: flex-start; gap: 10px; width: 100%; }
            .nav-links li { margin-left: 0; }
            .welcome-message { margin-right: 0; width: 100%; text-align: center; }
            .btn-navbar.dashboard, .btn-navbar.logout { margin-left: 0; width: auto; flex-grow: 1; }
        }

        @media (max-width: 768px) {
            .admin-approval-container h2 { font-size: 2em; }
            .organizers-table { display: block; overflow-x: auto; white-space: nowrap; -webkit-overflow-scrolling: touch; }
            .organizers-table thead, .organizers-table tbody, .organizers-table th, .organizers-table td, .organizers-table tr { display: block; }
            .organizers-table thead tr { position: absolute; top: -9999px; left: -9999px; }
            .organizers-table tr { margin-bottom: 15px; border: 1px solid #eee; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
            .organizers-table td { border: none; position: relative; padding-left: 50%; text-align: right; white-space: normal; }
            .organizers-table td:before {
                content: attr(data-label);
                position: absolute; left: 10px; width: 45%; white-space: nowrap; text-align: left; font-weight: bold; color: var(--text-color);
            }
            .organizers-table .actions { justify-content: flex-end; padding-top: 10px; border-top: 1px solid #eee; margin-top: 10px; gap: 8px;}
            .organizers-table .actions button, .organizers-table .actions a.btn-action { width: auto; flex-grow: 1; }
        }
        @media (max-width: 480px) {
            .admin-approval-container { padding: 15px; }
            .organizers-table th, .organizers-table td { padding: 8px 10px; }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <nav class="main-nav">
            <a href="../index.php" class="site-logo">Mero Events (Admin)</a>
            <ul class="nav-links">
                <li><a href="dashboard.php" class="btn-navbar dashboard">Back to Dashboard</a></li> 
                <li><a href="../admin-logout.php" class="btn-navbar logout">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="admin-approval-container">
            <h2>Approve Organizers</h2>
            
            <?php 
            if (!empty($message)) {
                echo $message;
            }
            ?>

            <?php if (!empty($pending_organizers)): ?>
                <div class="table-responsive-wrapper">
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
                                    <td data-label="Name"><?php echo htmlspecialchars($organizer['name']); ?></td>
                                    <td data-label="Email"><?php echo htmlspecialchars($organizer['email']); ?></td>
                                    <td data-label="ID Proof">
                                        <?php if (!empty($organizer['id_proof'])): ?>
                                            <a href="../<?php echo htmlspecialchars($organizer['id_proof']); ?>" target="_blank" class="id-proof-link">View Proof</a>
                                        <?php else: ?>
                                            No proof provided
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Actions" class="actions">
                                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" onsubmit="return confirm('Are you sure you want to approve <?php echo htmlspecialchars($organizer['name']); ?>?');">
                                            <input type="hidden" name="approve_organizer_id" value="<?php echo $organizer['id']; ?>">
                                            <button type="submit" class="btn-approve">Approve</button>
                                        </form>
                                        <!-- Add View Details button -->
                                        <a href="view-organizer.php?organizer_id=<?php echo htmlspecialchars($organizer['id']); ?>" class="btn-action btn-view-details">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div> <!-- End .table-responsive-wrapper -->
            <?php else: ?>
                <?php 
                if (strpos($message, 'No pending organizers') !== false) {
                    echo $message;
                } elseif (empty($message)) {
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