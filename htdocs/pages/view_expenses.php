<?php
session_start(); // Start the session

// Include the database connection file
include('../includes/db_connect.php'); // Adjust path if necessary

// Error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../index.html"); // Redirect to login page if not logged in
    exit();
}

// Get the logged-in user's expenses
$userId = $_SESSION['user'];
$stmt = $conn->prepare("SELECT amount, description, created_at FROM expenses WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

// Fetch expenses data
while ($row = $result->fetch_assoc()) {
    echo "Amount: " . $row['amount'] . " Description: " . $row['description'] . " Date: " . $row['created_at'] . "<br>";
}

$stmt->close();
$conn->close();
?>
