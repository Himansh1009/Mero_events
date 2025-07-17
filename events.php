<?php
// events.php (or browse-events.php)

session_start(); // PHP session for navbar

// Use includes/config.php for database connection.
require_once 'includes/config.php';

$events = []; // Initialize an empty array to store fetched events
$message = ""; // To display any messages (e.g., no events found)

// Get filter parameters from GET request and sanitize (PHP LOGIC - UNTOUCHED)
$search_term = trim($_GET['search'] ?? '');
$category_filter = trim($_GET['category'] ?? '');
$location_filter = trim($_GET['location'] ?? '');
$month_filter = trim($_GET['month'] ?? '');       
$date_filter = trim($_GET['date'] ?? ''); // Retain for backend logic if present in form

// Build the SQL query dynamically based on filters (PHP LOGIC - UNTOUCHED)
$conditions = ["e.status = 'approved'"]; 
$params = [];
$types = ""; 

if (!empty($search_term)) {
    $conditions[] = "(e.title LIKE ? OR e.description LIKE ?)"; 
    $params[] = "%" . $search_term . "%";
    $params[] = "%" . $search_term . "%";
    $types .= "ss";
}

if (!empty($category_filter) && $category_filter !== 'All') { 
    $conditions[] = "e.category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

if (!empty($location_filter)) {
    $conditions[] = "e.location LIKE ?";
    $params[] = "%" . $location_filter . "%";
    $types .= "s";
}

if (!empty($month_filter) && $month_filter !== 'All') {
    $conditions[] = "DATE_FORMAT(e.event_date, '%m') = ?";
    $params[] = $month_filter;
    $types .= "s";
}

if (!empty($date_filter)) {
    $conditions[] = "e.event_date = ?";
    $params[] = $date_filter;
    $types .= "s";
}

$where_clause = implode(" AND ", $conditions);

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
        WHERE " . $where_clause . "
        ORDER BY 
            e.event_date ASC, e.event_time ASC"; 

if ($stmt = $conn->prepare($sql)) {
    if (!empty($params)) {
        call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));
    }
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $events[] = $row;
            }
        } else {
            $message = "<div class='info-msg'>No upcoming events found matching your criteria.</div>";
        }
        $result->free();
    } else {
        $message = "<div class='error-msg'>Error executing query: " . $stmt->error . "</div>";
    }
    $stmt->close();
} else {
    $message = "<div class='error-msg'>Database error: Could not prepare statement.</div>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse All Events - Mero Events</title>
    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* 🎨 RE-APPLIED VIBRANT COLOR THEME (for everything EXCEPT NAVBAR) */
        :root {
            --primary-color: #ff6b6b;   /* Reddish-orange */
            --secondary-color: #1dd1a1; /* Teal green */
            --accent-color: #feca57;    /* Yellow-orange */
            --background-color: #f1f2f6;
            --text-color: #2f3542;
            --light-text-color: #666;
            --white: #fff;
            --border-color: #ddd;
            --shadow-color: rgba(0,0,0,0.05);
            --hover-shadow-color: rgba(0,0,0,0.1);
            --dark-gray: #4a4a4a; /* For specific text like "All Conferences" */
            --light-gray-bg: #e9ecef; /* For active buttons/toggles background */

            /* NEW NAVBAR COLORS from image */
            --navbar-bg: #ffffff;
            --navbar-border: #f0f0f0; /* Subtle border at bottom */
            --navbar-logo-color: #2f3542; /* Dark text for logo */
            --navbar-link-color: #666666; /* Gray text for links */
            --navbar-dashboard-btn-bg: #4a90e2; /* Blue from image */
            --navbar-logout-btn-bg: #e04444; /* Red from image */
            --navbar-btn-text-color: #ffffff; /* White text for buttons */
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Overall Layout Structure */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* --- NAVBAR STYLING (MATCHING IMAGE) --- */
        .main-header {
            background-color: var(--navbar-bg);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); /* Subtle shadow like the image */
            border-bottom: 1px solid var(--navbar-border); /* Thin line at bottom */
            padding: 15px 0; /* Padding remains consistent */
        }
        .main-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px; /* Constrain width */
            margin: 0 auto; /* Center nav content */
            padding: 0 20px; /* Inner padding */
        }
        .site-logo {
            font-size: 1.8em;
            font-weight: bold;
            color: var(--navbar-logo-color); /* Dark color from image */
            text-decoration: none;
            flex-shrink: 0; /* Prevent shrinking */
        }
        .site-logo:hover {
            color: var(--navbar-logo-color); /* No color change on hover, as per image */
            opacity: 0.8; /* Subtle hover effect */
        }
        .nav-links {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            gap: 25px; /* Spacing between links */
        }
        .nav-links li {
            margin-left: 0; /* Reset default li margin */
        }
        .nav-links a {
            color: var(--navbar-link-color); /* Gray color from image */
            font-weight: 500;
            padding: 5px 0;
            text-decoration: none; /* No underline */
            transition: color 0.2s ease;
        }
        .nav-links a:hover:not(.btn-navbar) { /* Ensure buttons don't get this hover */
            color: var(--navbar-logo-color); /* Slightly darker on hover */
        }
        .welcome-message {
            color: var(--navbar-link-color); /* Gray color from image */
            font-weight: 500;
            margin-right: 15px; /* Space before buttons */
            white-space: nowrap;
        }
        /* Dashboard and Logout Buttons */
        .btn-navbar { /* General style for these specific navbar buttons */
            display: inline-block;
            padding: 8px 18px; /* Adjusted padding to match image */
            border-radius: 8px; /* More rounded corners from image */
            font-weight: bold;
            font-size: 0.95em; /* Slightly smaller font */
            text-align: center;
            text-decoration: none;
            transition: background-color 0.2s ease, opacity 0.2s ease;
            color: var(--navbar-btn-text-color); /* White text for buttons */
            border: none; /* No border for solid look */
        }
        .btn-navbar.dashboard {
            background-color: var(--navbar-dashboard-btn-bg); /* Blue from image */
            margin-left: 10px; /* Space between welcome message and dashboard button */
        }
        .btn-navbar.logout {
            background-color: var(--navbar-logout-btn-bg); /* Red from image */
            margin-left: 10px; /* Space between dashboard and logout buttons */
        }
        .btn-navbar:hover {
            opacity: 0.9; /* Subtle hover effect on buttons */
        }


        /* --- Main Content Area Layout (Wireframe Structure - NO COLOR/DESIGN CHANGE) --- */
        main {
            flex-grow: 1;
            padding: 20px; 
            box-sizing: border-box;
        }

        .main-content-wrapper {
            display: flex;
            gap: 20px; 
            max-width: 1200px; 
            margin: 0 auto; 
            align-items: flex-start; 
        }

        /* Left Sidebar: Search & Filter */
        .left-sidebar {
            flex: 0 0 280px; 
            background-color: var(--white);
            border: 1px solid var(--border-color); 
            box-shadow: 0 2px 8px var(--shadow-color); 
            padding: 20px;
            box-sizing: border-box;
        }

        .sidebar-section {
            margin-bottom: 25px; 
            padding-bottom: 15px; 
            border-bottom: 1px solid var(--background-color); 
        }
        .sidebar-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .sidebar-section h2 {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--text-color);
            margin-bottom: 15px;
        }

        /* Filter Form Elements Styling (Vibrant Theme elements) */
        .filter-form .form-group {
            margin-bottom: 15px;
        }
        .filter-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--text-color);
            font-size: 0.95em;
        }
        .filter-form input[type="text"],
        .filter-form input[type="date"],
        .filter-form select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 1em;
            color: var(--text-color);
            background-color: var(--background-color); 
            box-sizing: border-box;
        }
        .filter-form input::placeholder {
            color: var(--light-text-color);
        }
        .filter-form input:focus,
        .filter-form select:focus {
            border-color: var(--accent-color); 
            outline: none;
            box-shadow: 0 0 0 2px rgba(254,202,87,0.2); 
        }

        .filter-form .btn-apply-filters,
        .filter-form .reset-button {
            width: 100%;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 1em;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease;
            margin-top: 10px;
        }
        .filter-form .btn-apply-filters {
            background-color: var(--secondary-color); 
            color: var(--white);
            border: none;
        }
        .filter-form .btn-apply-filters:hover {
            background-color: #17b38c; 
        }
        .filter-form .reset-button {
            background-color: var(--white);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }
        .filter-form .reset-button:hover {
            background-color: var(--background-color);
        }


        /* --- Right Content Area: List of Events (NO COLOR/DESIGN CHANGE) --- */
        .right-content {
            flex-grow: 1; 
            background-color: var(--white); 
            border: 1px solid var(--border-color); 
            box-shadow: 0 2px 8px var(--shadow-color); 
            padding: 20px;
            box-sizing: border-box;
        }

        .events-list-area h2 {
            font-size: 1.8em;
            color: var(--text-color);
            margin-top: 0;
            margin-bottom: 25px;
            text-align: left;
            font-weight: bold;
        }

        /* Event List Item Styling (Detailed, as in previous vibrant theme version) */
        .event-list-item {
            background-color: var(--white); 
            border: 1px solid var(--background-color); 
            border-radius: 8px; 
            box-shadow: 0 1px 4px rgba(0,0,0,0.05); 
            padding: 15px 20px;
            margin-bottom: 15px; 
            display: flex;
            align-items: flex-start; 
            gap: 20px;
            transition: box-shadow 0.2s ease, transform 0.2s ease;
            text-decoration: none; 
            color: inherit; 
        }
        .event-list-item:hover {
            box-shadow: 0 2px 10px var(--hover-shadow-color);
            transform: translateY(-2px); 
        }

        .event-logo {
            flex-shrink: 0; 
            width: 50px; 
            height: 50px;
            background-color: var(--background-color); 
            border-radius: 50%; 
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2em;
            color: var(--light-text-color);
            border: 1px solid var(--border-color);
        }

        .event-content-main {
            flex-grow: 1;
        }
        .event-content-main h3 {
            font-size: 1.3em; 
            color: var(--text-color);
            margin-top: 0;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .event-content-main .location-category {
            font-size: 0.9em;
            color: var(--light-text-color);
            margin-bottom: 8px;
        }
        .event-content-main .location-category span {
            margin-right: 10px;
        }
        .event-content-main p {
            font-size: 0.95em;
            color: var(--light-text-color);
            margin-bottom: 0;
        }


        .event-details-right {
            flex-shrink: 0; 
            text-align: right; 
            font-size: 0.9em;
            color: var(--light-text-color);
            display: flex;
            flex-direction: column;
            gap: 6px; 
            padding-left: 15px; 
        }
        .event-details-right p {
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: flex-end; 
            gap: 5px; 
            white-space: nowrap; 
        }
        .event-details-right .icon {
            color: var(--dark-gray); 
            font-size: 1.1em;
        }
        .event-details-right .date-info {
            font-weight: bold;
            color: var(--text-color);
        }
        .event-details-right .tickets-count {
            color: var(--accent-color); 
        }
        .event-details-right .price-info { 
            font-weight: bold;
            color: var(--secondary-color); 
        }
        .event-details-right .sold-out-status {
            color: var(--primary-color);
            font-weight: bold;
        }

        /* Message styling */
        .message {
            margin: 20px auto; 
            padding: 12px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            max-width: 600px;
        }
        .info-msg {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .error-msg {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* --- Footer (NO CHANGE IN STYLING/COLORS) --- */
        .main-footer {
            background-color: var(--text-color); 
            color: var(--white);
            text-align: center;
            padding: 20px 0;
            font-size: 0.9em;
            margin-top: auto;
            width: 100%;
        }
        .main-footer .container {
            padding: 0 20px;
        }


        /* Responsive adjustments */
        @media (max-width: 992px) {
            .main-content-wrapper {
                flex-direction: column; 
                align-items: center;
            }
            .left-sidebar, .right-content {
                flex: none;
                width: 100%;
                max-width: 600px; 
            }
            .left-sidebar {
                margin-bottom: 20px;
            }
            /* Adjust navbar for smaller screens if needed, otherwise default browser behavior */
            .main-nav {
                flex-direction: column; /* Stack logo and links */
                gap: 10px;
                align-items: flex-start;
            }
            .nav-links {
                flex-wrap: wrap;
                justify-content: flex-start;
                gap: 10px;
            }
            .nav-links li {
                margin-left: 0;
            }
            .welcome-message {
                margin-right: 0; /* Adjust spacing */
                width: 100%; /* Take full width */
                text-align: center;
            }
            .btn-navbar.dashboard, .btn-navbar.logout {
                 margin-left: 0; /* Remove specific margin for stacking */
            }
        }
        @media (max-width: 768px) {
            .events-header h1 { font-size: 2.2em; }
            .events-header p { font-size: 1em; }
            .event-list-item {
                flex-direction: column; 
                align-items: flex-start;
                gap: 15px;
            }
            .event-details-right {
                width: 100%;
                text-align: left;
                padding-left: 0;
                border-top: 1px solid var(--background-color); 
                padding-top: 15px;
            }
            .event-details-right p {
                justify-content: flex-start;
            }
            .filter-form .form-group {
                flex: 1 1 100%; 
            }
        }
        @media (max-width: 480px) {
            body { padding: 10px; }
            .left-sidebar, .right-content { padding: 15px; }
            .main-nav { padding: 10px; }
            .site-logo { font-size: 1.3em; }
        }
    </style>
</head>
<body>
    <header>
        <div class="main-header">
            <nav class="main-nav">
                <a href="index.php" class="site-logo">Mero Events</a>
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="events.php">Events</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    
                    <?php
                    // Dynamic Login/Dashboard/Logout links (PHP logic retained)
                    if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
                        $dashboard_link = '#'; 
                        
                        if (isset($_SESSION["user_type"])) {
                            if ($_SESSION["user_type"] == "organizer") {
                                $dashboard_link = 'organizer-dashboard/dashboard.php';
                            } elseif ($_SESSION["user_type"] == "user") {
                                $dashboard_link = 'user-dashboard/dashboard.php';
                            }
                        }
                        // Apply the new button classes
                        echo '<li class="welcome-message">Welcome, ' . htmlspecialchars($_SESSION["user_name"]) . '</li>';
                        echo '<li><a href="' . htmlspecialchars($dashboard_link) . '" class="btn-navbar dashboard">Dashboard</a></li>';
                        echo '<li><a href="logout.php" class="btn-navbar logout">Logout</a></li>'; 
                    } else {
                        echo '<li><a href="auth.php" class="btn-navbar dashboard">Login/Register</a></li>'; // Reusing dashboard button style for Login/Register
                    }
                    ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="main-content-wrapper">
            <!-- Left Sidebar -->
            <aside class="left-sidebar">
                <div class="sidebar-section">
                    <h2>Search</h2>
                    <form action="events.php" method="get" class="filter-form">
                        <div class="form-group">
                            <label for="search">Keyword:</label>
                            <input type="text" id="search" name="search" placeholder="Search event title or keyword" value="<?php echo htmlspecialchars($search_term); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Category:</label>
                            <select id="category" name="category">
                                <option value="All" <?php echo ($category_filter == 'All') ? 'selected' : ''; ?>>All Categories</option>
                                <option value="Education" <?php echo ($category_filter == 'Education') ? 'selected' : ''; ?>>Education</option>
                                <option value="Tech" <?php echo ($category_filter == 'Tech') ? 'selected' : ''; ?>>Tech</option>
                                <option value="Community" <?php echo ($category_filter == 'Community') ? 'selected' : ''; ?>>Community</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="location">Location:</label>
                            <input type="text" id="location" name="location" placeholder="e.g., Bharatpur" value="<?php echo htmlspecialchars($location_filter); ?>">
                        </div>

                        <div class="form-group">
                            <label for="month">Month:</label>
                            <select id="month" name="month">
                                <option value="All" <?php echo ($month_filter == 'All') ? 'selected' : ''; ?>>All Months</option>
                                <?php
                                $months = [
                                    '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
                                    '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
                                    '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
                                ];
                                foreach ($months as $num => $name) {
                                    echo '<option value="' . $num . '"' . (($month_filter == $num) ? ' selected' : '') . '>' . $name . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn-apply-filters">Apply Filters</button>
                        <button type="button" class="reset-button" onclick="window.location.href='events.php'">Reset Filters</button>
                    </form>
                </div>
            </aside>

            <!-- Right Content: List of Events -->
            <section class="right-content">
                <div class="events-list-area">
                    <h2>List of events</h2>
                    <?php 
                    // Display messages (error or info for no events)
                    if (!empty($message)) {
                        echo "<div class='message'>" . $message . "</div>";
                    }
                    ?>

                    <?php if (!empty($events)): ?>
                        <?php foreach ($events as $event): 
                            $tickets_remaining = $event['total_tickets'] - $event['tickets_booked'];
                            $is_sold_out = ($tickets_remaining <= 0);
                        ?>
                            <!-- Link entire event item -->
                            <a href="event-details.php?event_id=<?php echo htmlspecialchars($event['id']); ?>" class="event-list-item">
                                <div class="event-logo">
                                    <i class="fa-solid fa-building-columns"></i> <!-- Placeholder icon -->
                                </div>
                                <div class="event-content-main">
                                    <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                    <p class="location-category">
                                        <span><?php echo htmlspecialchars($event['location']); ?></span> 
                                        <span>• <?php echo htmlspecialchars($event['category']); ?></span>
                                    </p>
                                    <p>Organized by: <?php echo htmlspecialchars($event['organizer_name']); ?></p>
                                </div>
                                <div class="event-details-right">
                                    <p class="date-info">
                                        <i class="fa-solid fa-calendar-days icon"></i> 
                                        <?php echo htmlspecialchars(date('M d', strtotime($event['event_date']))); ?> 
                                        - <?php echo htmlspecialchars(date('M d', strtotime($event['event_date']))); ?>
                                        <br><?php echo htmlspecialchars(date('Y', strtotime($event['event_date']))); ?>
                                    </p>
                                    <p class="tickets-count">
                                        <i class="fa-solid fa-users icon"></i> 
                                        <?php echo htmlspecialchars($event['total_tickets']); ?>
                                    </p>
                                    <p class="price-info">
                                        <i class="fa-solid fa-ticket-alt icon"></i> 
                                        <?php if ($is_sold_out): ?>
                                            <span class="sold-out-status">Sold Out</span>
                                        <?php else: ?>
                                            Book Now
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <footer class="main-footer">
        <div class="container">
            <p>© 2023 Mero Events. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>