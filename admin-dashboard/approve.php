<?php
// admin-dashboard/approve.php

// Protect admin pages
require_once '../includes/session-admin.php';
// Connect to the database
require_once '../includes/config.php';

$redirect_status = 'error'; // Default redirect status

// Accept the event id via GET
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $event_id = $_GET['id'];
    $new_status = 'approved';

    // Update the events.status to 'approved' using prepared statements
    $sql = "UPDATE events SET status = ? WHERE id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $new_status, $event_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $redirect_status = 'success';
            } else {
                // No rows affected might mean ID was valid but status was already 'approved'
                $redirect_status = 'info'; // Or a more specific message if you want
            }
        } else {
            // SQL execution error
            $redirect_status = 'error';
        }
        $stmt->close();
    } else {
        // Prepare statement error
        $redirect_status = 'error';
    }
} else {
    // No valid event ID provided
    $redirect_status = 'invalid';
}

$conn->close(); // Close database connection

// Redirect back to manage-events.php with a status message
header("Location: manage-events.php?status_update=" . $redirect_status);
exit;
?>