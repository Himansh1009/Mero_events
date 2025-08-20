<?php
// contact.php
session_start(); // Start session to check login status for navbar

// Basic form handling (no email sending, just a placeholder message)
$contact_message = "";
$name = $_POST['name'] ?? ''; // Re-populate for sticky form
$email = $_POST['email'] ?? '';
$subject = $_POST['subject'] ?? '';
$message_content = $_POST['message'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $subject = htmlspecialchars(trim($_POST['subject'] ?? ''));
    $message_content = htmlspecialchars(trim($_POST['message'] ?? ''));

    if (empty($name) || empty($email) || empty($subject) || empty($message_content)) {
        $contact_message = "<div class='error-msg'>All fields are required.</div>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $contact_message = "<div class='error-msg'>Invalid email format.</div>";
    } else {
        // In a real application, you would send an email here
        $contact_message = "<div class='success-msg'>Thank you, " . $name . "! Your message has been received. We will get back to you shortly.</div>";
        // Clear form fields on success
        $name = $email = $subject = $message_content = "";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Mero Events</title>
    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Link to your main CSS file -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Define Color Palette for consistency with project theme */
        :root {
            /* General project colors */
            --primary-color: #ff6b6b;   /* Reddish-orange */
            --secondary-color: #1dd1a1; /* Teal green */
            --accent-color: #feca57;    /* Yellow-orange */
            --background-color: #f1f2f6;
            --text-color: #2f3542;      /* Dark text */
            --light-text-color: #666666; /* Lighter gray for secondary text */
            --white: #ffffff;
            --border-color: #ddd;
            --shadow-color: rgba(0,0,0,0.05);
            --hover-shadow-color: rgba(0,0,0,0.1);

            /* Navbar specific colors (from recent prompts) */
            --navbar-bg: #ffffff;
            --navbar-border: #f0f0f0; 
            --navbar-logo-color: #6A5ACD; /* Blue-purple from index.php's navbar image */
            --navbar-link-color: #666666; 
            --navbar-dashboard-btn-bg: #4a90e2; 
            --navbar-logout-btn-bg: #e04444; 
            --navbar-btn-text-color: #ffffff; 

            /* New Contact page specific colors (from provided screenshot) */
            --contact-hero-gradient-start: #6a5acd; /* Purple-blue */
            --contact-hero-gradient-end: #4a90e2;   /* Blue */
            --contact-hero-text-color: var(--white);
            
            --card-bg: var(--white);
            --card-shadow: rgba(0,0,0,0.08);
            --card-heading-color: #6a5acd; /* Purple-blue for card headings */
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

        /* --- Navbar (Consistent with site-wide theme) --- */
        .main-header {
            background-color: var(--navbar-bg);
            box-shadow: 0 2px 5px var(--shadow-color);
            border-bottom: 1px solid var(--navbar-border);
            padding: 15px 0;
        }
        .main-nav { 
            display: flex; justify-content: space-between; align-items: center; 
            max-width: 1200px; margin: 0 auto; padding: 0 20px; 
        }
        .site-logo { 
            font-size: 1.8em; font-weight: bold; color: var(--navbar-logo-color); 
            margin-right: 20px; text-decoration: none; flex-shrink: 0; 
        }
        .site-logo:hover { color: var(--navbar-logo-color); opacity: 0.9; }
        .nav-links { 
            list-style: none; display: flex; align-items: center; margin: 0; padding: 0; gap: 25px; 
        }
        .nav-links li { margin-left: 0; }
        .nav-links a { 
            color: var(--navbar-link-color); font-weight: 500; padding: 5px 0; text-decoration: none; 
            transition: color 0.2s ease; 
        }
        .nav-links a:hover:not(.btn-navbar) { color: var(--navbar-logo-color); }
        .welcome-message { color: var(--navbar-link-color); font-weight: 500; margin-right: 15px; white-space: nowrap; }
        .btn-navbar { 
            display: inline-block; padding: 8px 18px; border-radius: 8px; font-weight: bold; 
            font-size: 0.95em; text-align: center; text-decoration: none; 
            transition: background-color 0.2s ease, opacity 0.2s ease; 
            color: var(--navbar-btn-text-color); border: none; 
        }
        .btn-navbar.dashboard { background-color: var(--navbar-dashboard-btn-bg); margin-left: 10px; }
        .btn-navbar.logout { background-color: var(--navbar-logout-btn-bg); margin-left: 10px; }
        .btn-navbar:hover { opacity: 0.9; }

        /* --- Main Content Area --- */
        main {
            flex-grow: 1; /* For sticky footer */
        }

        /* Contact Hero Banner (Matching Screenshot) */
        .contact-hero-banner {
            background: linear-gradient(to right, var(--contact-hero-gradient-start), var(--contact-hero-gradient-end));
            padding: 80px 20px;
            text-align: center;
            color: var(--contact-hero-text-color);
            margin-bottom: 50px; /* Space below banner */
        }
        .contact-hero-banner h1 {
            font-size: 3em;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .contact-hero-banner p {
            font-size: 1.2em;
            opacity: 0.9;
        }

        /* Main Content Layout for Form and Info (Matching Screenshot) */
        .contact-content-wrapper {
            display: flex;
            flex-wrap: wrap; /* Allow columns to wrap */
            justify-content: center; /* Center columns */
            gap: 30px; /* Space between columns */
            max-width: 900px; /* Max width for content area */
            margin: 0 auto 50px auto; /* Center with space below */
            padding: 0 20px;
        }

        .contact-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 4px 15px var(--card-shadow);
            padding: 35px; /* Generous padding */
            flex: 1 1 calc(50% - 45px); /* Two columns, adjusted for gap */
            min-width: 300px; /* Min width before stacking */
            box-sizing: border-box;
            text-align: left; /* Align text within card */
        }
        .contact-card h2 {
            font-size: 1.8em;
            color: var(--card-heading-color);
            margin-top: 0;
            margin-bottom: 25px;
            font-weight: bold;
        }

        /* Contact Form */
        .contact-form .form-group {
            margin-bottom: 20px;
        }
        .contact-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--text-color);
            font-size: 0.95em;
        }
        .contact-form input[type="text"],
        .contact-form input[type="email"],
        .contact-form textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            background-color: var(--background-color); /* Light input background */
            color: var(--text-color);
            font-size: 1em;
            box-sizing: border-box;
            outline: none;
        }
        .contact-form textarea {
            min-height: 120px; /* Sufficient height for message */
            resize: vertical;
        }
        .contact-form input:focus,
        .contact-form textarea:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 2px rgba(254,202,87,0.2);
        }
        .contact-form .btn-submit {
            background-color: var(--primary-color); /* Reddish-orange for submit */
            color: var(--white);
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .contact-form .btn-submit:hover {
            background-color: #e65a5a; /* Darker primary on hover */
        }

        /* Our Information Section */
        .info-details p {
            margin-bottom: 15px;
            font-size: 1.1em;
            line-height: 1.5;
            color: var(--light-text-color);
        }
        .info-details p strong {
            color: var(--text-color);
            font-weight: bold;
            display: inline-block; /* Keep strong on same line with label */
            margin-right: 5px;
        }
        .info-details p i {
            color: var(--card-heading-color); /* Icon color */
            margin-right: 8px;
            font-size: 1.1em;
        }
        .info-details h3 { /* Business Hours heading */
            font-size: 1.4em;
            color: var(--card-heading-color);
            margin-top: 30px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        /* Message styling (reused) */
        .message {
            margin: 20px auto; 
            padding: 12px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            max-width: 600px;
        }
        .success-msg { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-msg { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        /* --- Footer (FROM includes/footer.php) --- */
        /* Assuming this content will be loaded from includes/footer.php and styled via style.css */
        .main-footer {
            background-color: #2f3542; 
            color: #e0e0e0; 
            padding: 60px 0 20px 0; 
            font-size: 0.95em;
            margin-top: auto; 
            width: 100%;
            box-sizing: border-box;
        }
        .main-footer .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .contact-hero-banner { padding: 60px 15px; }
            .contact-hero-banner h1 { font-size: 2.5em; }
            .contact-hero-banner p { font-size: 1em; }
            .contact-content-wrapper {
                flex-direction: column; /* Stack columns */
                align-items: center; /* Center stacked cards */
                gap: 20px; /* Reduce gap when stacked */
            }
            .contact-card {
                flex: 1 1 100%; /* Take full width */
                max-width: 450px; /* Max width for readability */
                padding: 30px;
            }
            .contact-card h2 { font-size: 1.6em; }
            /* Navbar adjustments */
            .main-nav { flex-direction: column; gap: 10px; align-items: flex-start; padding: 0 15px; }
            .site-logo { margin-bottom: 5px; }
            .nav-links { flex-wrap: wrap; justify-content: flex-start; gap: 10px; width: 100%; }
            .nav-links li { margin-left: 0; }
            .welcome-message { margin-right: 0; width: 100%; text-align: center; }
            .btn-navbar.dashboard, .btn-navbar.logout { margin-left: 0; width: auto; flex-grow: 1; }
        }
        @media (max-width: 480px) {
            .contact-hero-banner { padding: 40px 10px; }
            .contact-hero-banner h1 { font-size: 2em; }
            .contact-hero-banner p { font-size: 0.9em; }
            .contact-card { padding: 25px; }
            .contact-card h2 { font-size: 1.4em; }
            .contact-form input, .contact-form textarea { padding: 10px; font-size: 0.9em; }
            .contact-form .btn-submit { padding: 10px 20px; font-size: 1em; }
            .info-details p, .info-details h3 { font-size: 1em; }
            /* Navbar adjustments */
            .main-nav { padding: 10px; }
            .site-logo { font-size: 1.3em; }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <nav class="main-nav">
            <a href="index.php" class="site-logo">Mero Events</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Contact</a></li>
                
                <?php
                // Dynamic Login/Dashboard/Logout links (reused logic)
                if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
                    $dashboard_link = '#'; // Default fallback
                    if (isset($_SESSION["user_type"])) {
                        if ($_SESSION["user_type"] == "organizer") {
                            $dashboard_link = 'organizer-dashboard/dashboard.php';
                        } elseif ($_SESSION["user_type"] == "user") {
                            $dashboard_link = 'user-dashboard/dashboard.php';
                        }
                    }
                    echo '<li class="welcome-message">Welcome, ' . htmlspecialchars($_SESSION["user_name"]) . '</li>';
                    echo '<li><a href="' . htmlspecialchars($dashboard_link) . '" class="btn-navbar dashboard">Dashboard</a></li>';
                    echo '<li><a href="logout.php" class="btn-navbar logout">Logout</a></li>'; 
                } else {
                    echo '<li><a href="auth.php" class="btn-navbar dashboard">Login/Register</a></li>'; 
                }
                ?>
            </ul>
        </nav>
    </header>

    <main>
        <!-- Contact Hero Banner (Matching Screenshot) -->
        <section class="contact-hero-banner">
            <h1>Contact Us</h1>
            <p>Have questions? We'd love to hear from you!</p>
        </section>

        <!-- Main Contact Content Layout -->
        <div class="contact-content-wrapper">
            <!-- Send Us a Message Card -->
            <div class="contact-card">
                <h2>Send Us a Message</h2>
                <?php 
                if (!empty($contact_message)) {
                    echo $contact_message;
                }
                ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="contact-form">
                    <div class="form-group">
                        <label for="name">Your Name:</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Your Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject:</label>
                        <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($subject); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Message:</label>
                        <textarea id="message" name="message" required><?php echo htmlspecialchars($message_content); ?></textarea>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn-submit">Send Message</button>
                    </div>
                </form>
            </div>

            <!-- Our Information Card -->
            <div class="contact-card">
                <h2>Our Information</h2>
                <div class="info-details">
                    <p><i class="fa-solid fa-envelope"></i> <strong>Email:</strong> info@meroevents.com</p>
                    <p><i class="fa-solid fa-phone"></i> <strong>Phone:</strong> +977 9849011111</p>
                    <p><i class="fa-solid fa-location-dot"></i> <strong>Address:</strong> Kathmandu, Nepal</p>
                    
                    <h3>Business Hours</h3>
                    <p>Sunday - Friday: 9:00 AM - 5:00 PM</p>
                    <p>Saturday: Closed</p>
                </div>
            </div>
        </div>
    </main>

    <!-- FOOTER SECTION -->
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>