<?php
// includes/session-admin.php


if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {

    header("Location: ../admin-login.php?error=unauthorized_admin");
    exit; // Terminate script execution after redirection
}
?>