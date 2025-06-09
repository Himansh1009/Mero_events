<?php
// logout.php

// 1. Start the session
// This is necessary to access and manipulate session variables.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Unset all session variables
// This clears the $_SESSION array.
$_SESSION = array();

// 3. Destroy the session
// This deletes the session file on the server.
session_destroy();

// 4. Redirect the user to the homepage
header('Location: index.php');

// 5. Exit the script to ensure no further code is executed after the redirect
exit;
?>