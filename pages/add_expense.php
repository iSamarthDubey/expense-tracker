<?php
session_start();
header('Content-Type: application/json'); // Set the response type to JSON
include('../includes/db_connect.php'); // Include the database connection file

// Ensure the user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Unauthorized']); // Respond with an error if not logged in
    exit();
}

// Retrieve the user ID from the session
$userId = $_SESSION['user'];

// Get the form data sent via POST
$date = $_POST['date'] ?? null;
$category = $_POST['category'] ?? null;
$description = $_POST['description'] ?? null;
$amount = $_POST['amount'] ?? null;
$status = $_POST['status'] ?? null;

// Validate the inputs
if (!$date || !$category || !$description || !$amount) || !$status) {
    echo json_encode(['error' => 'All fields are required.']);
    exit();
}

// Insert the new expense into the database
$stmt = $conn->prepare("INSERT INTO expenses (user_id, date, category, description, amount, status) VALUES (?, ?, ?, ?, ?, 'Approved')");
$stmt->bind_param("isssd", $userId, $date, $category, $description, $amount, $status);

if ($stmt->execute()) {
    echo json_encode(['success' => 'Expense added successfully']); // Respond with success
} else {
    echo json_encode(['error' => 'Error adding expense: ' . $conn->error]); // Respond with an error
}

$stmt->close(); // Close the prepared statement
$conn->close(); // Close the database connection
?>
