<?php
// about.php
session_start(); // Start session to check login status for navbar
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Mero Events</title>
    <!-- Include Font Awesome for icons if needed (e.g., for social media in team cards) -->
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

            /* New About Us page specific colors (from provided screenshot) */
            --about-hero-gradient-start: #6a5acd; /* Purple-blue */
            --about-hero-gradient-end: #4a90e2;   /* Blue */
            --about-hero-text-color: var(--white);

            --team-card-bg: var(--white);
            --team-card-shadow: rgba(0,0,0,0.08);
            --team-circle-bg: linear-gradient(to bottom right, #6a5acd, #4a90e2); /* Gradient for circles */
            --team-name-color: var(--text-color);
            --team-role-color: var(--light-text-color);
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

        /* About Hero Banner (Matching Screenshot) */
        .about-hero-banner {
            background: linear-gradient(to right, var(--about-hero-gradient-start), var(--about-hero-gradient-end));
            padding: 80px 20px;
            text-align: center;
            color: var(--about-hero-text-color);
            margin-bottom: 50px; /* Space below banner */
        }
        .about-hero-banner h1 {
            font-size: 3em;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .about-hero-banner p {
            font-size: 1.2em;
            opacity: 0.9;
        }

        /* Section Styling (Our Mission, Meet The Team) */
        .content-section {
            padding: 40px 20px;
            max-width: 900px; /* Constrain content width */
            margin: 0 auto; /* Center content */
            text-align: center; /* Center headings */
        }
        .content-section h2 {
            font-size: 2.2em;
            color: var(--text-color);
            margin-bottom: 30px;
            font-weight: bold;
        }
        .content-section p {
            font-size: 1.1em;
            line-height: 1.8;
            color: var(--light-text-color);
            margin-bottom: 20px;
        }

        /* Meet The Team Section */
        .team-cards-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px; /* Space between team cards */
            margin-top: 40px;
        }

        .team-card {
            background-color: var(--team-card-bg);
            border-radius: 8px;
            box-shadow: 0 4px 15px var(--team-card-shadow);
            padding: 30px;
            flex: 1 1 calc(33.333% - 40px); /* 3 cards per row */
            max-width: 280px; /* Fixed width for team cards (approx) */
            min-width: 250px;
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .team-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }

        .team-member-circle {
            width: 120px;
            height: 120px;
            background: var(--team-circle-bg); /* Gradient background */
            border-radius: 50%;
            margin: 0 auto 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em; /* Placeholder initial or icon size */
            color: var(--white);
            overflow: hidden; /* For image if used later */
        }
        .team-member-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .team-card h3 {
            font-size: 1.3em;
            color: var(--team-name-color);
            margin-bottom: 5px;
            font-weight: bold;
        }
        .team-card p.role {
            font-size: 0.95em;
            color: var(--team-role-color);
            margin-bottom: 0;
        }

        /* --- Footer (FROM includes/footer.php - embedded for self-contained example) --- */
        /* Assuming this content will eventually be loaded from includes/footer.php */
        .main-footer {
            background-color: #2f3542; /* Dark background, matching project's text-color */
            color: #e0e0e0; /* Light gray for general text */
            padding: 60px 0 20px 0; 
            font-size: 0.95em;
            margin-top: auto; 
            width: 100%;
            box-sizing: border-box;
        }

        .footer-content {
            display: flex;
            flex-wrap: wrap; 
            justify-content: space-between; 
            gap: 30px; 
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            margin-bottom: 40px; 
        }

        .footer-column {
            flex: 1 1 280px; 
            min-width: 200px; 
            padding: 10px 0; 
        }

        .footer-column h3 {
            color: var(--white); 
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 25px;
            white-space: nowrap; 
        }

        .footer-column ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-column ul li {
            margin-bottom: 10px;
        }

        .footer-column ul li a {
            color: #e0e0e0;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .footer-column ul li a:hover {
            color: var(--primary-color);
        }

        .contact-info p {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .contact-info p i {
            color: var(--primary-color); 
            margin-right: 10px;
            font-size: 1.1em;
            flex-shrink: 0; 
        }

        .contact-info p a {
            color: #e0e0e0;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .contact-info p a:hover {
            color: var(--primary-color);
        }

        .subscribe-form {
            display: flex;
            margin-top: 10px;
            margin-bottom: 15px;
        }

        .subscribe-form input[type="email"] {
            flex-grow: 1;
            padding: 12px 15px;
            border: 1px solid #bbbbbb;
            border-radius: 5px 0 0 5px; 
            background-color: var(--white);
            color: var(--text-color);
            font-size: 0.95em;
            box-sizing: border-box;
            outline: none;
        }
        .subscribe-form input[type="email"]::placeholder {
            color: var(--light-text-color);
        }
        .subscribe-form input[type="email"]:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 2px rgba(254,202,87,0.3);
        }

        .subscribe-form button {
            background-color: #6a5acd; 
            color: var(--white);
            border: none;
            padding: 12px 20px;
            border-radius: 0 5px 5px 0; 
            font-size: 0.95em;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s ease;
            white-space: nowrap; 
        }

        .subscribe-form button:hover {
            background-color: #5a4bba;
        }

        .subscribe-text {
            font-size: 0.9em;
            line-height: 1.5;
            color: var(--footer-text-color);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            text-align: center;
            font-size: 0.85em;
            color: var(--footer-text-color);
            position: relative; 
        }

        .footer-bottom p {
            margin-bottom: 8px;
        }

        .footer-bottom a {
            color: var(--footer-text-color);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .footer-bottom a:hover {
            color: var(--primary-color);
        }

        .scroll-to-top-button {
            position: absolute; 
            bottom: 15px; 
            right: 20px;
            background-color: rgba(0, 0, 0, 0.6);
            color: var(--white);
            width: 40px;
            height: 40px;
            border-radius: 50%; 
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.2em;
            cursor: pointer;
            transition: background-color 0.2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }
        .scroll-to-top-button:hover {
            background-color: rgba(0, 0, 0, 0.8);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .about-hero-banner { padding: 60px 15px; }
            .about-hero-banner h1 { font-size: 2.5em; }
            .about-hero-banner p { font-size: 1em; }
            .content-section { padding: 30px 15px; }
            .content-section h2 { font-size: 1.8em; }
            .content-section p { font-size: 1em; }
            .team-cards-grid {
                flex-direction: column;
                align-items: center;
            }
            .team-card {
                flex: 1 1 100%;
                max-width: 300px;
            }

            /* Navbar adjustments */
            .main-nav { flex-direction: column; gap: 10px; align-items: flex-start; padding: 0 15px; }
            .site-logo { margin-bottom: 5px; }
            .nav-links { flex-wrap: wrap; justify-content: flex-start; gap: 10px; width: 100%; }
            .nav-links li { margin-left: 0; }
            .welcome-message { margin-right: 0; width: 100%; text-align: center; }
            .btn-navbar.dashboard, .btn-navbar.logout { margin-left: 0; width: auto; flex-grow: 1; }

            .footer-content {
                flex-direction: column; 
                align-items: center; 
                gap: 40px; 
            }
            .footer-column {
                flex: 1 1 100%; 
                max-width: 350px; 
                text-align: center; 
            }
            .footer-column h3 {
                margin-bottom: 20px; 
            }
            .contact-info p {
                justify-content: center; 
            }
            .subscribe-form {
                max-width: 350px;
                margin-left: auto;
                margin-right: auto;
            }
            .scroll-to-top-button {
                left: 50%; 
                transform: translateX(-50%);
                bottom: 20px; 
            }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <nav class="main-nav">
            <a href="index.php" class="site-logo">Mero Events</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="events.php">Browse Events</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Contact</a></li>
                
                <?php
                // Dynamic Login/Dashboard/Logout links (reused from index.php/events.php)
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
        <!-- Hero Banner for About Page -->
        <section class="about-hero-banner">
            <h1>About Mero Events</h1>
            <p>Connecting communities through unforgettable experiences.</p>
        </section>

        <!-- Our Mission Section -->
        <section class="content-section">
            <h2>Our Mission</h2>
            <p>We're a team of four passionate individuals dedicated to making event discovery seamless for everyone in our community. Our platform brings together entertainment, education, and global programs in one place. We believe in fostering community engagement and enriching lives through diverse events.</p>
        </section>

        <!-- Meet The Team Section -->
        <section class="content-section">
            <h2>Meet The Team</h2>
            <div class="team-cards-grid">
                <!-- Team Member 1 -->
                <div class="team-card">
                    <div class="team-member-circle">
                        <!-- Placeholder for image or initial -->
                        <i class="fa-solid fa-user"></i> <!-- Example icon -->
                        <!-- Or if using image: <img src="path/to/himanshu.jpg" alt="Himanshu"> -->
                    </div>
                    <h3>Himanshu</h3>
                    <p class="role">Co-Founder & Lead Developer</p>
                </div>
                <!-- Team Member 2 -->
                <div class="team-card">
                    <div class="team-member-circle">
                         <i class="fa-solid fa-user"></i>
                    </div>
                    <h3>Anmol</h3>
                    <p class="role">Co-Founder & Marketing Head</p>
                </div>
                <!-- Team Member 3 -->
                <div class="team-card">
                    <div class="team-member-circle">
                         <i class="fa-solid fa-user"></i>
                    </div>
                    <h3>Rasina</h3>
                    <p class="role">Community Outreach Manager</p>
                </div>
                <!-- Add more team members as needed -->
                <div class="team-card">
                    <div class="team-member-circle">
                         <i class="fa-solid fa-user"></i>
                    </div>
                    <h3>Saheshna</h3>
                    <p class="role">Operation Manager</p>
                </div>
            </div>
        </section>
    </main>

    <!-- FOOTER SECTION -->
    <?php require_once 'includes/footer.php'; ?> 
</body>
</html>