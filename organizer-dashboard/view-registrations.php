<?php
// organizer-dashboard/view-registrations.php

// Enable full error reporting at the very top for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Security: Only allow the logged-in organizer to view this page.
require_once '../includes/session-organizer.php';
// Use ../includes/config.php for DB connection
require_once '../includes/config.php';

$message = ""; // For displaying feedback messages
$organizer_id_from_session = $_SESSION['user_id']; // Get the logged-in organizer's ID

// Bonus: Optional search bar or filter by event name
$search_event_title = trim($_GET['search_event'] ?? ''); // Get search term from GET

// --- Handle CSV Download Request ---
if (isset($_POST['download_csv'])) {
    // We'll re-run the query with the current filters to get the data for CSV
    // This is safer than relying on hidden fields for the entire dataset

    $csv_conditions = ["e.organizer_id = ?"];
    $csv_params = [];
    $csv_types = "s"; // Start with 's' for the string parameter from search_event_title if it gets added.
                      // Adjust as needed if you have multiple conditions.

    // Correctly bind organizer_id by reference
    $organizer_id_ref_csv = $organizer_id_from_session; // Create a temporary variable for reference
    $csv_params[] = &$organizer_id_ref_csv; // Pass by reference

    if (!empty($search_event_title)) {
        $csv_conditions[] = "e.title LIKE ?";
        $csv_params[] = "%" . $search_event_title . "%";
        $csv_types .= "s";
    }

    $csv_where_clause = implode(" AND ", $csv_conditions);

    // Make sure types string matches number of parameters for call_user_func_array
    // The bind_param arguments need to be an array with types string as first element, then references.
    $csv_bind_args = array_merge([$csv_types], $csv_params);

    $sql_csv = "SELECT 
                    e.title AS EventTitle,
                    u.name AS UserName,
                    u.email AS UserEmail,
                    tb.num_tickets AS TicketsBooked,
                    tb.booking_date AS BookingDate
                FROM 
                    ticket_bookings tb
                JOIN 
                    events e ON tb.event_id = e.id
                JOIN 
                    users u ON tb.user_id = u.id
                WHERE " . $csv_where_clause . "
                ORDER BY 
                    e.title ASC, tb.booking_date ASC";

    if ($stmt_csv = $conn->prepare($sql_csv)) {
        // Dynamically bind parameters using call_user_func_array with references
        // The first argument to call_user_func_array is the callable itself.
        // The second argument is an array of arguments for the callable.
        // In this array, the first element is the types string, and subsequent elements are the parameters BY REFERENCE.
        call_user_func_array([$stmt_csv, 'bind_param'], $csv_bind_args);
        
        if ($stmt_csv->execute()) {
            $result_csv = $stmt_csv->get_result();

            // Set headers for CSV download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="event_registrations_' . date('Y-m-d') . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w'); // Open a file pointer to php://output

            // Output CSV headers
            fputcsv($output, ['Event Title', 'User Name', 'User Email', 'Tickets Booked', 'Booking Date']);

            // Output data rows
            while ($row_csv = $result_csv->fetch_assoc()) {
                fputcsv($output, $row_csv);
            }

            fclose($output); // Close the file pointer
            $stmt_csv->close();
            $conn->close(); // Close connection before exiting
            exit(); // Stop script execution
        } else {
            // Error handling for CSV query execution
            error_log("CSV download error: " . $stmt_csv->error);
            $message = "<div class='error-msg'>Failed to generate CSV: " . $stmt_csv->error . "</div>";
            $stmt_csv->close();
        }
    } else {
        // Error handling for CSV query preparation
        error_log("CSV download preparation error: " . $conn->error);
        $message = "<div class='error-msg'>Failed to generate CSV (DB preparation error).</div>";
    }
}


// --- Fetch bookings for the logged-in organizer's events ---
$conditions = ["e.organizer_id = ?"];
$params = [];
$types = "i"; // 'i' for integer (organizer_id)

// Correctly bind organizer_id by reference for the main query
$organizer_id_ref = $organizer_id_from_session; // Create a temporary variable for reference
$params[] = &$organizer_id_ref; // Pass by reference

// Apply search filter if present
if (!empty($search_event_title)) {
    $conditions[] = "e.title LIKE ?"; // Search by Event Title
    $search_term_ref = "%" . $search_event_title . "%"; // Create temporary variable for reference
    $params[] = &$search_term_ref; // Pass by reference
    $types .= "s";
}

$where_clause = implode(" AND ", $conditions);

$sql = "SELECT 
            e.title AS event_title,
            u.name AS user_name,
            u.email AS user_email,
            tb.num_tickets,
            tb.booking_date,
            tb.status AS booking_status
        FROM 
            ticket_bookings tb
        JOIN 
            events e ON tb.event_id = e.id
        JOIN 
            users u ON tb.user_id = u.id
        WHERE " . $where_clause . "
        ORDER BY 
            e.title ASC, tb.booking_date DESC"; // Order by event title, then latest booking

try {
    if ($stmt = $conn->prepare($sql)) {
        // Dynamically bind parameters using call_user_func_array with references
        $bind_args = array_merge([$types], $params); // Prepare arguments array
        call_user_func_array([$stmt, 'bind_param'], $bind_args); // Bind parameters
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $bookings_data[] = $row;
                }
            } else {
                $message = "<div class='info-msg'>No registrations found for your events." . (!empty($search_event_title) ? " (matching '" . htmlspecialchars($search_event_title) . "')" : "") . "</div>";
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
    $message = "<div class='error-msg'>A server error occurred while loading registrations. Please try again.</div>";
    error_log("view-registrations.php DB Error for organizer " . $organizer_id_from_session . ": " . $e->getMessage());
}

$conn->close(); // Close database connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Registrations - Mero Events</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Define Color Palette and general styles for consistency */
        :root {
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

            --status-booked-bg: #d1ecf1;
            --status-booked-text: #0c5460;
            --status-cancelled-bg: #f8d7da;
            --status-cancelled-text: #721c24;
            --status-completed-bg: #d4edda;
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

        main {
            flex-grow: 1;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .registrations-container {
            background-color: var(--white);
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-color);
            width: 100%;
            max-width: 1200px; /* Wider for detailed table */
            margin: auto;
            box-sizing: border-box;
        }

        .registrations-container h2 {
            text-align: center;
            color: var(--text-color);
            margin-bottom: 30px;
            font-size: 2.5em;
            font-weight: bold;
        }

        /* Search & Download Section */
        .search-download-section {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        .search-download-section .search-form {
            flex-grow: 1;
            display: flex;
            gap: 10px;
            max-width: 400px;
        }
        .search-download-section .search-form input[type="text"] {
            flex-grow: 1;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 0.95em;
            background-color: var(--background-color);
            color: var(--text-color);
        }
        .search-download-section .search-form button {
            background-color: var(--navbar-dashboard-btn-bg); /* Blue */
            color: var(--white);
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.95em;
            transition: background-color 0.2s ease;
        }
        .search-download-section .search-form button:hover {
            background-color: #357bd8;
        }

        .search-download-section .download-btn {
            background-color: #28a745; /* Green for download */
            color: var(--white);
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 1em;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s ease;
            text-decoration: none; /* For link-styled button */
        }
        .search-download-section .download-btn:hover {
            background-color: #218838;
        }


        /* --- Responsive Table Wrapper --- */
        .table-responsive-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
            margin-top: 20px; /* Adjust from h2 */
        }

        /* --- Table Styling --- */
        .registrations-table {
            width: 100%;
            min-width: 800px; /* Minimum width for horizontal scroll */
            border-collapse: separate;
            border-spacing: 0;
            background-color: var(--white);
        }

        .registrations-table th, .registrations-table td {
            padding: 15px 20px;
            text-align: left;
            vertical-align: middle;
            font-size: 0.95em;
            color: var(--text-color);
            border-bottom: 1px solid #eeeeee;
            white-space: nowrap;
        }

        .registrations-table th {
            background-color: #f8f8f8;
            color: var(--text-color);
            font-weight: bold;
            border-bottom: 1px solid var(--border-color);
        }
        .registrations-table thead th:first-child { border-top-left-radius: 8px; }
        .registrations-table thead th:last-child { border-top-right-radius: 8px; }

        .registrations-table tbody tr:last-child td { border-bottom: none; }

        .registrations-table tr:hover { background-color: #f9f9f9; }

        /* Status Badges */
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
        .status-completed { /* If you track completed status */
            background-color: var(--status-completed-bg);
            color: var(--status-completed-text);
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
            .registrations-container { padding: 20px; max-width: 95%; }
            .registrations-table { min-width: 700px; }
            .registrations-table th, .registrations-table td { padding: 10px 15px; font-size: 0.9em; }
            .search-download-section { flex-direction: column; align-items: stretch; }
            .search-download-section .search-form { max-width: 100%; }
            /* Navbar adjustments */
            .main-nav { flex-direction: column; gap: 10px; align-items: flex-start; padding: 0 15px; }
            .site-logo { margin-bottom: 5px; }
            .nav-links { flex-wrap: wrap; justify-content: flex-start; gap: 10px; width: 100%; }
            .nav-links li { margin-left: 0; }
            .welcome-message { margin-right: 0; width: 100%; text-align: center; }
            .btn-navbar.dashboard, .btn-navbar.logout { margin-left: 0; width: auto; flex-grow: 1; }
        }

        @media (max-width: 768px) {
            .registrations-container h2 { font-size: 2em; }
            .table-responsive-wrapper { border-radius: 0; box-shadow: none; }
            .registrations-table { min-width: unset; }
            .registrations-table thead, .registrations-table tbody, .registrations-table th, .registrations-table td, .registrations-table tr { display: block; }
            .registrations-table thead tr { position: absolute; top: -9999px; left: -9999px; }
            .registrations-table tr { margin-bottom: 15px; border: 1px solid #eee; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
            .registrations-table td { border: none; position: relative; padding-left: 50%; text-align: right; white-space: normal; }
            .registrations-table td:before {
                content: attr(data-label);
                position: absolute; left: 10px; width: 45%; white-space: nowrap; text-align: left; font-weight: bold; color: var(--text-color);
            }
        }
        @media (max-width: 480px) {
            .registrations-container { padding: 15px; }
            .registrations-table th, .registrations-table td { padding: 8px 10px; }
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
                // Dynamic links for logged-in organizer (consistent with other dashboard pages)
                $dashboard_link = 'dashboard.php'; 

                echo '<li class="welcome-message">Welcome, ' . htmlspecialchars($_SESSION["user_name"]) . '</li>';
                echo '<li><a href="' . htmlspecialchars($dashboard_link) . '" class="btn-navbar dashboard">Dashboard</a></li>';
                echo '<li><a href="../logout.php" class="btn-navbar logout">Logout</a></li>'; 
                ?>
            </ul>
        </nav>
    </header>

    <main>
        <div class="registrations-container">
            <h2>Registrations for Your Events</h2>
            
            <div class="search-download-section">
                <!-- Bonus: Optional search bar -->
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="get" class="search-form">
                    <input type="text" name="search_event" placeholder="Search by Event Title" value="<?php echo htmlspecialchars($search_event_title); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>

                <!-- Download CSV Button -->
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" style="margin:0;">
                    <input type="hidden" name="download_csv" value="1">
                    <input type="hidden" name="search_event_for_csv" value="<?php echo htmlspecialchars($search_event_title); ?>">
                    <button type="submit" class="download-btn"><i class="fas fa-download"></i> Download CSV</button>
                </form>
            </div>

            <?php 
            if (!empty($message)) {
                echo "<div class='message'>" . $message . "</div>";
            }
            ?>

            <?php if (!empty($bookings_data)): ?>
                <div class="table-responsive-wrapper">
                    <table class="registrations-table">
                        <thead>
                            <tr>
                                <th>Event Title</th>
                                <th>Booked User’s Name</th>
                                <th>Booked User’s Email</th>
                                <th>Number of Tickets</th>
                                <th>Booking Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings_data as $booking): ?>
                                <tr>
                                    <td data-label="Event Title"><?php echo htmlspecialchars($booking['event_title']); ?></td>
                                    <td data-label="User Name"><?php echo htmlspecialchars($booking['user_name']); ?></td>
                                    <td data-label="User Email"><?php echo htmlspecialchars($booking['user_email']); ?></td>
                                    <td data-label="Number of Tickets"><?php echo htmlspecialchars($booking['num_tickets']); ?></td>
                                    <td data-label="Booking Date"><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($booking['booking_date']))); ?></td>
                                    <td data-label="Status">
                                        <?php 
                                            // Determine status badge class
                                            $status_class = 'status-badge';
                                            if ($booking['booking_status'] == 'booked') {
                                                $status_class .= ' status-booked';
                                            } elseif ($booking['booking_status'] == 'cancelled') {
                                                $status_class .= ' status-cancelled';
                                            } elseif ($booking['booking_status'] == 'completed') {
                                                $status_class .= ' status-completed';
                                            }
                                        ?>
                                        <span class="<?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars(ucfirst($booking['booking_status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div> <!-- End .table-responsive-wrapper -->
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