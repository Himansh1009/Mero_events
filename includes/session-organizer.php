<?php
// includes/session-organizer.php

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in AND their user_type is 'organizer'
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || !isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "organizer") {
    // If not, redirect to the authentication page
    // The '..' is crucial if this file is included from a subdirectory like 'organizer-dashboard/'
    header("Location: ../auth.php?action=login&error=unauthorized_organizer");
    exit; // Terminate script execution after redirection
}
?>