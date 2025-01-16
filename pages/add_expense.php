<?php
// Start a session to access user data
session_start();

// Set the content type to JSON for proper API response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow requests from any origin
header('Access-Control-Allow-Methods: POST, GET, OPTIONS'); // Allow specific HTTP methods
header('Access-Control-Allow-Headers: Content-Type'); // Allow specific headers

// Include the database connection file
include('../includes/db_connect.php');

// Ensure the user is logged in before processing the request
if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Unauthorized']); // Respond with an error if the user is not logged in
    exit();
}

// Ensure the request method is POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve the form data sent via POST request
    $date = $_POST['date'] ?? null; // Expense date
    $category = $_POST['category'] ?? null; // Expense category
    $description = $_POST['description'] ?? null; // Expense description
    $amount = $_POST['amount'] ?? null; // Expense amount
    $status = $_POST['status'] ?? null; // Expense status

    // Validate that all required fields are present
    if (!$date || !$category || !$description || !$amount || !$status) {
        echo json_encode(['error' => 'All fields are required.']);
        exit();
    }

    // Get the user ID from the session
    $userId = $_SESSION['user'];

    // Prepare the SQL query to insert the new expense into the database
    $stmt = $conn->prepare("INSERT INTO expenses (user_id, date, category, description, amount, status) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        echo json_encode(['error' => 'Database error: ' . $conn->error]); // Respond with an error if the statement preparation fails
        exit();
    }

    // Bind the parameters to the SQL query
    $stmt->bind_param("isssds", $userId, $date, $category, $description, $amount, $status);

    // Execute the query and check if it was successful
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Expense added successfully', 'id' => $stmt->insert_id]); // Respond with success message and the new expense ID
    } else {
        echo json_encode(['error' => 'Error adding expense: ' . $stmt->error]); // Respond with an error if the execution fails
    }

    // Close the statement and database connection
    $stmt->close();
    $conn->close();
}
?>
