<?php
session_start();
header('Content-Type: application/json');
include('../includes/db_connect.php');

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user'];
$expense_id = $_POST['id'] ?? null;

if (!$expense_id) {
    echo json_encode(['error' => 'Expense ID is required']);
    exit();
}

// First verify the expense belongs to the user
$checkStmt = $conn->prepare("SELECT id FROM expenses WHERE id = ? AND user_id = ?");
$checkStmt->bind_param("ii", $expense_id, $user_id);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Expense not found or unauthorized']);
    exit();
}
$checkStmt->close();

// Now proceed with the delete
$deleteStmt = $conn->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
$deleteStmt->bind_param("ii", $expense_id, $user_id);

if ($deleteStmt->execute() && $deleteStmt->affected_rows > 0) {
    echo json_encode(['success' => 'Expense deleted successfully']);
} else {
    echo json_encode(['error' => 'Failed to delete expense']);
}

$deleteStmt->close();
$conn->close();
?>
