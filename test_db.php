<?php
// Include the database connection file
include('includes/db_connect.php');

// Check if the connection is successful
if ($conn->connect_error) {
    // If there is an error, display the error message
    echo "Connection failed: " . $conn->connect_error;
} else {
    // If connection is successful, display a success message
    echo "Database connection successful!";
}

// Close the database connection
$conn->close();
?>
