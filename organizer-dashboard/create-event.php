<?php
// organizer-dashboard/create-event.php

// 5. Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Include session protection to ensure only logged-in organizers can access this page
require_once '../includes/session-organizer.php'; // Path to session-organizer.php from current location
require_once '../includes/config.php';          // Path to config.php from current location

// Initialize variables for form data and messages
$event_title = $description = $event_date = $event_time = $location = $category = "";
$message = "";

// Get the organizer's ID from the session (guaranteed to be set by session-organizer.php)
$organizer_id = $_SESSION["user_id"];

// 6. Place the form handler and insert logic at the top
// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 2. Get and sanitize input from the form
    $event_title = trim($_POST["event_title"]);
    $description = trim($_POST["description"]);
    $event_date = trim($_POST["event_date"]);
    $event_time = trim($_POST["event_time"]);
    $location = trim($_POST["location"]);
    $category = trim($_POST["category"]);

    // 3. Server-side validation: All fields must be filled
    if (empty($event_title) || empty($description) || empty($event_date) || empty($event_time) || empty($location) || empty($category)) {
        $message = "<div class='error-msg'>All fields are required.</div>";
    } else {
        // 1. Insert all data into a MySQL table named `events` using prepared statements.
        // `organizer_id` from $_SESSION['user_id']
        // `status` defaults to 'pending' as per table definition, explicitly set here for clarity
        // Note: 'image_path' column is removed as per the new `events` table structure provided.
        $sql = "INSERT INTO events (title, description, event_date, event_time, location, category, organizer_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";

        if ($stmt = $conn->prepare($sql)) {
            // Bind parameters to the prepared statement
            // s: string, i: integer (for organizer_id)
            $stmt->bind_param(
                "ssssssi", // 6 strings for title, desc, date, time, loc, category; 1 integer for organizer_id
                $param_title,
                $param_description,
                $param_event_date,
                $param_event_time,
                $param_location,
                $param_category,
                $param_organizer_id
            );

            // Set parameter values
            $param_title = $event_title;
            $param_description = $description;
            $param_event_date = $event_date;
            $param_event_time = $event_time;
            $param_location = $location;
            $param_category = $category;
            $param_organizer_id = $organizer_id; // Retrieved from session

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // 3. Show a success message
                $message = "<div class='success-msg'>Event created successfully! It is now pending approval.</div>";
                
                // Clear form fields after successful submission (since we don't redirect)
                $event_title = $description = $event_date = $event_time = $location = $category = "";
            } else {
                // 3. Show the exact error if it fails
                $message = "<div class='error-msg'>Error: Could not create event. " . $stmt->error . "</div>";
            }

            // Close statement
            $stmt->close();
        } else {
            $message = "<div class='error-msg'>Database error: Could not prepare statement for event insertion.</div>";
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
    <!-- 4. Link external stylesheet -->
    <link rel="stylesheet" href="../assets/css/style.css"> 
    <style>
        /* 4. Basic styling to center the form in a card/box */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh; /* Ensures footer stays at bottom */
        }
        main {
            flex-grow: 1; /* Allows main content to fill available space */
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center; /* Vertically center the content */
        }

        .event-form-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px; /* Wider form for more content */
            margin: auto; /* Center the container */
        }

        .event-form-container h2 {
            text-align: center;
            color: #333;
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
            color: #555;
            font-weight: bold;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group input[type="time"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box; /* Ensures padding doesn't increase width */
        }

        .form-group textarea {
            resize: vertical; /* Allow vertical resizing for description */
            min-height: 120px; /* Minimum height for textarea */
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: #28a745; /* A pleasant green for 'create' action */
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-submit:hover {
            background-color: #218838;
        }

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
        <div class="event-form-container">
            <h2>Create New Event</h2>
            
            <?php 
            // Display message if set
            if (!empty($message)) {
                echo $message;
            }
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
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