<?php
// includes/session-admin.php

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // If not, redirect to the admin login page
    // The '..' is crucial if this file is included from a subdirectory like 'admin-dashboard/'
    header("Location: ../admin-login.php?error=unauthorized_admin");
    exit; // Terminate script execution after redirection
}
?>