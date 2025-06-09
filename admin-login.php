<?php
// admin-login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}// Start the session at the very beginning

// Include the database configuration
require_once 'includes/config.php';

// Initialize variables for form data and messages
$email = "";
$password = "";
$message = ""; // To store success or error messages

// If admin is already logged in, redirect to dashboard
if (isset($_SESSION["admin_logged_in"]) && $_SESSION["admin_logged_in"] === true) {
    header("location: admin-dashboard/dashboard.php");
    exit;
}

// Process form submission when POST request is made
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get and sanitize input
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    // Validate both fields are filled
    if (empty($email) || empty($password)) {
        $message = "<div class='error-msg'>Please enter both email and password.</div>";
    } else {
        // Prepare a select statement to fetch admin credentials
        $sql = "SELECT id, name, password FROM admins WHERE email = ?";

        if ($stmt = $conn->prepare($sql)) {
            // Bind parameters
            $stmt->bind_param("s", $param_email);

            // Set parameters
            $param_email = $email;

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Store result
                $stmt->store_result();

                // Check if email exists, if yes then verify password
                if ($stmt->num_rows == 1) {
                    // Bind result variables
                    $stmt->bind_result($admin_id, $admin_name, $hashed_password);
                    $stmt->fetch();

                    // Use password_verify() to compare input with hashed password
                    if (password_verify($password, $hashed_password)) {
                        // Password is correct, start a new session
                        $_SESSION["admin_logged_in"] = true;
                        $_SESSION["admin_id"] = $admin_id;
                        $_SESSION["admin_name"] = $admin_name;

                        // Redirect admin to dashboard page
                        
                        header("location: admin-dashboard/dashboard.php");
                        exit(); // Always exit after a header redirect
                    } else {
                        // Password is not valid
                        $message = "<div class='error-msg'>Invalid email or password.</div>"; // Generic message for security
                    }
                } else {
                    // Email doesn't exist
                    $message = "<div class='error-msg'>Invalid email or password.</div>"; // Generic message for security
                }
            } else {
                $message = "<div class='error-msg'>Oops! Something went wrong. Please try again later.</div>";
            }
            $stmt->close(); // Close statement
        } else {
            $message = "<div class='error-msg'>Database error: Could not prepare statement.</div>";
        }
    }
    $conn->close(); // Close connection
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Mero Events</title>
    <!-- Link to your main CSS file -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Basic styling for the admin login form */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .login-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 2em;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
        }

        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box;
        }

        .form-group input:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
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

        .message {
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }

        .error-msg {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        
        <?php 
        // Display message if set
        if (!empty($message)) {
            echo $message;
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn-submit">Login</button>
            </div>
        </form>
    </div>
</body>
</html>