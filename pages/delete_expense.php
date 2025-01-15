<?php
session_start();
header('Content-Type: application/json');
include('../includes/db_connect.php');

// Check authentication
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

// Verify expense belongs to user
$stmt = $conn->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $expense_id, $user_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => 'Expense deleted successfully']);
} else {
    echo json_encode(['error' => 'Failed to delete expense']);
}

$stmt->close();
$conn->close();
?>
