<?php
// organizer-dashboard/edit-event.php

// Enable full error reporting at the top
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Use session protection
require_once '../includes/session-organizer.php';
require_once '../includes/config.php';

// Initialize variables
$event_id = null;
$event_title = $description = $event_date = $event_time = $location = $category = "";
$total_tickets = ""; // Also fetch total_tickets
$message = "";
$organizer_id = $_SESSION["user_id"];

// Directory for event image uploads (relative to project root from edit-event.php)
$upload_dir = '../event-images/'; 
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
$default_image_path = 'event-images/default.jpg'; 
$current_image_path = ''; // To store the image path currently in DB

// --- Handle GET Request (Initial Load) ---
// Accept the event id via GET: event_id
if (isset($_GET['event_id']) && is_numeric($_GET['event_id'])) {
    $event_id = $_GET['event_id'];

    // Fetch existing event details from the database
    // Verify that the event belongs to the logged-in organizer
    // Also fetch total_tickets and image_path
    $sql = "SELECT title, description, event_date, event_time, location, category, total_tickets, image_path FROM events WHERE id = ? AND organizer_id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $event_id, $organizer_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $event_data = $result->fetch_assoc();
                $event_title = $event_data['title'];
                $description = $event_data['description'];
                $event_date = $event_data['event_date'];
                $event_time = $event_data['event_time'];
                $location = $event_data['location'];
                $category = $event_data['category'];
                $total_tickets = $event_data['total_tickets']; // Populate total_tickets
                $current_image_path = $event_data['image_path']; // Store current image path
            } else {
                $message = "<div class='error-msg'>Event not found or you don't have permission to edit it.</div>";
                $event_id = null; // Invalidate ID to prevent showing form
            }
        } else {
            $message = "<div class='error-msg'>Error fetching event details: " . $stmt->error . "</div>";
            $event_id = null;
        }
        $stmt->close();
    } else {
        $message = "<div class='error-msg'>Database error: Could not prepare fetch statement.</div>";
        $event_id = null;
    }
} else {
    $message = "<div class='error-msg'>No event ID provided or invalid ID.</div>";
    $event_id = null;
}


// --- Handle POST Request (Form Submission) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_id_to_update = trim($_POST["event_id"]); // From hidden field
    
    // Re-populate variables for sticky form fields in case of validation errors
    $event_title = trim($_POST["event_title"]);
    $description = trim($_POST["description"]);
    $event_date = trim($_POST["event_date"]);
    $event_time = trim($_POST["event_time"]);
    $location = trim($_POST["location"]);
    $category = trim($_POST["category"]);
    $total_tickets = trim($_POST["total_tickets"]);
    
    // Get original image path for fallback if no new image is uploaded
    $image_path_for_db = trim($_POST['current_image_path']); 
    $upload_error = false;

    // Validate inputs
    if (empty($event_id_to_update) || !is_numeric($event_id_to_update) || empty($event_title) || empty($description) || empty($event_date) || empty($event_time) || empty($location) || empty($category) || empty($total_tickets)) {
        $message = "<div class='error-msg'>All fields are required and a valid Event ID must be present.</div>";
    } elseif (!is_numeric($total_tickets) || $total_tickets < 1) {
        $message = "<div class='error-msg'>Total Tickets Available must be a positive number.</div>";
    } else {
        // Handle new image upload
        if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] == UPLOAD_ERR_OK) {
            $file_name = $_FILES['event_image']['name'];
            $file_tmp_name = $_FILES['event_image']['tmp_name'];
            $file_size = $_FILES['event_image']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_extensions = array("jpg", "jpeg", "png", "webp");
            $max_file_size = 2 * 1024 * 1024; // 2 MB

            if (!in_array($file_ext, $allowed_extensions)) {
                $message = "<div class='error-msg'>Invalid image file type. Only JPG, JPEG, PNG, WEBP are allowed.</div>";
                $upload_error = true;
            } elseif ($file_size > $max_file_size) {
                $message = "<div class='error-msg'>Image file is too large (max 2MB).</div>";
                $upload_error = true;
            } else {
                $new_file_name = uniqid('event_img_', true) . '.' . $file_ext;
                $destination = $upload_dir . $new_file_name;
                $image_path_for_db = 'event-images/' . $new_file_name;

                if (!move_uploaded_file($file_tmp_name, $destination)) {
                    $message = "<div class='error-msg'>Failed to upload new image. Check permissions.</div>";
                    $upload_error = true;
                } else {
                    // Optional: Delete old image if it's not the default
                    if (!empty($_POST['current_image_path']) && $_POST['current_image_path'] !== $default_image_path && file_exists('../' . $_POST['current_image_path'])) {
                        unlink('../' . $_POST['current_image_path']);
                    }
                }
            }
        } elseif (isset($_FILES['event_image']) && $_FILES['event_image']['error'] != UPLOAD_ERR_NO_FILE) {
            $message = "<div class='error-msg'>An upload error occurred for new image: Code " . $_FILES['event_image']['error'] . "</div>";
            $upload_error = true;
        }

        // Only proceed with DB update if no validation or upload errors
        if (empty($message) && !$upload_error) {
            // Set status back to 'pending' after any edit (as per common requirement)
            $sql = "UPDATE events SET title = ?, description = ?, event_date = ?, event_time = ?, location = ?, category = ?, total_tickets = ?, status = 'pending', image_path = ? WHERE id = ? AND organizer_id = ?";

            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param(
                    "ssssssiis", // 6 strings, 1 int, 1 int (total_tickets), 1 string (image_path), 1 int (id), 1 int (organizer_id)
                    $param_title,
                    $param_description,
                    $param_event_date,
                    $param_event_time,
                    $param_location,
                    $param_category,
                    $param_total_tickets,
                    $param_image_path, // New parameter
                    $param_event_id,
                    $param_organizer_id
                );

                $param_title = $event_title;
                $param_description = $description;
                $param_event_date = $event_date;
                $param_event_time = $event_time;
                $param_location = $location;
                $param_category = $category;
                $param_total_tickets = $total_tickets;
                $param_image_path = $image_path_for_db; // Use new or existing path
                $param_event_id = $event_id_to_update; 
                $param_organizer_id = $organizer_id; 

                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $message = "<div class='success-msg'>Event updated successfully! Status set to 'pending' for re-approval.</div>";
                    } else {
                        $message = "<div class='info-msg'>No changes made or event not found under your account.</div>";
                    }
                } else {
                    $message = "<div class='error-msg'>Error updating event: " . $stmt->error . "</div>";
                }
                $stmt->close();
            } else {
                $message = "<div class='error-msg'>Database error: Could not prepare update statement.</div>";
            }
        }
    }
    // Set event_id for re-fetching after POST to show updated values
    $event_id = $event_id_to_update; // Set for current display
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - Mero Events</title>
    <link rel="stylesheet" href="../assets/css/style.css"> 
    <style>
        /* Reusing common styles from other pages for form layout */
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
            align-items: center;
        }

        .event-form-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            margin: auto;
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
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea,
        .form-group input[type="file"] { /* Style for file input */
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box;
            background-color: #f4f7f6; /* Consistent with other forms */
        }
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus,
        .form-group input[type="file"]:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: #007bff; /* Blue for edit action */
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn-submit:hover {
            background-color: #0056b3;
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
        .info-msg { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

        /* Preview for current image */
        .current-image-preview {
            max-width: 150px;
            height: auto;
            display: block;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        /* Header/Footer styles (consistent with other pages) */
        .main-header { background-color: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); padding: 15px 0; }
        .main-nav { display: flex; justify-content: space-between; align-items: center; }
        .site-logo { font-size: 1.8em; font-weight: bold; color: #333; margin-right: 20px; text-decoration: none; }
        .site-logo:hover { color: #007bff; }
        .nav-links { list-style: none; display: flex; align-items: center; margin: 0; padding: 0; }
        .nav-links li { margin-left: 25px; }
        .nav-links a { color: #555; font-weight: 500; padding: 5px 0; transition: color 0.3s ease; text-decoration: none; }
        .nav-links a:not(.btn-navbar):hover { color: #007bff; }
        .welcome-message { color: #555; font-weight: 500; margin-right: 15px; white-space: nowrap; }
        .btn-navbar { display: inline-block; padding: 8px 18px; border-radius: 8px; font-weight: bold; font-size: 0.95em; text-align: center; text-decoration: none; transition: background-color 0.2s ease, opacity 0.2s ease; color: #fff; border: none; }
        .btn-navbar.dashboard { background-color: #4a90e2; }
        .btn-navbar.logout { background-color: #e04444; }
        .btn-navbar:hover { opacity: 0.9; }
        .main-footer { background-color: #333; color: #fff; text-align: center; padding: 25px 0; font-size: 0.9em; margin-top: auto; width: 100%; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
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
            <h2>Edit Event</h2>
            
            <?php 
            if (!empty($message)) {
                echo $message;
            }
            ?>

            <?php if ($event_id !== null): /* Only show form if a valid event was loaded */ ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?event_id=<?php echo htmlspecialchars($event_id); ?>" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event_id); ?>">
                    <input type="hidden" name="current_image_path" value="<?php echo htmlspecialchars($current_image_path); ?>">

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
                        <label>Current Event Image:</label>
                        <?php 
                        // Display current image preview
                        $current_img_src = !empty($current_image_path) ? htmlspecialchars($current_image_path) : 'event-images/default.jpg';
                        ?>
                        <img src="../<?php echo $current_img_src; ?>" alt="Current Event Image" class="current-image-preview">
                        <label for="event_image" style="margin-top: 15px;">Change Event Image (.jpg, .jpeg, .png, .webp, max 2MB):</label>
                        <input type="file" id="event_image" name="event_image" accept=".jpg, .jpeg, .png, .webp">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn-submit">Update Event</button>
                    </div>
                </form>
            <?php else: ?>
                <p style="text-align: center;">Please go to <a href="manage-events.php">Manage Events</a> to select an event to edit.</p>
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