<?php
session_start();
include('includes/db_connect.php'); // Include database connection

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.html'); // Redirect to login page if not authenticated
    exit();
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
        error_log("Found Planned expense: " . json_encode($expense)); // Debug log
    }
    if (!isset($categories[$expense['category']])) {
        $categories[$expense['category']] = 0;
    }
    $categories[$expense['category']] += $expense['amount'];
}

error_log("Initial Planned Count: " . $plannedCount); // Debug log

// Calculate monthly totals for the chart
$monthlyTotals = array_fill(0, 12, 0);
foreach ($expenses as $expense) {
    $month = (int)date('m', strtotime($expense['date'])) - 1;
    $monthlyTotals[$month] += $expense['amount'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Tracker - Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css"> <!-- Linking the external CSS stylesheet for styling -->
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h1 class="text-2xl font-bold mb-8">Expense Tracker</h1>
            <div class="mb-8">
                <div class="font-medium">Welcome, <?php echo htmlspecialchars($name); ?>!</div>
                <div class="text-sm text-gray-400"><?php echo htmlspecialchars($email); ?></div>
            </div>
            <nav>
                <a href="#" class="block mb-4 text-blue-400">Dashboard</a>
                <a href="pages/logout.php" class="block mb-4 text-red-400">Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <h2 class="text-2xl font-bold mb-6">Overview</h2>

            <!-- Summary Cards -->
            <div class="grid grid-cols-4 gap-6 mb-8">
                <div class="card">
                    <div>Total Expenses</div>
                    <div class="summary-value">₹<?php echo number_format($totalExpenses, 2); ?></div>
                </div>
                <div class="card">
                    <div>This Month</div>
                    <div class="summary-value">₹<?php echo number_format($monthlyExpenses, 2); ?></div>
                </div>
                <div class="card">
                    <div>Planned</div> <!-- Changed from 'Pending' to 'Planned' -->
                    <div class="summary-value"><?php echo $plannedCount; ?></div> <!-- Changed from $pendingCount to $plannedCount -->
                </div>
                <div class="card">
                    <div>Categories</div>
                    <div class="summary-value"><?php echo count($categories); ?></div>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-2 gap-6 mb-8">
                <div class="card">
                    <h3 class="text-lg font-medium mb-4">Expense Trend</h3>
                    <div class="chart-wrapper">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
                <div class="card">
                    <h3 class="text-lg font-medium mb-4">Category Distribution</h3>
                    <div class="chart-wrapper">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Add Expense Button -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Your Expenses</h2>
                <button class="btn btn-primary" onclick="showModal('addExpenseModal')">Add Expense</button>
            </div>
            
            <!------ Expense Table ----->

            <!-- Recent Expenses Table -->
            <div class="card">
                <h3 class="text-lg font-medium mb-4">Recent Expenses</h3>
                <table class="table-auto w-full">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th> <!-- Added Actions column -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expenses)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No expenses found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($expenses as $expense): ?>
                                
                                <tr data-id="<?= $expense['id'] ?>">
                                    <td><?php echo htmlspecialchars($expense['date']); ?></td>
                                    <td><?php echo htmlspecialchars($expense['category']); ?></td>
                                    <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                    <td>₹<?php echo number_format($expense['amount'], 2); ?></td>
                                    <td><span class="status-badge status-<?php echo strtolower($expense['status']); ?>"><?php echo htmlspecialchars($expense['status']); ?></span></td>
                                    
                                    <td>
                                    <button class="text-blue-600 hover:text-blue-700" onclick="editExpense(this, '<?= $expense['id'] ?>')">Edit</button>
                                    <button class="text-red-600 hover:text-red-700" onclick="deleteExpense(this)">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <?php
    // Add initialData to the page for JavaScript
    ?>
    <input type="hidden" id="initialData" value="<?php echo htmlspecialchars(json_encode([
        'totalExpenses' => $totalExpenses,
        'monthlyExpenses' => $monthlyExpenses,
        'plannedCount' => $plannedCount, // Changed from 'pendingCount' to 'plannedCount'
        'categories' => $categories,
        'monthlyTotals' => array_values($monthlyTotals),
        'expenses' => $expenses
    ]), ENT_QUOTES, 'UTF-8'); ?>">

    <!--- Modals (Add and Edit Expense) --->
    
    <!-- Add Modal: Same as your version -->
    <div id="addExpenseModal" class="modal">
    <div class="modal-content">
        <h2 class="text-xl font-bold mb-4">Add New Expense</h2>
        <!-- The form for adding a new expense -->
        <form id="expenseForm" onsubmit="addExpense(event)">
            <!-- Input field for selecting the expense date -->
            <div class="form-group">
                <label for="date">Date</label>
                <input type="date" id="date" required>
            </div>

            <!-- Dropdown to select the expense category -->
            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" required>
                    <option value="Travel">Travel</option>
                    <option value="Office">Office</option>
                    <option value="Meals">Meals</option>
                    <option value="Utilities">Utilities</option>
                    <option value="Others">Others</option>
                </select>
            </div>

            <!-- Input field for providing a description of the expense -->
            <div class="form-group">
                <label for="description">Description</label>
                <input type="text" id="description" required>
            </div>

            <!-- Input field for entering the expense amount -->
            <div class="form-group">
                <label for="amount">Amount</label>
                <input type="number" id="amount" step="0.01" required>
            </div>
            
            <!-- Input field for entering the status -->
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" required>
                    <option value="Approved">Approved</option>
                    <option value="Planned">Planned</option>
                    <option value="Unwanted">Unwanted</option>
                </select>
            </div>

            <!-- Buttons to cancel or submit the form -->
            <div class="flex justify-end gap-4">
                <button type="button" class="btn" onclick="hideModal('addExpenseModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Expense</button>
            </div>
        </form>
    </div>
</div>

    <!-- Edit Modal: Same as your version -->
    <div id="editExpenseModal" class="modal">
    <div class="modal-content">
        <h2 class="text-xl font-bold mb-4">Edit Expense</h2>
        <!-- Edit Expense Form -->
        <form id="editExpenseForm" onsubmit="updateExpense(event)">
            <input type="hidden" id="editExpenseId"> <!-- Hidden field for expense ID -->
            <div class="form-group">
                <label for="editDate">Date</label>
                <input type="date" id="editDate" required>
            </div>
            <div class="form-group">
                <label for="editCategory">Category</label>
                <select id="editCategory" required>
                    <option value="Travel">Travel</option>
                    <option value="Office">Office</option>
                    <option value="Meals">Meals</option>
                    <option value="Utilities">Utilities</option>
                    <option value="Others">Others</option>
                </select>
            </div>
            <div class="form-group">
                <label for="editDescription">Description</label>
                <input type="text" id="editDescription" required>
            </div>
            <div class="form-group">
                <label for="editAmount">Amount</label>
                <input type="number" id="editAmount" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="editStatus">Status</label>
                <select id="editStatus" required>
                    <option value="Approved">Approved</option>
                    <option value="Planned">Planned</option>
                    <option value="Unwanted">Unwanted</option>
                </select>
            </div>
            <div class="flex justify-end gap-4">
                <button type="button" class="btn" onclick="hideModal('editExpenseModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Expense</button>
            </div>
        </form>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- External JavaScript file for form interactions and functionality -->
    <script src="assets/js/dashboard.js"></script>

</body>
</html>
