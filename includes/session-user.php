<?php
// includes/session-user.php

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in AND their user_type is 'user'
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || !isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "user") {
    // If not, redirect to the authentication page
    // The '..' is crucial if this file is included from a subdirectory like 'user-dashboard/'
    header("Location: ../auth.php?action=login&error=unauthorized_user");
    exit; // Terminate script execution after redirection
}
?>