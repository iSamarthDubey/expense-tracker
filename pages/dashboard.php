<?php
session_start();
header('Content-Type: application/json'); // Ensure JSON responses
header('Access-Control-Allow-Origin: *'); // Allow requests from any origin
header('Access-Control-Allow-Methods: POST, GET, OPTIONS'); // Allow specific HTTP methods
header('Access-Control-Allow-Headers: Content-Type'); // Allow specific headers

include('../includes/db_connect.php'); // Include database connection

$response = [];

try {
    // Check if the user is logged in
    if (!isset($_SESSION['user'])) {
        throw new Exception('Unauthorized');
    }

    $userId = $_SESSION['user'];

    // Fetch user data
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($name, $email);
    $stmt->fetch();
    $stmt->close();

    // Fetch user expenses
    $expenses = [];
    $expenseStmt = $conn->prepare("SELECT id, date, category, description, amount, status FROM expenses WHERE user_id = ? ORDER BY date DESC"); // Note: Added 'id' for editing
    $expenseStmt->bind_param("i", $userId);
    $expenseStmt->execute();
    $result = $expenseStmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $expenses[] = $row;
    }
    $expenseStmt->close();

    // Calculate summary data
    $totalExpenses = 0;
    $monthlyExpenses = 0;
    $plannedCount = 0;
    $categories = [];
    $currentMonth = date('Y-m');

    foreach ($expenses as $expense) {
        $totalExpenses += $expense['amount'];
        if (strpos($expense['date'], $currentMonth) === 0) {
            $monthlyExpenses += $expense['amount'];
        }
        if ($expense['status'] === 'Planned') {
            $plannedCount++;
        }
        if (!isset($categories[$expense['category']])) {
            $categories[$expense['category']] = 0;
        }
        $categories[$expense['category']] += $expense['amount'];
    }

    // Calculate monthly totals for the chart
    $monthlyTotals = array_fill(0, 12, 0);
    foreach ($expenses as $expense) {
        $month = (int)date('m', strtotime($expense['date'])) - 1;
        $monthlyTotals[$month] += $expense['amount'];
    }

    $conn->close();

    // Prepare the response data
    $response = [
        'name' => $name,
        'email' => $email,
        'summary' => [
            'total_expenses' => $totalExpenses,
            'monthly_expenses' => $monthlyExpenses,
            'planned' => $plannedCount,
            'categories' => count($categories)
        ],
        'expenses' => $expenses,
        'monthlyTotals' => $monthlyTotals
    ];
} catch (Exception $e) {
    $response = ['error' => $e->getMessage()];
}

// Return the data as JSON
echo json_encode($response);
?>
