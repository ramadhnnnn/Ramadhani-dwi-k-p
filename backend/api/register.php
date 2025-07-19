<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: https://games.teluapp.org"); 
header("Access-Control-Allow-Credentials: true"); 
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Include database configuration
include __DIR__ . '/../config/database.php';

// Check database connection
if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit();
}

// Get JSON data from frontend
$data = json_decode(file_get_contents("php://input"));

// Check received data
if ($data === null) {
    echo json_encode(["status" => "error", "message" => "No valid data received"]);
    exit();
}

// Validate required data
if (!isset($data->username) || !isset($data->email) || !isset($data->password)) {
    echo json_encode(["status" => "error", "message" => "Username, email, and password are required"]);
    exit();
}

// Sanitize and validate inputs
$username = trim($data->username);
$email = trim($data->email);
$password = $data->password;

// Username validation
if (strlen($username) < 3) {
    echo json_encode(["status" => "error", "message" => "Username must be at least 3 characters long"]);
    exit();
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    echo json_encode(["status" => "error", "message" => "Username can only contain letters, numbers, and underscores"]);
    exit();
}

// Email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "error", "message" => "Invalid email format"]);
    exit();
}

// Password validation
if (strlen($password) < 8) {
    echo json_encode(["status" => "error", "message" => "Password must be at least 8 characters long"]);
    exit();
}

// Check password strength
if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    echo json_encode([
        "status" => "error", 
        "message" => "Password must contain at least one uppercase letter, one lowercase letter, and one number"
    ]);
    exit();
}

// Hash password securely with bcrypt
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Check if username already exists
$checkQuery = "SELECT id FROM users WHERE username = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Username already exists"]);
    exit();
}

// Check if email already exists
$checkQuery = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Email already in use"]);
    exit();
}

// Insert new user with transaction for better data integrity
$conn->begin_transaction();

try {
    // Insert user
    $query = "INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $username, $email, $hashedPassword);
    
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    
    $userId = $conn->insert_id;
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        "status" => "success", 
        "message" => "User registered successfully",
        "user_id" => $userId
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    $error = $e->getMessage();
    error_log("Registration error: " . $error);
    echo json_encode(["status" => "error", "message" => "Failed to register user: " . $error]);
}

// Close database connection
$stmt->close();
$conn->close();
?>