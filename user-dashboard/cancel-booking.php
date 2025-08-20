<?php
// user-dashboard/cancel-booking.php

// Enable full error reporting at the top
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure only logged-in users can access this page
require_once '../includes/session-user.php';
// Use includes/config.php for DB connection
require_once '../includes/config.php';

$redirect_status = 'error'; // Default status for redirection
$user_id = $_SESSION['user_id']; // Get logged-in user's ID

// Get booking_id from GET parameter (redirected from my-bookings.php)
$booking_id_to_cancel = $_GET['booking_id'] ?? null;

// Validate booking ID
if (!filter_var($booking_id_to_cancel, FILTER_VALIDATE_INT)) {
    $redirect_status = 'invalid_id';
} else {
    // Start a transaction for atomicity
    $conn->begin_transaction();

    try {
        // 1. Fetch booking details (num_tickets, event_id, and current status) and lock the row
        $sql_fetch_booking = "SELECT num_tickets, event_id, status FROM ticket_bookings WHERE id = ? AND user_id = ? FOR UPDATE";
        if ($stmt_fetch = $conn->prepare($sql_fetch_booking)) {
            $stmt_fetch->bind_param("ii", $booking_id_to_cancel, $user_id);
            $stmt_fetch->execute();
            $result_fetch = $stmt_fetch->get_result();

            if ($result_fetch->num_rows == 0) {
                throw new Exception("Booking not found or you are not authorized to cancel it.");
            }
            $booking_details = $result_fetch->fetch_assoc();
            
            // Check if already cancelled
            if ($booking_details['status'] == 'cancelled') {
                throw new Exception("This booking has already been canceled.");
            }

            // Check if event date has passed (prevent cancellation of completed events)
            $sql_check_event_date = "SELECT event_date, event_time FROM events WHERE id = ?";
            if ($stmt_event_date = $conn->prepare($sql_check_event_date)) {
                $stmt_event_date->bind_param("i", $booking_details['event_id']);
                $stmt_event_date->execute();
                $result_event_date = $stmt_event_date->get_result();
                $event_row = $result_event_date->fetch_assoc();
                $event_datetime = strtotime($event_row['event_date'] . ' ' . $event_row['event_time']);

                if ($event_datetime < time()) {
                    throw new Exception("Cannot cancel booking for an event that has already passed.");
                }
                $stmt_event_date->close();
            } else {
                throw new Exception("Database error checking event date: " . $conn->error);
            }

            $num_tickets_booked = $booking_details['num_tickets'];
            $event_id_for_update = $booking_details['event_id'];
            $stmt_fetch->close();
        } else {
            throw new Exception("Database error fetching booking details: " . $conn->error);
        }

        // 2. Reduce tickets_booked in the events table
        $sql_update_event = "UPDATE events SET tickets_booked = tickets_booked - ? WHERE id = ?";
        if ($stmt_update = $conn->prepare($sql_update_event)) {
            $stmt_update->bind_param("ii", $num_tickets_booked, $event_id_for_update);
            if (!$stmt_update->execute()) {
                throw new Exception("Failed to release tickets for event: " . $stmt_update->error);
            }
            $stmt_update->close();
        } else {
            throw new Exception("Database error preparing event ticket update: " . $conn->error);
        }

        // 3. Mark the booking as 'cancelled' in the ticket_bookings table
        $sql_update_booking_status = "UPDATE ticket_bookings SET status = 'cancelled' WHERE id = ? AND user_id = ?";
        if ($stmt_update_status = $conn->prepare($sql_update_booking_status)) {
            $stmt_update_status->bind_param("ii", $booking_id_to_cancel, $user_id);
            if (!$stmt_update_status->execute()) {
                throw new Exception("Failed to update booking status: " . $stmt_update_status->error);
            }
            $stmt_update_status->close();
        } else {
            throw new Exception("Database error preparing booking status update: " . $conn->error);
        }

        $conn->commit(); // Commit the transaction
        $redirect_status = 'success'; // Cancellation successful

    } catch (Exception $e) {
        $conn->rollback(); // Rollback on error
        error_log("Booking cancellation failed for user " . $user_id . ", booking " . $booking_id_to_cancel . ": " . $e->getMessage());
        // For security, just send a generic error to the user if it's not an authorization error
        if (strpos($e->getMessage(), "not authorized") !== false || strpos($e->getMessage(), "already been canceled") !== false || strpos($e->getMessage(), "event that has already passed") !== false) {
             $redirect_status = 'error_specific'; // Use a different status for specific error messages
             $_SESSION['cancel_error_message'] = $e->getMessage(); // Store specific message in session
        } else {
             $redirect_status = 'error';
        }
    }
}

$conn->close(); // Close database connection

// Redirect back to my-bookings.php with a status message
header("Location: my-bookings.php?cancel_status=" . $redirect_status);
exit;
?>