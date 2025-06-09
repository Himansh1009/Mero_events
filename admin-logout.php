<?php
// admin-logout.php
session_start();
unset($_SESSION['admin_logged_in']); // Unset specific admin session variable
$_SESSION = array(); // Clear all session data if you want to completely log out
session_destroy();
header("Location: admin-login.php"); // Redirect back to admin login
exit;
?>