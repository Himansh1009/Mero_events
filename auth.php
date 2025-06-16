<?php
session_start(); // Start the session at the very beginning

// Include the database configuration
require_once 'includes/config.php';

// Initialize variables for form data and messages
$message = "";
$action_type = "login"; // Default to login view
$selected_user_type = ""; // To pre-select user type in form, defaults to 'user' for new forms

// Variables for registration form fields (to retain values on error)
$reg_name = $reg_email = $reg_password = $reg_confirm_password = "";

// Variables for login form fields (to retain values on error)
$login_email = $login_password = "";

// Directory for ID proof uploads
$upload_dir = 'uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true); // Create directory if it doesn't exist, with full permissions
}

// Check if a specific action (login/register) is requested via GET for initial view
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'register') {
        $action_type = "register";
        $selected_user_type = 'user'; // Default selection for new registration form
    } elseif ($_GET['action'] == 'login') {
        $action_type = "login";
        $selected_user_type = 'user'; // Default selection for new login form
    }
}

// Handle POST request (form submission for either login or registration)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Determine if it's a Registration attempt ---
    if (isset($_POST['register_submit'])) {
        $action_type = "register"; // Ensure the register form is shown after submission
        $selected_user_type = isset($_POST['user_type']) ? trim($_POST['user_type']) : '';
        $reg_name = trim($_POST['full_name']);
        $reg_email = trim($_POST['email']);
        $reg_password = $_POST['password'];
        $reg_confirm_password = $_POST['confirm_password'];

        // 1. Registration Validation: All fields required
        if (empty($reg_name) || empty($reg_email) || empty($reg_password) || empty($reg_confirm_password) || empty($selected_user_type)) {
            $message = "<div class='error-msg'>All fields are required.</div>";
        } 
        // Passwords must match
        elseif ($reg_password !== $reg_confirm_password) {
            $message = "<div class='error-msg'>Password and Confirm Password do not match.</div>";
        } 
        // Basic email format validation
        elseif (!filter_var($reg_email, FILTER_VALIDATE_EMAIL)) {
            $message = "<div class='error-msg'>Invalid email format.</div>";
        } else {
            $table = ($selected_user_type == 'organizer') ? 'organizers' : 'users';
            $redirect_dashboard = ($selected_user_type == 'organizer') ? 'organizer-dashboard/dashboard.php' : 'user-dashboard/dashboard.php';

            // Check email uniqueness within the selected user type's table
            $sql_check_email = "SELECT id FROM $table WHERE email = ?";
            if ($stmt_check = $conn->prepare($sql_check_email)) {
                $stmt_check->bind_param("s", $param_email);
                $param_email = $reg_email;
                $stmt_check->execute();
                $stmt_check->store_result();
                if ($stmt_check->num_rows > 0) {
                    $message = "<div class='error-msg'>This email address is already registered as a " . htmlspecialchars($selected_user_type) . ".</div>";
                } else {
                    // Handle ID Proof upload for organizers only
                    $id_proof_path = null;
                    $upload_error = false;

                    if ($selected_user_type == 'organizer') {
                        if (isset($_FILES['id_proof']) && $_FILES['id_proof']['error'] == UPLOAD_ERR_OK) {
                            $file_name = $_FILES['id_proof']['name'];
                            $file_tmp_name = $_FILES['id_proof']['tmp_name'];
                            $file_size = $_FILES['id_proof']['size'];
                            
                            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                            $allowed_extensions = array("jpg", "jpeg", "png", "pdf");
                            $max_file_size = 5 * 1024 * 1024; // 5 MB in bytes

                            if (!in_array($file_ext, $allowed_extensions)) {
                                $message = "<div class='error-msg'>Invalid ID proof file type. Only JPG, JPEG, PNG, PDF are allowed.</div>";
                                $upload_error = true;
                            } elseif ($file_size > $max_file_size) {
                                $message = "<div class='error-msg'>ID proof file is too large (max 5MB).</div>";
                                $upload_error = true;
                            } else {
                                $new_file_name = uniqid('id_proof_', true) . '.' . $file_ext;
                                $destination = $upload_dir . $new_file_name;
                                if (move_uploaded_file($file_tmp_name, $destination)) {
                                    $id_proof_path = $destination;
                                } else {
                                    $message = "<div class='error-msg'>Failed to upload ID proof. Please check directory permissions.</div>";
                                    $upload_error = true;
                                }
                            }
                        } else {
                            // If user selected organizer but didn't upload file, or there was an error
                            if ($_FILES['id_proof']['error'] == UPLOAD_ERR_NO_FILE) {
                                $message = "<div class='error-msg'>ID Proof is required for organizer registration.</div>";
                            } else {
                                $message = "<div class='error-msg'>Error uploading ID proof: " . $_FILES['id_proof']['error'] . "</div>";
                            }
                            $upload_error = true;
                        }
                    }

                    // Proceed with registration only if no validation/upload errors
                    if (empty($message) && !$upload_error) {
                        $hashed_password = password_hash($reg_password, PASSWORD_DEFAULT);

                        if ($selected_user_type == 'organizer') {
                            $sql_insert = "INSERT INTO organizers (name, email, password, id_proof) VALUES (?, ?, ?, ?)";
                            if ($stmt_insert = $conn->prepare($sql_insert)) {
                                $stmt_insert->bind_param("ssss", $reg_name, $reg_email, $hashed_password, $id_proof_path);
                            }
                        } else { // 'user'
                            $sql_insert = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
                            if ($stmt_insert = $conn->prepare($sql_insert)) {
                                $stmt_insert->bind_param("sss", $reg_name, $reg_email, $hashed_password);
                            }
                        }

                        if (isset($stmt_insert) && $stmt_insert->execute()) {
                            // Registration successful, set session variables
                            $_SESSION["logged_in"] = true;
                            $_SESSION["user_id"] = $conn->insert_id; // ID of the newly registered user/organizer
                            $_SESSION["user_name"] = $reg_name;
                            $_SESSION["user_email"] = $reg_email; // Store email in session
                            $_SESSION["user_type"] = $selected_user_type;

                            // Redirect to respective dashboard
                            header("location: $redirect_dashboard");
                            exit();
                        } else {
                            $message = "<div class='error-msg'>Something went wrong during registration. Please try again. " . (isset($stmt_insert) ? $stmt_insert->error : '') . "</div>";
                        }
                        if (isset($stmt_insert)) $stmt_insert->close();
                    }
                }
                $stmt_check->close();
            } else {
                $message = "<div class='error-msg'>Database error during email uniqueness check.</div>";
            }
        }

    } // --- Determine if it's a Login attempt ---
    elseif (isset($_POST['login_submit'])) {
        $action_type = "login"; // Ensure the login form is shown after submission
        $selected_user_type = isset($_POST['login_user_type']) ? trim($_POST['login_user_type']) : '';
        $login_email = trim($_POST['login_email']);
        $login_password = $_POST['login_password'];

        // 1. Login Validation: Both fields and role must be filled
        if (empty($login_email) || empty($login_password) || empty($selected_user_type)) {
            $message = "<div class='error-msg'>Please enter email, password, and select your role.</div>";
        } else {
            $table = ($selected_user_type == 'organizer') ? 'organizers' : 'users';
            $redirect_dashboard = ($selected_user_type == 'organizer') ? 'organizer-dashboard/dashboard.php' : 'user-dashboard/dashboard.php';

            // Prepare a select statement to fetch credentials
            $sql_login = "SELECT id, name, password, is_approved FROM $table WHERE email = ?"; // Added email to select for session
            if ($stmt_login = $conn->prepare($sql_login)) {
                $stmt_login->bind_param("s", $param_email);
                $param_email = $login_email;
                $stmt_login->execute();
                $stmt_login->store_result();

                if ($stmt_login->num_rows == 1) {
                    $stmt_login->bind_result($id, $name, $hashed_password, $is_approved); // Bind email from DB
                    $stmt_login->fetch();

                    // Verify the password using password_verify()
                    if (password_verify($login_password, $hashed_password)) {
                        // Password is correct, create PHP session
                        $_SESSION["logged_in"] = true;
                        $_SESSION["user_id"] = $id;
                        $_SESSION["user_name"] = $name;
                        $_SESSION["user_email"] = $email_from_db; // Store email in session
                        $_SESSION["user_type"] = $selected_user_type; // Store user type in session

                        // Redirect user to respective dashboard page
                        header("location: $redirect_dashboard");
                        exit(); // Always exit after a header redirect
                    } else {
                        $message = "<div class='error-msg'>The password you entered was not valid.</div>";
                    }
                } else {
                    // Email doesn't exist for the selected role
                    $message = "<div class='error-msg'>No account found with that email address for the selected role.</div>";
                }
                $stmt_login->close();
            } else {
                $message = "<div class='error-msg'>Database error during login process.</div>";
            }
        }
    }
}

$conn->close(); // Close database connection after all operations
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login / Register - Mero Events</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* General body and main content styling */
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
            align-items: center; /* Vertically center the content */
        }
        .auth-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 480px; /* Slightly wider for better field spacing */
            text-align: center; /* Center the toggle buttons */
        }
        .auth-container h2 {
            color: #333;
            margin-bottom: 25px;
            font-size: 2em;
        }

        /* Toggle buttons for Login/Register */
        .auth-toggle {
            margin-bottom: 30px;
            display: flex;
            justify-content: center;
            gap: 15px; /* Space between buttons */
        }
        .auth-toggle button {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            cursor: pointer;
            background-color: #e9ecef;
            color: #495057;
            transition: background-color 0.3s ease, color 0.3s ease;
            flex: 1; /* Make buttons expand to fill space */
            max-width: 180px; /* Limit max width of buttons */
        }
        .auth-toggle button.active {
            background-color: #007bff;
            color: #fff;
            font-weight: bold;
        }
        .auth-toggle button:hover:not(.active) {
            background-color: #dee2e6;
        }

        /* Form styling */
        .form-group {
            margin-bottom: 20px;
            text-align: left; /* Align labels/inputs left within their group */
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="file"],
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        }

        /* Radio buttons for user type */
        .user-type-group {
            margin-bottom: 25px;
            text-align: center;
        }
        .user-type-group label {
            display: inline-block;
            margin: 0 15px;
            cursor: pointer;
            font-weight: normal; /* Override bold from .form-group label */
        }
        .user-type-group input[type="radio"] {
            margin-right: 5px;
            transform: scale(1.2); /* Make radio buttons a bit larger */
            vertical-align: middle;
        }
        
        /* Submit buttons */
        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: #28a745; /* Green for register */
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn-submit.login-btn {
            background-color: #007bff; /* Blue for login */
        }
        .btn-submit:hover {
            opacity: 0.9;
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

        /* Hide/Show forms based on PHP $action_type */
        .login-form, .register-form {
            display: none;
        }
        <?php if ($action_type == 'login'): ?>
            .login-form { display: block; }
        <?php else: ?>
            .register-form { display: block; }
        <?php endif; ?>
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
                    // Dynamic Login/Dashboard/Logout links for the navbar
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
        <div class="auth-container">
            <!-- Toggle buttons for switching between Login and Register views -->
            <div class="auth-toggle">
                <button onclick="window.location.href='auth.php?action=login'" class="<?php echo ($action_type == 'login') ? 'active' : ''; ?>">Login</button>
                <button onclick="window.location.href='auth.php?action=register'" class="<?php echo ($action_type == 'register') ? 'active' : ''; ?>">Register</button>
            </div>

            <?php 
            // Display message if set (for success or error)
            if (!empty($message)) {
                echo $message;
            }
            ?>

            <!-- Login Form -->
            <div class="login-form">
                <h2>Login to Mero Events</h2>
                <form action="auth.php" method="post">
                    <!-- Hidden field to identify this form submission as a login attempt -->
                    <input type="hidden" name="login_submit" value="1">
                    
                    <div class="form-group">
                        <label for="login_email">Email:</label>
                        <input type="email" id="login_email" name="login_email" value="<?php echo htmlspecialchars($login_email); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="login_password">Password:</label>
                        <input type="password" id="login_password" name="login_password" required>
                    </div>
                    <div class="user-type-group">
                        <label>Login as:</label>
                        <label>
                            <input type="radio" name="login_user_type" value="user" <?php echo ($selected_user_type == 'user' || empty($selected_user_type)) ? 'checked' : ''; ?> required> User
                        </label>
                        <label>
                            <input type="radio" name="login_user_type" value="organizer" <?php echo ($selected_user_type == 'organizer') ? 'checked' : ''; ?>> Organizer
                        </label>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn-submit login-btn">Login</button>
                    </div>
                </form>
            </div>

            <!-- Registration Form -->
            <div class="register-form">
                <h2>Register for Mero Events</h2>
                <form action="auth.php" method="post" enctype="multipart/form-data">
                    <!-- Hidden field to identify this form submission as a registration attempt -->
                    <input type="hidden" name="register_submit" value="1">
                    
                    <div class="form-group">
                        <label for="full_name">Full Name:</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($reg_name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="reg_email">Email:</label>
                        <input type="email" id="reg_email" name="email" value="<?php echo htmlspecialchars($reg_email); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="reg_password">Password:</label>
                        <input type="password" id="reg_password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="reg_confirm_password">Confirm Password:</label>
                        <input type="password" id="reg_confirm_password" name="confirm_password" required>
                    </div>
                    <div class="user-type-group">
                        <label>Register as:</label>
                        <label>
                            <input type="radio" name="user_type" value="user" <?php echo ($selected_user_type == 'user' || empty($selected_user_type)) ? 'checked' : ''; ?> required> User
                        </label>
                        <label>
                            <input type="radio" name="user_type" value="organizer" <?php echo ($selected_user_type == 'organizer') ? 'checked' : ''; ?>> Organizer
                        </label>
                    </div>

                    <!-- ID Proof field for Organizers - always in DOM, required attribute is dynamic -->
                    <div class="form-group" id="organizer_id_proof_group">
                        <label for="id_proof">ID Proof (for Organizers: JPG, PNG, PDF max 5MB):</label>
                        <input type="file" id="id_proof" name="id_proof" <?php echo ($selected_user_type == 'organizer') ? 'required' : ''; ?>>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn-submit">Register</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer class="main-footer">
        <div class="container">
            <p>© 2023 Mero Events. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>