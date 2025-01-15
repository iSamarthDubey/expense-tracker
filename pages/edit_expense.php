<?php
// Start a session to access user data
session_start();

// Set the content type to JSON for proper API response
header('Content-Type: application/json');

// Include the database connection file
include('../includes/db_connect.php');

// Ensure the user is logged in before processing the request
if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Unauthorized']); // Respond with an error if the user is not logged in
    exit();
}

$user_id = $_SESSION['user'];

// Retrieve the form data sent via POST request
$id = $_POST['id'] ?? null; // Expense ID to edit
$date = $_POST['date'] ?? null; // Updated date of the expense
$category = $_POST['category'] ?? null; // Updated category
$description = $_POST['description'] ?? null; // Updated description
$amount = $_POST['amount'] ?? null; // Updated amount
$status = $_POST['status'] ?? null; // Updated Status

// Validate that all required fields are present
if (!$id || !$date || !$category || !$description || !$amount || !$status) {
    echo json_encode(['error' => 'All fields are required.']);
    exit();
}

// First verify the expense belongs to the user
$checkStmt = $conn->prepare("SELECT id FROM expenses WHERE id = ? AND user_id = ?");
$checkStmt->bind_param("ii", $id, $user_id);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Expense not found or unauthorized']);
    exit();
}
$checkStmt->close();

// Prepare the SQL query to update the expense in the database
$updateStmt = $conn->prepare("UPDATE expenses SET date = ?, category = ?, description = ?, amount = ?, status = ? WHERE id = ? AND user_id = ?");
if (!$updateStmt) {
    echo json_encode(['error' => 'Database error: ' . $conn->error]); // Respond with an error if the statement preparation fails
    exit();
}

// Bind the parameters to the SQL query
$updateStmt->bind_param("sssdsii", $date, $category, $description, $amount, $status, $id, $user_id);

// Execute the query and check if it was successful
if ($updateStmt->execute()) {
    echo json_encode(['success' => 'Expense updated successfully']); // Respond with success message
} else {
    echo json_encode(['error' => 'Error updating expense: ' . $updateStmt->error]); // Respond with an error if the execution fails
}

// Close the statement and database connection
$updateStmt->close();
$conn->close();
?>
