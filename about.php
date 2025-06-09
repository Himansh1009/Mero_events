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
    <!-- Link to your main CSS file -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Specific styling for the About page */
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
        }

        .about-section {
            background-color: #fff;
            padding: 50px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 900px;
            margin: 40px auto; /* Center the content vertically and horizontally */
            text-align: left;
        }

        .about-section h1 {
            font-size: 2.8em;
            color: #2c3e50;
            margin-bottom: 25px;
            text-align: center;
        }

        .about-section h2 {
            font-size: 1.8em;
            color: #007bff;
            margin-top: 30px;
            margin-bottom: 15px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .about-section p {
            font-size: 1.1em;
            line-height: 1.8;
            color: #555;
            margin-bottom: 15px;
        }

        .about-section ul {
            list-style: disc;
            margin-left: 25px;
            margin-bottom: 20px;
        }

        .about-section ul li {
            font-size: 1.1em;
            line-height: 1.6;
            color: #555;
            margin-bottom: 8px;
        }

        /* Reusing common styles from style.css (header/footer/buttons) */
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
        
        .btn {
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

        .main-footer {
            background-color: #333;
            color: #fff;
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
                <a href="index.php" class="site-logo">Mero Events</a>
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="events.php">Events</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    
                    <?php
                    // Dynamic Login/Dashboard/Logout links (reused logic from index.php)
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
                        echo '<li><a href="' . htmlspecialchars($dashboard_link) . '" class="btn btn-primary">Dashboard</a></li>';
                        echo '<li><a href="logout.php" class="btn btn-primary" style="background-color: #dc3545;">Logout</a></li>'; 
                    } else {
                        echo '<li><a href="auth.php" class="btn btn-primary">Login/Register</a></li>';
                    }
                    ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section class="about-section">
            <h1>About Mero Events</h1>
            <p>Welcome to **Mero Events**, your dedicated platform for discovering and promoting a diverse range of educational, community, and student-focused events right here in Bharatpur, Nepal!</p>

            <p>Our mission is simple: to bridge the gap between diligent event organizers and their eager audience. In a bustling city like Bharatpur, countless valuable workshops, seminars, cultural gatherings, and student initiatives take place, but often struggle for visibility. Mero Events was created to solve this challenge.</p>

            <h2>What We Offer</h2>
            <ul>
                <li><strong>For Attendees:</strong> A centralized hub to easily find events by category, date, or keyword. Whether you're looking for academic enrichment, tech meetups, social causes, or student club activities, Mero Events makes discovery effortless.</li>
                <li><strong>For Organizers:</strong> A powerful tool to list, promote, and manage your events. Gain wider reach, attract more participants, and streamline your event publicity with our intuitive platform.</li>
                <li><strong>Community Impact:</strong> By increasing visibility and participation, we aim to foster a more vibrant and engaged community in Bharatpur. We believe that access to information about local events is key to personal growth and collective development.</li>
            </ul>

            <h2>Our Vision</h2>
            <p>We envision a Bharatpur where no significant educational, community, or student event goes unnoticed. Mero Events is committed to becoming the go-to resource for anyone looking to organize or attend events that truly matter, enriching the lives of individuals and strengthening our local community.</p>

            <p>Join us in building a more connected and informed Bharatpur, one event at a time!</p>
        </section>
    </main>

    <footer class="main-footer">
        <div class="container">
            <p>© 2023 Mero Events. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>