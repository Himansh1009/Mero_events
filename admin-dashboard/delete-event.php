<?php
// admin-dashboard/delete-event.php

// Enable full error reporting at the top (good for debugging during development)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure only logged-in admin users can access this functionality.
require_once '../includes/session-admin.php'; // Protect admin pages
require_once '../includes/config.php';       // Database connection

$redirect_status = 'error'; // Default status for redirection (e.g., if something goes wrong)

// Accept event_id securely via POST (as sent from manage-events.php)
// Use null coalescing operator to safely get the value or null
$event_id_to_delete = $_POST['event_id'] ?? null; 

// Validate the event ID
if (!filter_var($event_id_to_delete, FILTER_VALIDATE_INT)) {
    $redirect_status = 'invalid_id';
} else {
    // Start a transaction for atomicity (either both deletions succeed or both fail)
    $conn->begin_transaction();

    try {
        // 1. Delete all rows from the ticket_bookings table that reference this event_id.
        $sql_delete_bookings = "DELETE FROM ticket_bookings WHERE event_id = ?";
        if ($stmt_bookings = $conn->prepare($sql_delete_bookings)) {
            $stmt_bookings->bind_param("i", $event_id_to_delete);
            if (!$stmt_bookings->execute()) {
                throw new Exception("Failed to delete associated bookings: " . $stmt_bookings->error);
            }
            $stmt_bookings->close();
        } else {
            throw new Exception("Database error preparing booking deletion: " . $conn->error);
        }

        // 2. Then delete the corresponding row from the events table.
        // For admin, there's no `organizer_id` check here because admins can delete *any* event.
        $sql_delete_event = "DELETE FROM events WHERE id = ?";
        if ($stmt_event = $conn->prepare($sql_delete_event)) {
            $stmt_event->bind_param("i", $event_id_to_delete);
            if (!$stmt_event->execute()) {
                throw new Exception("Failed to delete event: " . $stmt_event->error);
            }
            // Check if the event was actually deleted (i.e., it existed)
            if ($stmt_event->affected_rows === 0) {
                // If 0 rows affected, it might mean the event was not found.
                // For admin, we assume they are trying to delete an existing event.
                throw new Exception("Event not found. It might have already been deleted.");
            }
            $stmt_event->close();
        } else {
            throw new Exception("Database error preparing event deletion: " . $conn->error);
        }

        // If both deletions succeed, commit the transaction
        $conn->commit();
        $redirect_status = 'success'; // Deletion successful

    } catch (Exception $e) {
        // If any error occurred, rollback the transaction
        $conn->rollback();
        // Log the actual error for debugging
        error_log("Admin event deletion failed for event ID " . $event_id_to_delete . ": " . $e->getMessage());
        $redirect_status = 'error'; // Generic error message for the user
    }
}

// Close database connection
$conn->close();

// Redirect back to manage-events.php with a status message
header("Location: manage-events.php?deletion_status=" . $redirect_status);
exit; // Stop script execution after redirection
?>