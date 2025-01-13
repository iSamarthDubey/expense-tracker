<?php
session_start();
header('Content-Type: application/json'); // Ensure JSON responses
include('../includes/db_connect.php'); // Include database connection

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Check for empty fields
    if (empty($email) || empty($password)) {
        echo json_encode(['error' => 'Email and password are required.']);
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Invalid email address.']);
        exit();
    }

    // Prepare query to check user credentials
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    if (!$stmt) {
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($userId, $dbPassword);
    $stmt->fetch();

    if (password_verify($password, $dbPassword)) {
        $_SESSION['user'] = $userId; // Store user ID in session
        echo json_encode(['success' => 'Login successful!']);
    } else {
        echo json_encode(['error' => 'Invalid email or password.']);
    }

    $stmt->close();
    $conn->close();
}
?>
