<?php
session_start(); // Start the session at the very beginning of the script
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mero Events - Find, Explore, and Attend Events</title>
    <!-- Link to your main CSS file -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        
        .welcome-message {
            color: #555; 
            font-weight: 500;
            margin-right: 15px; 
            white-space: nowrap; 
        }
        
        /* Ensure consistent spacing in nav links */
        .nav-links li {
            margin-left: 25px; 
        }
        .nav-links li:first-child {
            margin-left: 0; 
        }

        
        .hero-section {
            background-color: #e9f0f9; 
            padding: 100px 0; 
            text-align: center;
            color: #333;
        }

        .hero-content h1 {
            font-size: 3.2em; 
            margin-bottom: 20px;
            color: #2c3e50; 
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-content p {
            font-size: 1.2em;
            max-width: 800px; 
            margin: 0 auto 30px auto; 
            color: #555;
            line-height: 1.8;
        }

        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
            transition: background-color 0.3s ease, transform 0.2s ease;
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
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #28a745; 
            color: #fff;
            border: 1px solid #28a745;
        }

        .btn-secondary:hover {
            background-color: #218838;
            border-color: #1e7e34;
            transform: translateY(-2px);
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
        .main-footer p {
            margin: 0;
        }
        
      
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        main {
            flex-grow: 1; 
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
                    // Dynamic Login/Dashboard/Logout links
                    if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
                        // User is logged in
                        $dashboard_link = '#'; // Default fallback
                        
                        // Determine dashboard link based on user type
                        if (isset($_SESSION["user_type"])) {
                            if ($_SESSION["user_type"] == "organizer") {
                                $dashboard_link = 'organizer-dashboard/dashboard.php';
                            } elseif ($_SESSION["user_type"] == "user") {
                                $dashboard_link = 'user-dashboard/dashboard.php';
                            }
                        }

                        // Display Welcome message, Dashboard link, and Logout link
                        echo '<li class="welcome-message">Welcome, ' . htmlspecialchars($_SESSION["user_name"]) . '</li>';
                        echo '<li><a href="' . htmlspecialchars($dashboard_link) . '" class="btn btn-primary">Dashboard</a></li>';
                        echo '<li><a href="logout.php" class="btn btn-primary" style="background-color: #dc3545;">Logout</a></li>'; // Red background for logout
                    } else {
                        // User is not logged in, show Login/Register button
                        echo '<li><a href="auth.php" class="btn btn-primary">Login/Register</a></li>';
                    }
                    ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero-section">
            <div class="container hero-content">
                <h1>Find, Explore, and Attend Events That Matter</h1>
                <p>From community workshops to college seminars, Mero Events brings them all together. Discover local educational programs, tech meetups, and student-focused activities in Bharatpur, Nepal.</p>
                <a href="events.php" class="btn btn-secondary">Browse Events</a>
            </div>
        </section>
        
      

    </main>

    <footer class="main-footer">
        <div class="container">
            <p>© 2023 Mero Events. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>