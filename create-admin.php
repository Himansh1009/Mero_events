<?php
// create-admin.php

// Enable full error reporting at the top
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle database connection via includes/config.php.
require_once 'includes/config.php';

// Initialize variables for form data and messages
$admin_name = $admin_email = $admin_password = "";
$message = "";

// Process form submission when POST request is made
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Accept values for: Admin Name, Admin Email, Admin Password
    $admin_name = trim($_POST['admin_name'] ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');
    $admin_password = $_POST['admin_password'] ?? ''; // Keep raw for hashing

    // Validate inputs
    if (empty($admin_name) || empty($admin_email) || empty($admin_password)) {
        $message = "<div class='error-msg'>All fields are required.</div>";
    } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div class='error-msg'>Invalid email format.</div>";
    } else {
        // Handle duplicate email check (do not allow same email for multiple admins).
        $sql_check_email = "SELECT id FROM admins WHERE email = ?";
        if ($stmt_check = $conn->prepare($sql_check_email)) {
            $stmt_check->bind_param("s", $param_email);
            $param_email = $admin_email;
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $message = "<div class='error-msg'>This email address is already registered as an admin.</div>";
            } else {
                // Hash the password securely using password_hash() function (default BCRYPT).
                $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

                // Insert into admins table.
                $sql_insert = "INSERT INTO admins (name, email, password) VALUES (?, ?, ?)";
                if ($stmt_insert = $conn->prepare($sql_insert)) {
                    $stmt_insert->bind_param("sss", $admin_name, $admin_email, $hashed_password);

                    if ($stmt_insert->execute()) {
                        // After successful insert, display "Admin Created Successfully".
                        $message = "<div class='success-msg'>Admin '" . htmlspecialchars($admin_name) . "' created successfully!</div>";
                        // Clear form fields on success
                        $admin_name = $admin_email = $admin_password = ""; 
                    } else {
                        $message = "<div class='error-msg'>Error creating admin: " . $stmt_insert->error . "</div>";
                    }
                    $stmt_insert->close();
                } else {
                    $message = "<div class='error-msg'>Database error: Could not prepare insert statement.</div>";
                }
            }
            $stmt_check->close();
        } else {
            $message = "<div class='error-msg'>Database error: Could not prepare email check statement.</div>";
        }
    }
    $conn->close(); // Close database connection
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Admin - Mero Events</title>
    <!-- Link to your main CSS file for consistent styling -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Basic styling for a simple form (similar to login/register) */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .admin-creation-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
        }

        .admin-creation-container h2 {
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

        .form-group input[type="text"],
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
            background-color: #28a745; /* Green for creation */
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-submit:hover {
            background-color: #218838;
        }

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
    </style>
</head>
<body>
    <div class="admin-creation-container">
        <h2>Create New Admin User</h2>
        
        <?php 
        // Display message if set
        if (!empty($message)) {
            echo $message;
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="admin_name">Admin Name:</label>
                <input type="text" id="admin_name" name="admin_name" value="<?php echo htmlspecialchars($admin_name); ?>" required>
            </div>
            <div class="form-group">
                <label for="admin_email">Admin Email:</label>
                <input type="email" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($admin_email); ?>" required>
            </div>
            <div class="form-group">
                <label for="admin_password">Admin Password:</label>
                <input type="password" id="admin_password" name="admin_password" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn-submit">Create Admin</button>
            </div>
        </form>
    </div>
</body>
</html>