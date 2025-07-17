<style>
    /* Footer Specific Colors (derived from project palette but adjusted for dark background) */
    :root {
        /* Existing project colors */
        --primary-color: #ff6b6b;   /* Reddish-orange */
        --secondary-color: #1dd1a1; /* Teal green */
        --accent-color: #feca57;    /* Yellow-orange */
        --background-color: #f1f2f6;
        --text-color: #2f3542;      /* Dark text */
        --white: #fff;

        /* New Footer specific colors */
        --footer-bg: #2f3542;       /* Dark background, matching project's text-color */
        --footer-text-color: #e0e0e0; /* Light gray for general text */
        --footer-heading-color: var(--white); /* White for headings */
        --footer-link-color: var(--footer-text-color); /* Inherit general text color */
        --footer-link-hover-color: var(--primary-color); /* Primary color on hover for links */
        --footer-input-bg: var(--white);
        --footer-input-border: #bbbbbb;
        --footer-subscribe-btn-bg: #6a5acd; /* Purple-blue from image */
        --footer-subscribe-btn-hover-bg: #5a4bba;
        --footer-bottom-border: rgba(255, 255, 255, 0.1); /* Subtle white border */
        --scroll-to-top-bg: rgba(0, 0, 0, 0.6); /* Semi-transparent black */
        --scroll-to-top-hover-bg: rgba(0, 0, 0, 0.8);
    }

    .main-footer {
        background-color: var(--footer-bg);
        color: var(--footer-text-color);
        padding: 60px 0 20px 0; /* Top padding for content, bottom for copyright */
        font-size: 0.95em;
        margin-top: auto; /* Pushes footer to the bottom of the page */
        width: 100%;
        box-sizing: border-box;
    }

    .footer-content {
        display: flex;
        flex-wrap: wrap; /* Allows columns to wrap on smaller screens */
        justify-content: space-between; /* Distributes columns */
        gap: 30px; /* Space between columns */
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        margin-bottom: 40px; /* Space before copyright section */
    }

    .footer-column {
        flex: 1 1 280px; /* Base width for columns, allows flexibility */
        min-width: 200px; /* Minimum width before wrapping */
        padding: 10px 0; /* Inner padding */
    }

    .footer-column h3 {
        color: var(--footer-heading-color);
        font-size: 1.2em;
        font-weight: bold;
        margin-bottom: 25px;
        white-space: nowrap; /* Prevent heading wrap */
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
        color: var(--footer-link-color);
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .footer-column ul li a:hover {
        color: var(--footer-link-hover-color);
    }

    /* Contact Us */
    .contact-info p {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        line-height: 1.4;
    }

    .contact-info p i {
        color: var(--footer-link-hover-color); /* Accent for icons */
        margin-right: 10px;
        font-size: 1.1em;
        flex-shrink: 0; /* Prevent icon from shrinking */
    }

    .contact-info p a {
        color: var(--footer-text-color);
        text-decoration: none;
        transition: color 0.2s ease;
    }
     .contact-info p a:hover {
        color: var(--footer-link-hover-color);
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
        border: 1px solid var(--footer-input-border);
        border-radius: 5px 0 0 5px; /* Rounded left side */
        background-color: var(--footer-input-bg);
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
        box-shadow: 0 0 0 2px rgba(254,202,87,0.3); /* Subtle glow */
    }

    .subscribe-form button {
        background-color: var(--footer-subscribe-btn-bg);
        color: var(--white);
        border: none;
        padding: 12px 20px;
        border-radius: 0 5px 5px 0; /* Rounded right side */
        font-size: 0.95em;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.2s ease;
        white-space: nowrap; /* Prevent button text from wrapping */
    }

    .subscribe-form button:hover {
        background-color: var(--footer-subscribe-btn-hover-bg);
    }

    .subscribe-text {
        font-size: 0.9em;
        line-height: 1.5;
        color: var(--footer-text-color);
    }

    /* Copyright Bar */
    .footer-bottom {
        border-top: 1px solid var(--footer-bottom-border);
        padding-top: 20px;
        text-align: center;
        font-size: 0.85em;
        color: var(--footer-text-color);
        position: relative; /* For scroll-to-top button positioning */
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
        position: absolute; /* Position relative to footer-bottom */
        bottom: 15px; /* Adjust as needed */
        right: 20px;
        background-color: var(--scroll-to-top-bg);
        color: var(--white);
        width: 40px;
        height: 40px;
        border-radius: 50%; /* Circular */
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 1.2em;
        cursor: pointer;
        transition: background-color 0.2s ease;
        box-shadow: 0 2px 5px rgba(0,0,0,0.3);
    }
    .scroll-to-top-button:hover {
        background-color: var(--scroll-to-top-hover-bg);
    }

    /* Responsive adjustments for footer */
    @media (max-width: 768px) {
        .footer-content {
            flex-direction: column; /* Stack columns vertically */
            align-items: center; /* Center stacked columns */
            gap: 40px; /* More space between stacked columns */
        }
        .footer-column {
            flex: 1 1 100%; /* Each column takes full width */
            max-width: 350px; /* Max width for readability on small screens */
            text-align: center; /* Center content within columns */
        }
        .footer-column h3 {
            margin-bottom: 20px; /* Adjust heading spacing */
        }
        .contact-info p {
            justify-content: center; /* Center icons and text */
        }
        .subscribe-form {
            max-width: 350px;
            margin-left: auto;
            margin-right: auto;
        }
        .scroll-to-top-button {
            left: 50%; /* Center horizontally */
            transform: translateX(-50%);
            bottom: 20px; /* Adjust position */
        }
    }
</style>

<footer class="main-footer">
    <div class="footer-content">
        <div class="footer-column">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="browse-events.php">Browse Events</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="contact.php">Contact</a></li>
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> 
            </ul>
        </div>

        <div class="footer-column">
            <h3>Contact Us</h3>
            <div class="contact-info">
                <p><i class="fa-solid fa-envelope"></i> <a href="mailto:info@meroevents.com">info@meroevents.com</a></p>
                <p><i class="fa-solid fa-phone"></i> +977 9841000000</p>
                <p><i class="fa-solid fa-location-dot"></i> Bharatpur, Nepal</p>
            </div>
        </div>

        <div class="footer-column">
            <h3>Stay Updated</h3>
            <form action="#" method="post" class="subscribe-form">
                <input type="email" name="email" placeholder="Your Email" required>
                <button type="submit">Subscribe</button>
            </form>
            <p class="subscribe-text">Get notified about upcoming events and Mero Events news!</p>
        </div>
    </div>

    <div class="footer-bottom">
        <p>© 2023 Mero Events. All rights reserved.</p>
        <p><a href="#top">Back to Top</a></p> <!-- Links to an element with id="top" typically at the body/html start -->
        <div class="scroll-to-top-button" onclick="window.scrollTo({ top: 0, behavior: 'smooth' });">
             <i class="fa-solid fa-magnifying-glass"></i> <!-- Using search icon as seen in image bottom right -->
        </div>
    </div>
</footer>

<!-- You need to include Font Awesome in your <head> for icons to work -->
