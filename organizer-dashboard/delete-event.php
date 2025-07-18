<?php
// organizer-dashboard/delete-event.php

// Enable full error reporting at the top (good for debugging during development)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure session validation exists to restrict access to logged-in organizers only.
require_once '../includes/session-organizer.php';
require_once '../includes/config.php'; // DB connection

$redirect_status = 'error'; // Default status for redirection (e.g., if something goes wrong)

// Retrieve the event_id from a GET or POST parameter.
// We'll prioritize POST as that's how it's sent from manage-events.php
$event_id_to_delete = $_POST['event_id_to_delete'] ?? $_GET['event_id'] ?? null;
$organizer_id = $_SESSION['user_id']; // Get the current logged-in organizer's ID

// Validate the event ID
if (!filter_var($event_id_to_delete, FILTER_VALIDATE_INT)) {
    $redirect_status = 'invalid_id';
} else {
    // Start a transaction for atomicity
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

        // 2. Then deletes the corresponding row from the events table.
        // IMPORTANT: Ensure the event belongs to the current organizer ($organizer_id)
        $sql_delete_event = "DELETE FROM events WHERE id = ? AND organizer_id = ?";
        if ($stmt_event = $conn->prepare($sql_delete_event)) {
            $stmt_event->bind_param("ii", $event_id_to_delete, $organizer_id);
            if (!$stmt_event->execute()) {
                throw new Exception("Failed to delete event: " . $stmt_event->error);
            }
            // Check if the event was actually deleted (i.e., it existed and belonged to the organizer)
            if ($stmt_event->affected_rows === 0) {
                throw new Exception("Event not found or you are not authorized to delete it.");
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
        // You can log $e->getMessage() for debugging
        error_log("Event deletion failed: " . $e->getMessage());
        $redirect_status = 'error'; // Generic error for redirect
        
        // If the specific error was authorization, adjust status
        if (strpos($e->getMessage(), "not authorized") !== false) {
            $redirect_status = 'unauthorized';
        }
    }
}

$conn->close(); // Closes all database resources properly.

// Redirect back to manage-events.php with a status message
header("Location: manage-events.php?deletion_status=" . $redirect_status);
exit; // Stop script execution after redirection
?>