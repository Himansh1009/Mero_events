<?php
// index.php
session_start(); // Start the session at the very beginning of the script

// Include the database configuration for fetching featured events
require_once 'includes/config.php';

$featured_events = [];
$featured_message = "";

// Fetch any 3 approved events with available tickets, ordered randomly
// Adjust this query if you want specific criteria for "featured" events (e.g., upcoming, high-rated)
$sql_featured = "SELECT 
                    e.id, 
                    e.title, 
                    e.event_date, 
                    e.location,
                    e.tickets_booked,
                    e.total_tickets,
                    e.image_path /* ADDED: Select the image_path */
                 FROM 
                    events e
                 WHERE 
                    e.status = 'approved' AND (e.total_tickets - e.tickets_booked) > 0
                 ORDER BY 
                    RAND() 
                 LIMIT 3";

if ($result_featured = $conn->query($sql_featured)) {
    if ($result_featured->num_rows > 0) {
        while ($row = $result_featured->fetch_assoc()) {
            $featured_events[] = $row;
        }
    } else {
        $featured_message = "<p class='info-msg'>No featured events available right now. Check back later!</p>";
    }
    $result_featured->free();
} else {
    $featured_message = "<p class='error-msg'>Error fetching featured events: " . $conn->error . "</p>";
}

$conn->close(); // Close the database connection after fetching events
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mero Events - Discover Amazing Events Near You!</title>
    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Link to your main CSS file (assets/css/style.css) if you have global styles there -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Define Color Palette */
        :root {
            /* General project colors (from previous prompts) */
            --primary-color: #ff6b6b;   /* Reddish-orange */
            --secondary-color: #1dd1a1; /* Teal green */
            --accent-color: #feca57;    /* Yellow-orange */
            --background-color: #f1f2f6;
            --text-color: #2f3542;      /* Dark text */
            --light-text-color: #666;
            --white: #fff;
            --border-color: #ddd;
            --shadow-color: rgba(0,0,0,0.05);
            --hover-shadow-color: rgba(0,0,0,0.1);

            /* Navbar specific colors (from previous prompt's image) */
            --navbar-bg: #ffffff;
            --navbar-border: #f0f0f0; 
            --navbar-logo-color: #2f3542; /* Dark text for logo */
            --navbar-link-color: #666666; /* Gray text for links */
            --navbar-dashboard-btn-bg: #4a90e2; /* Blue from image */
            --navbar-logout-btn-bg: #e04444; /* Red from image */
            --navbar-btn-text-color: #ffffff; /* White text for buttons */

            /* New index.php specific colors (from image inspiration) */
            --hero-gradient-start: #6a5acd; /* Purple-blue */
            --hero-gradient-end: #4a90e2;   /* Lighter blue */
            --hero-text-color: var(--white);
            --explore-btn-bg: var(--white);
            --explore-btn-text: #6a5acd; /* Dark text matching gradient start */

            /* Featured card colors */
            --card-bg: var(--white);
            --card-shadow: rgba(0,0,0,0.08);
            --card-title-color: #2f3542; /* Matches general text-color */
            --card-meta-color: #666;
            --card-meta-icon-color: #bbbbbb; /* Lighter icon color */
        }

        body {
            font-family: Arial, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            margin: 0;
            display: flex;
            flex-direction: column; /* For sticky footer */
            min-height: 100vh; /* For sticky footer */
        }

        /* --- Navbar (Styled to match the image in the prompt) --- */
        .main-header {
            background-color: var(--navbar-bg);
            box-shadow: 0 2px 5px var(--shadow-color); /* Subtle shadow like the image */
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
            color: #6A5ACD; /* Blue-purple from image */
            text-decoration: none;
            flex-shrink: 0; /* Prevent shrinking */
        }
        .site-logo:hover {
            color: #6A5ACD; /* No color change on hover, as per image */
            opacity: 0.9; /* Subtle hover effect */
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

        /* --- Main Content Area --- */
        main {
            flex-grow: 1; /* For sticky footer */
            padding-bottom: 50px; /* Space above footer */
        }

        /* Hero Section Styling */
        .hero-section {
            background: linear-gradient(to right, var(--hero-gradient-start), var(--hero-gradient-end));
            padding: 100px 20px;
            text-align: center;
            color: var(--hero-text-color);
            margin-bottom: 50px; /* Space below hero */
        }

        .hero-section h1 {
            font-size: 3.5em;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .hero-section p {
            font-size: 1.3em;
            margin-bottom: 40px;
            opacity: 0.9;
        }

        .hero-section .explore-btn {
            background-color: var(--explore-btn-bg);
            color: var(--explore-btn-text);
            padding: 15px 35px;
            border-radius: 30px; /* Pill shape */
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1em;
            transition: background-color 0.2s ease, transform 0.2s ease;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2); /* Subtle shadow for button */
        }

        .hero-section .explore-btn:hover {
            background-color: var(--background-color);
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.3);
        }

        /* Featured Events Section */
        .featured-events-section {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }

        .featured-events-section h2 {
            font-size: 2.5em;
            color: var(--text-color);
            margin-bottom: 40px;
            font-weight: bold;
        }

        .featured-cards-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px; /* Space between cards */
        }

        .featured-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 4px 15px var(--card-shadow);
            overflow: hidden; /* For image border-radius */
            flex: 1 1 calc(33.333% - 40px); /* 3 cards per row */
            max-width: 380px; /* Max width for consistency */
            min-width: 280px; /* Minimum width before stacking */
            text-align: left;
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .featured-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }

        .featured-card-image {
            width: 100%;
            height: 180px; /* Fixed height for images */
            object-fit: cover;
            display: block;
        }

        .featured-card-content {
            padding: 20px;
        }

        .featured-card-content h3 {
            font-size: 1.4em;
            color: var(--card-title-color);
            margin-top: 0;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .featured-card-meta {
            font-size: 0.95em;
            color: var(--card-meta-color);
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        .featured-card-meta i {
            color: var(--card-meta-icon-color);
            margin-right: 8px;
            font-size: 1.1em;
        }

        /* Full Events CTA */
        .full-events-cta {
            text-align: center;
            margin-top: 60px;
            margin-bottom: 40px; /* Space before footer */
        }
        .full-events-cta .btn-secondary { /* Reusing btn-secondary style */
            background-color: var(--secondary-color);
            color: var(--white);
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1em;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }
        .full-events-cta .btn-secondary:hover {
            background-color: #17b38c;
            transform: translateY(-3px);
        }

        /* Message styling (reused from other pages) */
        .info-msg, .error-msg {
            margin: 20px auto; 
            padding: 12px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            max-width: 600px;
        }
        .info-msg { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .error-msg { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* --- Footer (Embedded for self-contained example, will be include in real project) --- */
        /* Assuming this content will eventually be loaded from includes/footer.php */
        .main-footer {
            background-color: #2f3542; /* Dark background, matching project's text-color */
            color: #e0e0e0; /* Light gray for general text */
            padding: 60px 0 20px 0; 
            font-size: 0.95em;
            margin-top: auto; /* Pushes footer to the bottom of the page */
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
            color: var(--white); /* White for headings */
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 25px;
            white-space: nowrap; 
        }

        /* Quick Links */
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

        /* Contact Us */
        .contact-info p {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .contact-info p i {
            color: var(--primary-color); /* Accent for icons */
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

        /* Stay Updated Form */
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
            background-color: #6a5acd; /* Purple-blue from image */
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

        /* Copyright Bar */
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

        /* Scroll to Top / Search Icon Button */
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
            .hero-section h1 { font-size: 2.5em; }
            .hero-section p { font-size: 1.1em; }
            .featured-events-section h2 { font-size: 2em; }
            .featured-cards-grid {
                flex-direction: column;
                align-items: center;
            }
            .featured-card {
                flex: 1 1 100%;
                max-width: 400px;
            }
            .main-nav {
                flex-direction: column;
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
                margin-right: 0;
                width: 100%;
                text-align: center;
            }
            .btn-navbar.dashboard, .btn-navbar.logout {
                 margin-left: 0;
            }

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
        @media (max-width: 480px) {
            .hero-section { padding: 80px 15px; }
            .hero-section h1 { font-size: 2em; }
            .hero-section p { font-size: 1em; }
            .hero-section .explore-btn { padding: 12px 25px; font-size: 1em; }
            .featured-events-section h2 { font-size: 1.8em; }
        }
    </style>
</head>
<body id="top"> <!-- Added ID for Back to Top link -->
    <header class="main-header">
        <nav class="main-nav">
            <a href="index.php" class="site-logo">Mero Events</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="events.php">Browse Events</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Contact</a></li>
                
                <?php
                // Dynamic Login/Dashboard/Logout links (PHP logic retained from previous prompts)
                if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
                    $dashboard_link = '#'; 
                    
                    if (isset($_SESSION["user_type"])) {
                        if ($_SESSION["user_type"] == "organizer") {
                            $dashboard_link = 'organizer-dashboard/dashboard.php';
                        } elseif ($_SESSION["user_type"] == "user") {
                            $dashboard_link = 'user-dashboard/dashboard.php';
                        }
                    }
                    // Apply button styles as per the image
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
        <!-- Hero Section -->
        <section class="hero-section">
            <h1>Discover Amazing Events Near You!</h1>
            <p>Concerts, Workshops, MUNs & more.</p>
            <a href="events.php" class="explore-btn">Explore Events</a>
        </section>

        <!-- Featured Events Section -->
        <section class="featured-events-section">
            <h2>Featured Events</h2>
            <?php if (!empty($featured_events)): ?>
                <div class="featured-cards-grid">
                    <?php foreach ($featured_events as $event): 
                        // Determine image path (use default if empty/invalid)
                        $image_src = !empty($event['image_path']) ? htmlspecialchars($event['image_path']) : 'event-images/default.jpg';
                    ?>
                        <a href="event-details.php?event_id=<?php echo htmlspecialchars($event['id']); ?>" class="featured-card">
                            <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="featured-card-image">
                            <div class="featured-card-content">
                                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                <p class="featured-card-meta">
                                    <i class="fa-solid fa-calendar-days"></i> <?php echo htmlspecialchars(date('M d, Y', strtotime($event['event_date']))); ?> 
                                    <span style="margin-left: 15px;"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($event['location']); ?></span>
                                </p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <?php echo $featured_message; ?>
            <?php endif; ?>
        </section>

        <!-- CTA button to full events page -->
        <section class="full-events-cta">
            <a href="events.php" class="btn-secondary">View All Events</a>
        </section>
    </main>

    <!-- FOOTER SECTION (Included from includes/footer.php) -->
    <?php require_once 'includes/footer.php'; ?> 
</body>
</html>