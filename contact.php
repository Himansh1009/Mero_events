<?php
// contact.php
session_start(); // Start session to check login status for navbar

// Basic form handling (no email sending, just a placeholder message)
$contact_message = "";
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
        // In a real application, you would send an email here using PHP's mail() function
        // or a more robust library like PHPMailer.
        // For now, we'll just simulate success.
        $contact_message = "<div class='success-msg'>Thank you, " . $name . "! Your message has been received. We will get back to you shortly.</div>";
        // Clear form fields
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
    <!-- Link to your main CSS file -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Specific styling for the Contact page */
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

        .contact-section {
            background-color: #fff;
            padding: 50px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 40px auto;
            text-align: left;
        }

        .contact-section h1 {
            font-size: 2.8em;
            color: #2c3e50;
            margin-bottom: 25px;
            text-align: center;
        }

        .contact-info {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .contact-info p {
            font-size: 1.1em;
            line-height: 1.6;
            color: #555;
            margin-bottom: 10px;
        }
        .contact-info p strong {
            color: #333;
        }

        .contact-form .form-group {
            margin-bottom: 20px;
        }

        .contact-form label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
        }

        .contact-form input[type="text"],
        .contact-form input[type="email"],
        .contact-form textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box;
        }

        .contact-form textarea {
            min-height: 150px;
            resize: vertical;
        }

        .contact-form input:focus,
        .contact-form textarea:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        }

        .contact-form .btn-submit {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: auto;
            display: block;
            margin: 0 auto;
        }

        .contact-form .btn-submit:hover {
            background-color: #0056b3;
        }

        /* Message styling (reused from other pages) */
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
        <section class="contact-section">
            <h1>Contact Mero Events</h1>
            <p>Have a question, feedback, or need assistance? Reach out to us using the information below or fill out the contact form. We're here to help you connect with events that matter!</p>

            <div class="contact-info">
                <p><strong>Email:</strong> info@meroevents.com</p>
                <p><strong>Phone:</strong> +977-9849011111 (Nepal)</p>
                <p><strong>Address:</strong> Bharatpur, Chitwan, Nepal</p>
                <p><strong>Office Hours:</strong> Sunday - Friday, 9:00 AM - 5:00 PM (NPT)</p>
            </div>

            <h2>Send Us a Message</h2>
            <?php 
            if (!empty($contact_message)) {
                echo $contact_message;
            }
            ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="contact-form">
                <div class="form-group">
                    <label for="name">Your Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Your Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($subject ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" required><?php echo htmlspecialchars($message_content ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn-submit">Send Message</button>
                </div>
            </form>
        </section>
    </main>

    <footer class="main-footer">
        <div class="container">
            <p>© 2023 Mero Events. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>