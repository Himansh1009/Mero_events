<?php
// api/load-cities.php

// Set header for JSON response
header('Content-Type: application/json');

// Use ../includes/config.php for database connection.
require_once '../includes/config.php'; // Path from api/ to includes/

// Get selected province via GET param
$province_name = $_GET['province'] ?? ''; 
$cities = []; // Initialize empty array for cities

// Basic input validation
if (empty($province_name)) {
    // If no province is provided, return empty array immediately
    echo json_encode($cities);
    $conn->close();
    exit();
}

try {
    // Select cities matching the selected province using a prepared statement
    $sql = "SELECT city FROM locations WHERE province = ? ORDER BY city ASC";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $province_name);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $cities[] = $row['city'];
                }
            }
            $result->free();
        } else {
            // Log SQL error during execution
            error_log("SQL Error in load-cities.php: " . $stmt->error);
        }
        $stmt->close(); // Close statement
    } else {
        // Log database preparation error
        error_log("Database error preparing statement in load-cities.php: " . $conn->error);
    }
} catch (Exception $e) {
    // Log general PHP exceptions
    error_log("PHP Exception in load-cities.php: " . $e->getMessage());
} finally {
    // Close connection properly
    $conn->close();
}

// Return cities as JSON array
echo json_encode($cities);
?>