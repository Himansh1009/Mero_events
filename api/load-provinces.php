<?php
// api/load-provinces.php

// Set header for JSON response
header('Content-Type: application/json');

// Use ../includes/config.php for database connection.
require_once '../includes/config.php'; // Path from api/ to includes/

$provinces = [];

try {
    // Select all unique provinces from the locations table
    $sql = "SELECT DISTINCT province FROM locations ORDER BY province ASC";
    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $provinces[] = $row['province'];
        }
        $result->free(); // Free result set
    } else {
        // Log SQL error, but return empty array to client for graceful handling
        error_log("SQL Error in load-provinces.php: " . $conn->error);
    }
} catch (Exception $e) {
    // Log general PHP errors/exceptions
    error_log("PHP Exception in load-provinces.php: " . $e->getMessage());
} finally {
    // Close connection properly
    $conn->close();
}

// Return provinces as JSON array
echo json_encode($provinces);
?>