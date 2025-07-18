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
$event_title = $description = $event_date = $event_time = $location = $category = "";
$total_tickets = ""; 
$message = "";

// Directory for event image uploads (relative to project root from create-event.php)
$upload_dir = '../event-images/'; 
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true); // Create directory if it doesn't exist, with full permissions
}
$default_image_path = 'event-images/default.jpg'; // Path to store in DB if no upload

// Get the organizer's ID from the session (guaranteed to be set by session-organizer.php)
$organizer_id = $_SESSION["user_id"];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get and sanitize input from the form
    $event_title = trim($_POST["event_title"]);
    $description = trim($_POST["description"]);
    $event_date = trim($_POST["event_date"]);
    $event_time = trim($_POST["event_time"]);
    $location = trim($_POST["location"]);
    $category = trim($_POST["category"]);
    $total_tickets = trim($_POST["total_tickets"]);

    // Start with default image path; it will be updated if an image is successfully uploaded
    $event_image_path_for_db = $default_image_path; 
    $upload_error = false; // Flag to track upload-specific errors

    // 1. Validate inputs (all fields required, tickets >= 1)
    if (empty($event_title) || empty($description) || empty($event_date) || empty($event_time) || empty($location) || empty($category) || empty($total_tickets)) {
        $message = "<div class='error-msg'>All fields are required.</div>";
    } elseif (!is_numeric($total_tickets) || $total_tickets < 1) {
        $message = "<div class='error-msg'>Total Tickets Available must be a positive number.</div>";
    } else {
        // Handle image upload if a file was selected
        if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] == UPLOAD_ERR_OK) {
            $file_name = $_FILES['event_image']['name'];
            $file_tmp_name = $_FILES['event_image']['tmp_name'];
            $file_size = $_FILES['event_image']['size'];
            
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Validate the file type and size (e.g., max 2MB).
            $allowed_extensions = array("jpg", "jpeg", "png", "webp");
            $max_file_size = 2 * 1024 * 1024; // 2 MB in bytes

            if (!in_array($file_ext, $allowed_extensions)) {
                $message = "<div class='error-msg'>Invalid image file type. Only JPG, JPEG, PNG, WEBP are allowed.</div>";
                $upload_error = true;
            } elseif ($file_size > $max_file_size) {
                $message = "<div class='error-msg'>Image file is too large (max 2MB).</div>";
                $upload_error = true;
            } else {
                // Store the image in a folder named event-images/
                $new_file_name = uniqid('event_img_', true) . '.' . $file_ext;
                $destination = $upload_dir . $new_file_name; // Full server path
                $event_image_path_for_db = 'event-images/' . $new_file_name; // Path relative to project root for DB

                if (!move_uploaded_file($file_tmp_name, $destination)) {
                    $message = "<div class='error-msg'>Failed to upload event image. Please check directory permissions.</div>";
                    $upload_error = true;
                }
            }
        } elseif (isset($_FILES['event_image']) && $_FILES['event_image']['error'] != UPLOAD_ERR_NO_FILE) {
            // Handle other potential upload errors (e.g., UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE)
            $message = "<div class='error-msg'>An upload error occurred: Code " . $_FILES['event_image']['error'] . "</div>";
            $upload_error = true;
        }
        
        // Only proceed to insert into DB if no validation errors and no upload errors
        if (empty($message) && !$upload_error) {
            // Insert into `events` table
            // `tickets_booked` defaults to 0, `status` defaults to 'pending'
            $sql = "INSERT INTO events (title, description, event_date, event_time, location, category, total_tickets, tickets_booked, organizer_id, status, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, 'pending', ?)";

            if ($stmt = $conn->prepare($sql)) {
                // Bind parameters: 6 strings (basic fields), 1 int (total_tickets), 1 int (organizer_id), 1 string (image_path)
                $stmt->bind_param(
                    "ssssssiis", 
                    $param_title,
                    $param_description,
                    $param_event_date,
                    $param_event_time,
                    $param_location,
                    $param_category,
                    $param_total_tickets, 
                    $param_organizer_id,
                    $param_image_path // New parameter for image path
                );

                // Set parameter values
                $param_title = $event_title;
                $param_description = $description;
                $param_event_date = $event_date;
                $param_event_time = $event_time;
                $param_location = $location;
                $param_category = $category;
                $param_total_tickets = $total_tickets; 
                $param_organizer_id = $organizer_id; 
                $param_image_path = $event_image_path_for_db; // Store the image path

                // Attempt to execute the prepared statement
                if ($stmt->execute()) {
                    // Show success message
                    $message = "<div class='success-msg'>Event submitted successfully and is pending admin approval.</div>";
                    
                    // Clear form fields after successful submission
                    $event_title = $description = $event_date = $event_time = $location = $category = "";
                    $total_tickets = ""; 
                } else {
                    // Show error message
                    $message = "<div class='error-msg'>Error: Could not create event. " . $stmt->error . "</div>";
                }

                // Close statement
                $stmt->close();
            } else {
                $message = "<div class='error-msg'>Database error: Could not prepare statement for event insertion.</div>";
            }
        }
    }
    // Close database connection after all operations for this POST request
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Event - Mero Events</title>
    <!-- Link to your main CSS file -->
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

            /* Navbar specific colors (from previous prompt's image) */
            --navbar-bg: #ffffff;
            --navbar-border: #f0f0f0; 
            --navbar-logo-color: #2f3542; 
            --navbar-link-color: #666666; 
            --navbar-dashboard-btn-bg: #4a90e2; 
            --navbar-logout-btn-bg: #e04444; 
            --navbar-btn-text-color: #ffffff; 
        }

        /* Basic styling to center the form in a card/box (from previous common styles) */
        body {
            font-family: Arial, sans-serif;
            background-color: var(--background-color); /* Use variable */
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
            background-color: var(--white); /* Use variable */
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-color); /* Use variable */
            width: 100%;
            max-width: 600px;
            margin: auto;
        }

        .event-form-container h2 {
            text-align: center;
            color: var(--text-color); /* Use variable */
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
            color: var(--text-color); /* Use variable */
            font-weight: bold;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group input[type="time"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea,
        .form-group input[type="file"] { /* Style for file input */
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color); /* Use variable */
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box;
            background-color: var(--background-color); /* Light background for inputs */
            color: var(--text-color); /* Use variable for input text color */
        }
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus,
        .form-group input[type="file"]:focus {
            border-color: var(--accent-color); /* Use accent color on focus */
            outline: none;
            box-shadow: 0 0 0 3px rgba(254,202,87,0.25); /* Accent shadow */
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: var(--secondary-color); /* Green from palette */
            color: var(--white);
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn-submit:hover {
            background-color: #17b38c; /* Darker secondary on hover */
        }

        /* Message styling (reusing from common styles) */
        .message {
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        .success-msg { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-msg { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        /* Header/Footer styles (consistent with other pages, using variables) */
        .main-header {
            background-color: var(--navbar-bg);
            box-shadow: 0 2px 5px var(--shadow-color);
            border-bottom: 1px solid var(--navbar-border);
            padding: 15px 0;
        }
        .main-nav { display: flex; justify-content: space-between; align-items: center; }
        .site-logo { font-size: 1.8em; font-weight: bold; color: var(--navbar-logo-color); margin-right: 20px; text-decoration: none; }
        .site-logo:hover { color: var(--primary-color); } /* Keep primary hover as per general theme */
        .nav-links { list-style: none; display: flex; align-items: center; margin: 0; padding: 0; }
        .nav-links li { margin-left: 25px; }
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
            background-color: var(--text-color); /* Assuming footer is dark as per theme */
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
            // Display message if set
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
                <div class="form-group">
                    <label for="location">Location:</label>
                    <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($location); ?>" required>
                </div>
                <div class="form-group">
                    <label for="category">Category:</label>
                    <select id="category" name="category" required>
                        <option value="">Select Category</option>
                        <option value="Education" <?php echo ($category == 'Education') ? 'selected' : ''; ?>>Education</option>
                        <option value="Tech" <?php echo ($category == 'Tech') ? 'selected' : ''; ?>>Tech</option>
                        <option value="Community" <?php echo ($category == 'Community') ? 'selected' : ''; ?>>Community</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="total_tickets">Total Tickets Available:</label>
                    <input type="number" id="total_tickets" name="total_tickets" min="1" value="<?php echo htmlspecialchars($total_tickets); ?>" required>
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
</body>
</html>