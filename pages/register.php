<?php
session_start();
header('Content-Type: application/json'); // Ensure JSON responses
include('../includes/db_connect.php'); // Include database connection

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['regName']);
    $email = trim($_POST['regEmail']);
    $password = $_POST['regPassword'];
    $confirmPassword = $_POST['regConfirmPassword'];

    // Check for empty fields
    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        echo json_encode(['error' => 'All fields are required.']);
        exit();
    }

    // Validate passwords match
    if ($password !== $confirmPassword) {
        echo json_encode(['error' => 'Passwords do not match.']);
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Invalid email address.']);
        exit();
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Check if the email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$stmt) {
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(['error' => 'Email is already registered.']);
        $stmt->close();
        exit();
    }

    $stmt->close();

    // Insert new user into the database
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())");
    if (!$stmt) {
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("sss", $name, $email, $hashedPassword);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Registration successful!']);
    } else {
        echo json_encode(['error' => 'Error during registration: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
?>
