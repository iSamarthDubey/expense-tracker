// Move initialData declaration before its usage
const initialData = JSON.parse(document.getElementById('initialData').value);
console.log('Initial Data:', initialData); // Add this line to log initial data

// Update variables with initial data
let totalExpenses = initialData.totalExpenses;
let monthlyExpenses = initialData.monthlyExpenses;
let plannedCount = initialData.plannedCount; // Changed from 'pendingCount' to 'plannedCount'
let expenses = initialData.expenses || [];
let expenseChart, categoryChart;

const chartData = {
    expenses: initialData.monthlyTotals,
    categories: initialData.categories
};

function initializeCharts() {
    console.log('Initializing Charts with Data:', chartData); // Add this line to log chart data
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    expenseChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Expenses (₹)',
                data: chartData.expenses,
                borderColor: 'rgba(37, 99, 235, 0.7)',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: true } }
        }
    });

    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    categoryChart = new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(chartData.categories),
            datasets: [{
                data: Object.values(chartData.categories),
                backgroundColor: ['#2563eb', '#1d4ed8', '#9333ea', '#ef4444', '#14b8a6']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: true } }
        }
    });
}

function updateCharts(amount, category) {
    // Update expense trend
    chartData.expenses[chartData.expenses.length - 1] += parseFloat(amount);
    expenseChart.update();

    // Update category distribution
    chartData.categories[category] += parseFloat(amount);
    categoryChart.data.datasets[0].data = Object.values(chartData.categories);
    categoryChart.update();
}

// Add this new function to recalculate all totals
function recalculateTotals() {
    const rows = document.querySelectorAll('.data-table tbody tr');
    
    // Reset all counters
    totalExpenses = 0;
    monthlyExpenses = 0;
    plannedCount = 0;
    const currentMonth = new Date().toISOString().slice(0, 7); // YYYY-MM
    
    // Reset category totals
    Object.keys(chartData.categories).forEach(key => {
        chartData.categories[key] = 0;
    });

    // Reset monthly totals
    chartData.expenses = Array(12).fill(0);

    // Recalculate from current table data
    rows.forEach(row => {
        const amount = parseFloat(row.children[3].textContent.replace('₹', ''));
        const category = row.children[1].textContent.trim();
        const status = row.children[4].querySelector('.status-badge').textContent;
        const date = row.children[0].textContent.trim();
        
        // Update totals
        totalExpenses += amount;
        
        // Update monthly expenses
        if (date.startsWith(currentMonth)) {
            monthlyExpenses += amount;
        }
        
        // Update planned count
        if (status === 'Planned') {
            plannedCount++;
        }
        
        // Update category totals
        if (!chartData.categories[category]) {
            chartData.categories[category] = 0;
        }
        chartData.categories[category] += amount;
        
        // Update monthly trend
        const month = new Date(date).getMonth();
        chartData.expenses[month] += amount;
    });

    // Update UI
    updateStats();
    expenseChart.update();
    categoryChart.update();
}

// Update form submission handler to include status
async function addExpense(event) {
    event.preventDefault();
    console.log('Adding expense...');

    const formData = {
        date: document.getElementById('date').value,
        category: document.getElementById('category').value,
        description: document.getElementById('description').value,
        amount: parseFloat(document.getElementById('amount').value),
        status: document.getElementById('status').value
    };

    try {
        const response = await fetch('pages/add_expense.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(formData)
        });

        const result = await response.json();
        console.log('Add result:', result);

        if (result.success) {
            const tableBody = document.querySelector('.data-table tbody');
            const row = `
                <tr data-id="${result.id}">
                    <td>${formData.date}</td>
                    <td>${formData.category}</td>
                    <td>${formData.description}</td>
                    <td>₹${formData.amount.toFixed(2)}</td>
                    <td><span class="status-badge status-${formData.status.toLowerCase()}">${formData.status}</span></td>
                    <td>
                        <button class="text-blue-600 hover:text-blue-700" onclick="editExpense(this, '${result.id}')">Edit</button>
                        <button class="text-red-600 hover:text-red-700" onclick="deleteExpense(this)">Delete</button>
                    </td>
                </tr>
            `;
            tableBody.insertAdjacentHTML('afterbegin', row);
            
            // Reset form and close modal
            document.getElementById('expenseForm').reset();
            hideModal('addExpenseModal');
            
            // Update stats and charts
            recalculateTotals();
            showToast('Expense added successfully');
        } else {
            showToast(result.error || 'Failed to add expense', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Failed to add expense', 'error');
    }
}

async function submitEditExpense(event) {
    event.preventDefault();
    console.log('Submitting edit...');

    const formData = {
        id: document.getElementById('editExpenseId').value,
        date: document.getElementById('editDate').value,
        category: document.getElementById('editCategory').value,
        description: document.getElementById('editDescription').value,
        amount: document.getElementById('editAmount').value,
        status: document.getElementById('editStatus').value
    };

    try {
        const response = await fetch('pages/edit_expense.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(formData)
        });

        const result = await response.json();
        console.log('Edit result:', result);

        if (result.success) {
            const row = document.querySelector(`tr[data-id="${formData.id}"]`);
            if (row) {
                row.children[0].textContent = formData.date;
                row.children[1].textContent = formData.category;
                row.children[2].textContent = formData.description;
                row.children[3].textContent = `₹${parseFloat(formData.amount).toFixed(2)}`;
                
                const statusBadge = row.children[4].querySelector('.status-badge');
                statusBadge.textContent = formData.status;
                statusBadge.className = `status-badge status-${formData.status.toLowerCase()}`;
            }

            // Reset form and close modal
            document.getElementById('editExpenseForm').reset();
            hideModal('editExpenseModal');
            
            // Update stats and charts
            recalculateTotals();
            showToast('Expense updated successfully');
        } else {
            showToast(result.error || 'Failed to update expense', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Failed to update expense', 'error');
    }
}

// Add this showToast function if not already present
function showToast(message, type = 'success') {
    alert(message); // Simple alert - replace with better toast implementation if needed
}

async function deleteExpense(button) {
    if (!confirm('Are you sure you want to delete this expense?')) {
        return;
    }

    const row = button.closest('tr');
    const expenseId = row.getAttribute('data-id');

    try {
        const response = await fetch('pages/delete_expense.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ id: expenseId })
        });

        const result = await response.json();

        if (result.success) {
            row.remove();
            recalculateTotals();
            showToast('Expense deleted successfully');
        } else {
            showToast(result.error || 'Failed to delete expense', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Failed to delete expense', 'error');
    }
}

// Fix status badges class names
function changeStatus(button) {
    const statuses = ['Planned', 'Approved', 'Unwanted'];
    const row = button.closest('tr');
    const statusBadge = row.querySelector('.status-badge');
    const oldStatus = statusBadge.textContent;
    const currentIndex = statuses.indexOf(oldStatus);
    const newStatus = statuses[(currentIndex + 1) % statuses.length];
    
    // Update planned count based on status change
    if (oldStatus === 'Planned') {
        plannedCount--;
    }
    if (newStatus === 'Planned') {
        plannedCount++;
    }
    
    statusBadge.className = `status-badge status-${newStatus.toLowerCase()}`;
    statusBadge.textContent = newStatus;
    
    console.log('Status Changed:', {
        from: oldStatus,
        to: newStatus,
        plannedCount
    });
    
    updateStats();
}

function updateStats() {
    // Log the current state
    console.log('Updating Stats:', {
        totalExpenses,
        monthlyExpenses,
        plannedCount,
        categories: chartData.categories
    });

    // Update summary cards using the correct selectors
    document.querySelector('.card:nth-child(1) .summary-value').textContent = 
        `₹${totalExpenses.toLocaleString()}`;
    document.querySelector('.card:nth-child(2) .summary-value').textContent = 
        `₹${monthlyExpenses.toLocaleString()}`;
    document.querySelector('.card:nth-child(3) .summary-value').textContent = 
        plannedCount.toString();
    document.querySelector('.card:nth-child(4) .summary-value').textContent = 
        Object.keys(chartData.categories).length.toString();
}

function showModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        // Reset the corresponding form
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
        }
    }
}

function setActiveNav(element, sectionId) {
    document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
    element.classList.add('active');
}

/**
 * Opens the Edit Expense modal and pre-fills the fields with the selected expense data.
 * @param {HTMLElement} button - The Edit button clicked.
 * @param {string} expenseId - The ID of the expense to edit.
 */
function editExpense(button, expenseId) {
    // Locate the table row corresponding to the expense being edited
    const row = button.closest('tr');

    // Populate the hidden input field with the expense ID
    document.getElementById('editExpenseId').value = expenseId;

    // Pre-fill the form fields with the data from the selected row
    document.getElementById('editDate').value = row.children[0].textContent.trim(); // Fill the date
    document.getElementById('editCategory').value = row.children[1].textContent.trim(); // Fill the category
    document.getElementById('editDescription').value = row.children[2].textContent.trim(); // Fill the description
    document.getElementById('editAmount').value = parseFloat(row.children[3].textContent.replace('₹', '')); // Fill the amount
    document.getElementById('editStatus').value = row.children[4].textContent.trim(); // Fill the status

    // Display the Edit Expense modal
    showModal('editExpenseModal');
}

/**
 * Submits the updated expense data to the backend and updates the table dynamically.
 * Handles both the backend communication and frontend updates.
 */
async function submitEditExpense(event) {
    // Prevent the default form submission to handle it via JavaScript
    event.preventDefault();

    // Collect updated data from the form fields
    const formData = {
        id: document.getElementById('editExpenseId').value, // The ID of the expense being edited
        date: document.getElementById('editDate').value, // Updated date of the expense
        category: document.getElementById('editCategory').value, // Updated category of the expense
        description: document.getElementById('editDescription').value, // Updated description of the expense
        amount: parseFloat(document.getElementById('editAmount').value), // Updated amount of the expense
        status: document.getElementById('editStatus').value // Updated status of the expense
    };

    try {
        // Send the updated data to the backend using the fetch API
        const response = await fetch('pages/edit_expense.php', {
            method: 'POST', // HTTP POST method for sending data
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, // Set the content type for form data
            body: new URLSearchParams(formData) // Convert the form data into URL-encoded format
        });

        // Parse the JSON response from the backend
        const result = await response.json();

        // Check if the backend indicates success
        if (result.success) {
            // Display a success notification to the user
            showToast(result.success, 'success');

            // Find the table row matching the edited expense and update its content dynamically
            const rows = document.querySelectorAll('.data-table tbody tr');
            rows.forEach(row => {
                const editButton = row.children[5].querySelector('button');
                if (editButton.getAttribute('onclick').includes(formData.id)) {
                    row.children[0].textContent = formData.date; // Update the date
                    row.children[1].textContent = formData.category; // Update the category
                    row.children[2].textContent = formData.description; // Update the description
                    row.children[3].textContent = `₹${formData.amount.toFixed(2)}`; // Update the amount
                    const oldStatus = row.children[4].querySelector('.status-badge').textContent;
        
                    // Update planned count when status changes
                    if (oldStatus === 'Planned' && formData.status !== 'Planned') {
                        plannedCount--;
                    } else if (oldStatus !== 'Planned' && formData.status === 'Planned') {
                        plannedCount++;
                    }
                    
                    // Update the row content
                    row.children[4].querySelector('.status-badge').textContent = formData.status;
                    row.children[4].querySelector('.status-badge').className = 
                        `status-badge status-${formData.status.toLowerCase()}`;
                        
                    updateStats();
                }
            });

            // Optionally, refresh the charts and stats dynamically after editing
            updateStats(); // Recalculate and display updated summary stats
            updateCharts(formData.amount, formData.category); // Update the charts with the edited data

            // Recalculate all totals and update UI
            recalculateTotals();

            // Close the Edit Expense modal
            hideModal('editExpenseModal');
        } else {
            // Display an error notification if the backend returns an error
            showToast(result.error, 'error');
        }
    } catch (error) {
        // Handle network or backend errors by displaying an error notification
        showToast('Failed to update expense. Please try again.', 'error');
    }
}

// Add click handlers to existing table rows
document.addEventListener('DOMContentLoaded', function() {
    const existingRows = document.querySelectorAll('.data-table tbody tr');
    existingRows.forEach(row => {
        const deleteBtn = row.querySelector('td:last-child button:last-child');
        if (deleteBtn) {
            deleteBtn.onclick = () => deleteExpense(deleteBtn);
        }
        const statusBadge = row.querySelector('.status-badge');
        if (statusBadge) {
            statusBadge.onclick = () => changeStatus(statusBadge);
        }
    });
    
    initializeCharts();
    updateStats();
});

// Function to fetch and update Overview Cards dynamically
async function loadOverviewData() {
    try {
        // Fetch the data from the backend using the dashboard.php endpoint
        const response = await fetch('pages/dashboard.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        // Check if the response is OK
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        // Parse the response as JSON
        const data = await response.json();

        // Check if there is an error in the response
        if (data.error) {
            throw new Error(data.error);
        }

        // Update the Total Expenses card
        const totalExpensesElement = document.getElementById('total-expenses');
        if (totalExpensesElement) {
            totalExpensesElement.innerText = `₹${data.summary.total_expenses}`;
        }

        // Update the This Month Expenses card
        const monthlyExpensesElement = document.getElementById('monthly-expenses');
        if (monthlyExpensesElement) {
            monthlyExpensesElement.innerText = `₹${data.summary.monthly_expenses}`;
        }

        // Update the Planned card
        const plannedElement = document.getElementById('planned');
        if (plannedElement) {
            plannedElement.innerText = data.summary.planned;
        }

        // Update the Categories card
        const categoriesElement = document.getElementById('categories');
        if (categoriesElement) {
            categoriesElement.innerText = data.summary.categories;
        }
    } catch (error) {
        // Log an error if the data cannot be fetched
        console.error('Failed to load overview data:', error);
        showToast('Failed to load overview data: ' + error.message, 'error');
    }
}

// Call the function when the page loads
window.onload = function () {
    loadOverviewData();
};
