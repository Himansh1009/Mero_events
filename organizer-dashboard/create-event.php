<?php
// organizer-dashboard/create-event.php

// Enable full error reporting at the top
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Use includes/session-organizer.php for session protection.
require_once '../includes/session-organizer.php';
// Use includes/config.php for database connection.
require_once '../includes/config.php';

// Initialize variables for form data and messages
$event_title = $description = $event_date = $event_time = $category = ""; 
$province = ""; 
$city = "";     
$location_full_string = ""; 
$default_single_ticket_price = ""; // For the single ticket price if seating not enabled
$default_single_ticket_quantity = ""; // For the single ticket quantity if seating not enabled
$enable_seating_options = 'off'; // Default value for checkbox (corresponds to unchecked)
$message = "";

// For dynamic ticket types, store previously entered values to re-populate on error
$ticket_types_data = []; // Will store ['name', 'price', 'quantity'] for each type

// Directory for event image uploads
$upload_dir = '../event-images/'; 
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true); 
}
$default_image_path = 'event-images/default.jpg'; 

// Get the organizer's ID from the session
$organizer_id = $_SESSION["user_id"];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get and sanitize input from the form
    $event_title = trim($_POST["event_title"]);
    $description = trim($_POST["description"]);
    $event_date = trim($_POST["event_date"]);
    $event_time = trim($_POST["event_time"]);
    $province = trim($_POST["province"] ?? ''); 
    $city = trim($_POST["city"] ?? '');         
    $location_full_string = $city . ', ' . $province; 
    $category = trim($_POST["category"]); // Get category from form
    $enable_seating_options = isset($_POST['enable_seating_options']) ? 'on' : 'off'; // Get checkbox state

    $event_image_path_for_db = $default_image_path; 
    $upload_error = false; 
    
    // Default values for database if seating options are NOT enabled
    $total_tickets_sum = 0; // Will be sum of types or single quantity
    $single_ticket_price_for_db = 0.00; // Correctly initialized

    // Validate main event details
    if (empty($event_title) || empty($description) || empty($event_date) || empty($event_time) || empty($province) || empty($city) || empty($category)) {
        $message = "<div class='error-msg'>All main event fields (Title, Description, Date, Time, Location, Category) are required.</div>";
    } else {
        // Handle image upload if a file was selected (UNCHANGED)
        if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] == UPLOAD_ERR_OK) {
            $file_name = $_FILES['event_image']['name'];
            $file_tmp_name = $_FILES['event_image']['tmp_name'];
            $file_size = $_FILES['event_image']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_extensions = array("jpg", "jpeg", "png", "webp");
            $max_file_size = 2 * 1024 * 1024; 

            if (!in_array($file_ext, $allowed_extensions)) {
                $message = "<div class='error-msg'>Invalid image file type. Only JPG, JPEG, PNG, WEBP are allowed.</div>";
                $upload_error = true;
            } elseif ($file_size > $max_file_size) {
                $message = "<div class='error-msg'>Image file is too large (max 2MB).</div>";
                $upload_error = true;
            } else {
                $new_file_name = uniqid('event_img_', true) . '.' . $file_ext;
                $destination = $upload_dir . $new_file_name; 
                $event_image_path_for_db = 'event-images/' . $new_file_name; 

                if (!move_uploaded_file($file_tmp_name, $destination)) {
                    $message = "<div class='error-msg'>Failed to upload event image. Please check directory permissions.</div>";
                    $upload_error = true;
                }
            }
        } elseif (isset($_FILES['event_image']) && $_FILES['event_image']['error'] != UPLOAD_ERR_NO_FILE) {
            $message = "<div class='error-msg'>An upload error occurred: Code " . $_FILES['event_image']['error'] . "</div>";
            $upload_error = true;
        }

        // Validate Ticket/Seating Options
        if ($enable_seating_options === 'on') {
            // Validate dynamic ticket types (similar to previous version, but now mandatory if checked)
            $has_ticket_types = false;
            $all_ticket_types_valid = true;
            $processed_ticket_types = []; 
            $total_tickets_sum = 0; // Sum quantities for overall event ticket count if needed

            if (isset($_POST['ticket_type_name']) && is_array($_POST['ticket_type_name'])) {
                for ($i = 0; $i < count($_POST['ticket_type_name']); $i++) {
                    $type_name = trim($_POST['ticket_type_name'][$i]);
                    $price = trim($_POST['ticket_type_price'][$i]);
                    $quantity = trim($_POST['ticket_type_quantity'][$i]);

                    // Store for re-population (important to ensure all keys exist for htmlspecialchars)
                    $ticket_types_data[] = [
                        'name' => $type_name,
                        'price' => $price,
                        'quantity' => $quantity
                    ];

                    if (empty($type_name) || !is_numeric($price) || $price < 0 || !is_numeric($quantity) || $quantity < 0) {
                        $message = "<div class='error-msg'>All ticket type fields must be filled with valid non-negative numbers.</div>";
                        $all_ticket_types_valid = false;
                        break;
                    }
                    // Check for duplicate type names within this form submission
                    // Convert to lowercase for case-insensitive check
                    $processed_names = array_map('strtolower', array_column($processed_ticket_types, 'name'));
                    if (in_array(strtolower($type_name), $processed_names)) {
                        $message = "<div class='error-msg'>Duplicate ticket type name found: " . htmlspecialchars($type_name) . ".</div>";
                        $all_ticket_types_valid = false;
                        break;
                    }
                    
                    $has_ticket_types = true;
                    $processed_ticket_types[] = [
                        'name' => $type_name,
                        'price' => (float)$price,
                        'quantity' => (int)$quantity
                    ];
                    $total_tickets_sum += (int)$quantity; // Sum quantities
                }
            } 
            if (!$has_ticket_types && $all_ticket_types_valid) { // if array is empty but no other error
                $message = "<div class='error-msg'>Please add at least one ticket type when seating options are enabled.</div>";
                $all_ticket_types_valid = false;
            }


            if (!$all_ticket_types_valid) { // If there were issues with ticket types
                // Message already set above, just mark overall process as invalid
                $message .= "<br>Please correct ticket type details.";
            }

        } else { // Seating options NOT enabled (use single ticket quantity/price)
            $default_single_ticket_price = trim($_POST['default_single_ticket_price'] ?? '');
            $default_single_ticket_quantity = trim($_POST['default_single_ticket_quantity'] ?? '');

            if (empty($default_single_ticket_price) || !is_numeric($default_single_ticket_price) || $default_single_ticket_price < 0 || 
                empty($default_single_ticket_quantity) || !is_numeric($default_single_ticket_quantity) || $default_single_ticket_quantity < 1) {
                $message = "<div class='error-msg'>Please provide a valid Price and Quantity for the default ticket.</div>";
            } else {
                $total_tickets_sum = (int)$default_single_ticket_quantity; // Sum for events table total_tickets
                $single_ticket_price_for_db = (float)$default_single_ticket_price;
            }
        }
        
        // Only proceed to insert into DB if no validation errors and no upload errors
        if (empty($message) && !$upload_error) {
            $conn->begin_transaction();
            $event_inserted = false;
            $new_event_id = null;

            try {
                // Insert into `events` table
                // Pass total_tickets_sum for event_total_tickets
                $sql_event = "INSERT INTO events (title, description, event_date, event_time, location, category, total_tickets, tickets_booked, organizer_id, status, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, 'pending', ?)";
                
                if ($stmt_event = $conn->prepare($sql_event)) {
                    // Corrected bind_param type string: "sssssiis" (6 s, 2 i, 1 s) for 9 placeholders
                    $stmt_event->bind_param(
                        "ssssssiis", // Corrected type string
                        $param_title,
                        $param_description,
                        $param_event_date,
                        $param_event_time,
                        $param_location, 
                        $param_category, 
                        $param_total_tickets_sum, 
                        $param_organizer_id,
                        $param_image_path 
                    );

                    $param_title = $event_title;
                    $param_description = $description;
                    $param_event_date = $event_date;
                    $param_event_time = $event_time;
                    $param_location = $location_full_string; 
                    $param_category = $category; 
                    $param_total_tickets_sum = $total_tickets_sum; 
                    $param_organizer_id = $organizer_id; 
                    $param_image_path = $event_image_path_for_db; 

                    if (!$stmt_event->execute()) {
                        throw new Exception("Failed to create event: " . $stmt_event->error);
                    }
                    $new_event_id = $conn->insert_id;
                    $stmt_event->close();
                    $event_inserted = true;
                } else {
                    throw new Exception("Database error preparing event insertion: " . $conn->error);
                }

                // Conditionally insert into event_ticket_types or default "General" type
                if ($enable_seating_options === 'on') {
                    $sql_ticket_type = "INSERT INTO event_ticket_types (event_id, type_name, price, available_qty) VALUES (?, ?, ?, ?)";
                    if ($stmt_ticket_type = $conn->prepare($sql_ticket_type)) {
                        foreach ($processed_ticket_types as $type) {
                            $stmt_ticket_type->bind_param(
                                "isdi", // integer, string, decimal, integer
                                $new_event_id,
                                $type['name'],
                                $type['price'],
                                $type['quantity']
                            );
                            if (!$stmt_ticket_type->execute()) {
                                throw new Exception("Failed to add ticket type '" . htmlspecialchars($type['name']) . "': " . $stmt_ticket_type->error);
                            }
                        }
                        $stmt_ticket_type->close();
                    } else {
                        throw new Exception("Database error preparing ticket type insertion: " . $conn->error);
                    }
                } else { // Seating options NOT enabled: Insert a single "General" ticket type
                    $sql_default_type = "INSERT INTO event_ticket_types (event_id, type_name, price, available_qty) VALUES (?, 'General', ?, ?)";
                    if ($stmt_default_type = $conn->prepare($sql_default_type)) {
                        $stmt_default_type->bind_param(
                            "idi", // integer, decimal, integer
                            $new_event_id,
                            $single_ticket_price_for_db,
                            $total_tickets_sum // This is the single quantity from default field
                        );
                        if (!$stmt_default_type->execute()) {
                            throw new Exception("Failed to add default ticket type: " . $stmt_default_type->error);
                        }
                        $stmt_default_type->close();
                    } else {
                        throw new Exception("Database error preparing default ticket type insertion: " . $conn->error);
                    }
                }

                // If everything succeeded, commit the transaction
                $conn->commit();
                $message = "<div class='success-msg'>Event submitted successfully with all ticket details and is pending admin approval.</div>";
                
                // Clear form fields on success
                $event_title = $description = $event_date = $event_time = $location_full_string = $category = "";
                $province = $city = ""; 
                $default_single_ticket_price = "";
                $default_single_ticket_quantity = "";
                $enable_seating_options = 'off'; // Reset checkbox state
                $ticket_types_data = []; 
            } catch (Exception $e) {
                // If an error occurred, rollback the transaction
                $conn->rollback();
                $message = "<div class='error-msg'>Error during event creation: " . htmlspecialchars($e->getMessage()) . "</div>";
                // Optionally delete uploaded image if event insertion failed (only if it's not default)
                if ($event_inserted && $event_image_path_for_db !== $default_image_path && file_exists($upload_dir . basename($event_image_path_for_db))) {
                    unlink($upload_dir . basename($event_image_path_for_db));
                }
            }
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Event - Mero Events</title>
    <link rel="stylesheet" href="../assets/css/style.css"> 
    <style>
        /* Define Color Palette for this page's specific elements/overrides */
        :root {
            --primary-color: #ff6b6b;   /* Reddish-orange */
            --secondary-color: #1dd1a1; /* Teal green */
            --accent-color: #feca57;    /* Yellow-orange */
            --background-color: #f1f2f6;
            --text-color: #2f3542;      /* Dark text */
            --light-text-color: #666666; /* Lighter gray for secondary text */
            --white: #ffffff;
            --border-color: #ddd;
            --shadow-color: rgba(0,0,0,0.05);

            /* Navbar specific colors */
            --navbar-bg: #ffffff;
            --navbar-border: #f0f0f0; 
            --navbar-logo-color: #6A5ACD; 
            --navbar-link-color: #666666; 
            --navbar-dashboard-btn-bg: #4a90e2; 
            --navbar-logout-btn-bg: #e04444; 
            --navbar-btn-text-color: #ffffff; 
        }

        body {
            font-family: Arial, sans-serif;
            background-color: var(--background-color);
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
            align-items: center;
        }

        .event-form-container {
            background-color: var(--white); 
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-color); 
            width: 100%;
            max-width: 600px;
            margin: auto;
        }

        .event-form-container h2 {
            text-align: center;
            color: var(--text-color); 
            margin-bottom: 30px;
            font-size: 2em;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color); 
            font-weight: bold;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group input[type="time"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea,
        .form-group input[type="file"] { 
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color); 
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box;
            background-color: var(--background-color); 
            color: var(--text-color); 
        }
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus,
        .form-group input[type="file"]:focus {
            border-color: var(--accent-color); 
            outline: none;
            box-shadow: 0 0 0 3px rgba(254,202,87,0.25); 
        }

        /* Toggle switch for "Enable Seating Options" */
        .toggle-switch-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 10px 0;
            border-bottom: 1px dashed var(--border-color);
        }
        .toggle-switch-wrapper label {
            margin-bottom: 0; /* Override form-group label */
            font-weight: bold;
            color: var(--text-color);
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 50px; /* Wider switch */
            height: 28px; /* Taller switch */
        }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc; transition: .4s; border-radius: 28px; /* Rounded slider */
        }
        .slider:before {
            position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px;
            background-color: var(--white); transition: .4s; border-radius: 50%;
        }
        input:checked + .slider { background-color: var(--secondary-color); }
        input:checked + .slider:before { transform: translateX(22px); /* Move further */ }

        /* Dynamic Ticket Types Section */
        .ticket-types-section {
            border: 1px dashed var(--border-color);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            /* Controlled by JavaScript for visibility */
        }
        .ticket-types-section h3 {
            font-size: 1.3em;
            color: var(--text-color);
            margin-top: 0;
            margin-bottom: 15px;
            border-bottom: 1px solid var(--background-color);
            padding-bottom: 10px;
        }
        .ticket-type-item {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: flex-end;
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid var(--background-color);
            border-radius: 5px;
        }
        .ticket-type-item .form-group-inline {
            flex: 1; 
            min-width: 120px; 
            margin-bottom: 0; 
        }
        .ticket-type-item .form-group-inline label {
            font-size: 0.85em;
            margin-bottom: 5px;
            font-weight: normal;
        }
        .ticket-type-item .form-group-inline input {
            padding: 8px; 
            font-size: 0.9em;
            background-color: var(--white);
        }
        .ticket-type-item .remove-type-btn {
            background-color: var(--primary-color); 
            color: var(--white);
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: bold;
            transition: background-color 0.2s ease;
            height: 38px; /* Align with input height */
        }
        .ticket-type-item .remove-type-btn:hover {
            background-color: #e65a5a;
        }
        .add-type-btn {
            background-color: var(--navbar-dashboard-btn-bg); 
            color: var(--white);
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.95em;
            font-weight: bold;
            transition: background-color 0.2s ease;
            width: auto;
            margin-top: 10px;
        }
        .add-type-btn:hover {
            background-color: #357bd8;
        }

        /* Default Ticket Fields (when seating options are off) */
        #default-ticket-fields {
            /* Controlled by JavaScript for visibility */
        }
        #default-ticket-fields .form-group label {
            color: var(--text-color);
            font-weight: bold;
        }
        #default-ticket-fields .form-group input {
            background-color: var(--background-color);
            color: var(--text-color);
        }


        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: var(--secondary-color); 
            color: var(--white);
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px; 
        }
        .btn-submit:hover {
            background-color: #17b38c; 
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
        
        /* Header/Footer styles (consistent with other pages) */
        .main-header {
            background-color: var(--navbar-bg);
            box-shadow: 0 2px 5px var(--shadow-color);
            border-bottom: 1px solid var(--navbar-border);
            padding: 15px 0;
        }
        .main-nav { display: flex; justify-content: space-between; align-items: center; }
        .site-logo { font-size: 1.8em; font-weight: bold; color: var(--navbar-logo-color); margin-right: 20px; text-decoration: none; }
        .site-logo:hover { color: var(--navbar-logo-color); opacity: 0.9; } 
        .nav-links { list-style: none; display: flex; align-items: center; margin: 0; padding: 0; gap: 25px; }
        .nav-links li { margin-left: 0; }
        .nav-links a { color: var(--navbar-link-color); font-weight: 500; padding: 5px 0; transition: color 0.3s ease; text-decoration: none; }
        .nav-links a:not(.btn-navbar):hover { color: var(--navbar-logo-color); }
        .welcome-message { color: var(--navbar-link-color); font-weight: 500; margin-right: 15px; white-space: nowrap; }
        
        .btn-navbar {
            display: inline-block;
            padding: 8px 18px; 
            border-radius: 8px; 
            font-weight: bold;
            font-size: 0.95em; 
            text-align: center;
            text-decoration: none;
            transition: background-color 0.2s ease, opacity 0.2s ease;
            color: var(--navbar-btn-text-color); 
            border: none;
        }
        .btn-navbar.dashboard { background-color: var(--navbar-dashboard-btn-bg); }
        .btn-navbar.logout { background-color: var(--navbar-logout-btn-bg); }
        .btn-navbar:hover { opacity: 0.9; }

        .main-footer {
            background-color: var(--text-color); 
            color: var(--white);
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

        /* Responsive adjustments */
        @media (max-width: 768px) {
            main { padding: 20px 15px; }
            .event-form-container { padding: 30px; max-width: 95%; }
            .event-form-container h2 { font-size: 1.8em; }
            .form-group input, .form-group select, .form-group textarea, .form-group input[type="file"] {
                padding: 10px; font-size: 0.9em;
            }
            .ticket-type-item { flex-direction: column; align-items: stretch; }
            .ticket-type-item .form-group-inline { width: 100%; min-width: unset; }
            .ticket-type-item .remove-type-btn { width: 100%; margin-top: 10px; }
            .add-type-btn { width: 100%; }
            .btn-submit { font-size: 1em; padding: 10px; }

            /* Navbar adjustments */
            .main-nav { flex-direction: column; gap: 10px; align-items: flex-start; padding: 0 15px; }
            .site-logo { margin-bottom: 5px; }
            .nav-links { flex-wrap: wrap; justify-content: flex-start; gap: 10px; width: 100%; }
            .nav-links li { margin-left: 0; }
            .welcome-message { margin-right: 0; width: 100%; text-align: center; }
            .btn-navbar.dashboard, .btn-navbar.logout { margin-left: 0; width: auto; flex-grow: 1; }
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
                    $dashboard_link = 'dashboard.php'; 

                    echo '<li class="welcome-message">Welcome, ' . htmlspecialchars($_SESSION["user_name"]) . '</li>';
                    echo '<li><a href="' . htmlspecialchars($dashboard_link) . '" class="btn-navbar dashboard">Dashboard</a></li>';
                    echo '<li><a href="../logout.php" class="btn-navbar logout">Logout</a></li>'; 
                    ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="event-form-container">
            <h2>Create New Event</h2>
            
            <?php 
            if (!empty($message)) {
                echo $message;
            }
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="event_title">Event Title:</label>
                    <input type="text" id="event_title" name="event_title" value="<?php echo htmlspecialchars($event_title); ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" required><?php echo htmlspecialchars($description); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="event_date">Date:</label>
                    <input type="date" id="event_date" name="event_date" value="<?php echo htmlspecialchars($event_date); ?>" required>
                </div>
                <div class="form-group">
                    <label for="event_time">Time:</label>
                    <input type="time" id="event_time" name="event_time" value="<?php echo htmlspecialchars($event_time); ?>" required>
                </div>
                <!-- Location dropdowns -->
                <div class="form-group">
                    <label for="province">Province:</label>
                    <select id="province" name="province" required>
                        <option value="">--Select Province--</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="city">City:</label>
                    <select id="city" name="city" required disabled>
                        <option value="">Select Province First</option>
                    </select>
                </div>
                <!-- End Location dropdowns -->
                <div class="form-group">
                    <label for="category">Category:</label>
                    <select id="category" name="category" required>
                        <option value="">--Select Category--</option>
                        <option value="Education" <?php echo ($category == 'Education') ? 'selected' : ''; ?>>Education</option>
                        <option value="Technology" <?php echo ($category == 'Technology') ? 'selected' : ''; ?>>Technology</option>
                        <option value="Community" <?php echo ($category == 'Community') ? 'selected' : ''; ?>>Community</option>
                        <option value="Entertainment" <?php echo ($category == 'Entertainment') ? 'selected' : ''; ?>>Entertainment</option>
                    </select>
                </div>
                
                <!-- NEW: Enable Seating Options Checkbox -->
                <div class="toggle-switch-wrapper">
                    <label for="enable_seating_options">Enable Seating Options:</label>
                    <label class="switch">
                        <input type="checkbox" id="enable_seating_options" name="enable_seating_options" <?php echo ($enable_seating_options === 'on') ? 'checked' : ''; ?>>
                        <span class="slider round"></span>
                    </label>
                </div>

                <!-- Default Single Ticket Fields (initially visible if checkbox unchecked) -->
                <div id="default-ticket-fields">
                    <div class="form-group">
                        <label for="default_single_ticket_price">Price Per Ticket (Default):</label>
                        <input type="number" id="default_single_ticket_price" name="default_single_ticket_price" step="0.01" min="0" value="<?php echo htmlspecialchars($default_single_ticket_price); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="default_single_ticket_quantity">Total Default Tickets Available:</label>
                        <input type="number" id="default_single_ticket_quantity" name="default_single_ticket_quantity" min="1" value="<?php echo htmlspecialchars($default_single_ticket_quantity); ?>" required>
                    </div>
                </div>

                <!-- Dynamic Ticket Types Section (initially hidden if checkbox unchecked) -->
                <div id="ticket-types-section" class="ticket-types-section" style="display: none;">
                    <h3>Specific Ticket Types</h3>
                    <div id="ticket-types-container">
                        <?php 
                        // Repopulate dynamic fields on error
                        if (!empty($ticket_types_data)): 
                            foreach ($ticket_types_data as $index => $type): ?>
                                <div class="ticket-type-item">
                                    <div class="form-group-inline">
                                        <label>Type Name:</label>
                                        <input type="text" name="ticket_type_name[]" value="<?php echo htmlspecialchars($type['name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-group-inline">
                                        <label>Price:</label>
                                        <input type="number" name="ticket_type_price[]" step="0.01" min="0" value="<?php echo htmlspecialchars($type['price'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-group-inline">
                                        <label>Quantity:</label>
                                        <input type="number" name="ticket_type_quantity[]" min="0" value="<?php echo htmlspecialchars($type['quantity'] ?? ''); ?>" required>
                                    </div>
                                    <button type="button" class="remove-type-btn">Remove</button>
                                </div>
                            <?php endforeach; 
                        else: // Show one empty row initially if needed, if seating options were checked previously ?>
                            <div class="ticket-type-item">
                                <div class="form-group-inline">
                                    <label>Type Name:</label>
                                    <input type="text" name="ticket_type_name[]" required>
                                </div>
                                <div class="form-group-inline">
                                    <label>Price:</label>
                                    <input type="number" name="ticket_type_price[]" step="0.01" min="0" required>
                                </div>
                                <div class="form-group-inline">
                                    <label>Quantity:</label>
                                    <input type="number" name="ticket_type_quantity[]" min="0" required>
                                </div>
                                <button type="button" class="remove-type-btn" style="display:none;">Remove</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" id="add-ticket-type-btn" class="add-type-btn">Add Ticket Type</button>
                </div>

                <div class="form-group">
                    <label for="event_image">Upload Event Image (.jpg, .jpeg, .png, .webp, max 2MB):</label>
                    <input type="file" id="event_image" name="event_image" accept=".jpg, .jpeg, .png, .webp">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn-submit">Create Event</button>
                </div>
            </form>
        </div>
    </main>

    <footer class="main-footer">
        <div class="container">
            <p>© 2023 Mero Events. All rights reserved.</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const provinceSelect = document.getElementById('province');
            const citySelect = document.getElementById('city');
            const enableSeatingOptionsCheckbox = document.getElementById('enable_seating_options');
            const defaultTicketFields = document.getElementById('default-ticket-fields');
            const ticketTypesSection = document.getElementById('ticket-types-section');
            const ticketTypesContainer = document.getElementById('ticket-types-container');
            const addTicketTypeBtn = document.getElementById('add-ticket-type-btn');

            // --- Province/City Dynamic Dropdowns ---
            function loadProvinces() {
                fetch('../api/load-provinces.php') 
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(provinces => {
                        provinceSelect.innerHTML = '<option value="">--Select Province--</option>'; 
                        provinces.forEach(province => {
                            const option = document.createElement('option');
                            option.value = province;
                            option.textContent = province;
                            provinceSelect.appendChild(option);
                        });

                        const previouslySelectedProvince = "<?php echo htmlspecialchars($province); ?>";
                        if (previouslySelectedProvince) {
                            provinceSelect.value = previouslySelectedProvince;
                            loadCities(previouslySelectedProvince); 
                        }
                    })
                    .catch(error => {
                        console.error('Error loading provinces:', error);
                        provinceSelect.innerHTML = '<option value="">Error loading provinces</option>';
                        citySelect.innerHTML = '<option value="">Error loading cities</option>';
                    });
            }

            function loadCities(selectedProvince) {
                if (!selectedProvince) {
                    citySelect.innerHTML = '<option value="">Select Province First</option>';
                    citySelect.disabled = true;
                    return;
                }

                citySelect.innerHTML = '<option value="">Loading Cities...</option>';
                citySelect.disabled = true; 

                fetch(`../api/load-cities.php?province=${encodeURIComponent(selectedProvince)}`) 
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(cities => {
                        citySelect.innerHTML = '<option value="">--Select City--</option>'; 
                        if (cities.length > 0) {
                            cities.forEach(city => {
                                const option = document.createElement('option');
                                option.value = city;
                                option.textContent = city;
                                citySelect.appendChild(option);
                            });
                            citySelect.disabled = false; 

                            const previouslySelectedCity = "<?php echo htmlspecialchars($city); ?>";
                            if (previouslySelectedCity) {
                                citySelect.value = previouslySelectedCity;
                            }
                        } else {
                            citySelect.innerHTML = '<option value="">No cities found</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading cities:', error);
                        citySelect.innerHTML = '<option value="">Error loading cities</option>';
                    });
            }

            provinceSelect.addEventListener('change', function() {
                loadCities(this.value);
            });

            loadProvinces(); // Initial load of provinces

            // --- Conditional Seating Options Logic ---

            function toggleTicketFieldsVisibility() {
                if (enableSeatingOptionsCheckbox.checked) {
                    defaultTicketFields.style.display = 'none';
                    ticketTypesSection.style.display = 'block';
                    // Make dynamic fields required, default fields NOT required
                    ticketTypesContainer.querySelectorAll('input').forEach(input => input.setAttribute('required', 'required'));
                    defaultTicketFields.querySelectorAll('input').forEach(input => input.removeAttribute('required'));
                } else {
                    defaultTicketFields.style.display = 'block';
                    ticketTypesSection.style.display = 'none';
                    // Make default fields required, dynamic fields NOT required
                    defaultTicketFields.querySelectorAll('input').forEach(input => input.setAttribute('required', 'required'));
                    ticketTypesContainer.querySelectorAll('input').forEach(input => input.removeAttribute('required'));
                }
                updateRemoveButtons(); 
            }

            // Function to manage remove button visibility based on count
            function updateRemoveButtons() {
                const removeButtons = ticketTypesContainer.querySelectorAll('.remove-type-btn');
                if (removeButtons.length <= 1) { 
                    if (removeButtons[0]) removeButtons[0].style.display = 'none';
                } else {
                    removeButtons.forEach(btn => btn.style.display = 'inline-block');
                }
            }

            // Function to add a new ticket type item row
            function addTicketTypeItem(name = '', price = '', quantity = '') {
                const newTicketTypeHtml = `
                    <div class="ticket-type-item">
                        <div class="form-group-inline">
                            <label>Type Name:</label>
                            <input type="text" name="ticket_type_name[]" value="${name}" required>
                        </div>
                        <div class="form-group-inline">
                            <label>Price:</label>
                            <input type="number" name="ticket_type_price[]" step="0.01" min="0" value="${price}" required>
                        </div>
                        <div class="form-group-inline">
                            <label>Quantity:</label>
                            <input type="number" name="ticket_type_quantity[]" min="0" value="${quantity}" required>
                        </div>
                        <button type="button" class="remove-type-btn">Remove</button>
                    </div>
                `;
                ticketTypesContainer.insertAdjacentHTML('beforeend', newTicketTypeHtml);
                updateRemoveButtons();
            }

            // Event listeners for toggle and dynamic fields
            enableSeatingOptionsCheckbox.addEventListener('change', toggleTicketFieldsVisibility);
            addTicketTypeBtn.addEventListener('click', function() {
                addTicketTypeItem();
            });

            ticketTypesContainer.addEventListener('click', function(event) {
                if (event.target.classList.contains('remove-type-btn')) {
                    if (ticketTypesContainer.querySelectorAll('.ticket-type-item').length > 1) {
                        event.target.closest('.ticket-type-item').remove();
                        updateRemoveButtons();
                    } else {
                        alert('You must have at least one ticket type if seating options are enabled.');
                    }
                }
            });

            // Initial call to set visibility based on PHP's $enable_seating_options on page load
            toggleTicketFieldsVisibility(); 
        });
    </script>
</body>
</html>