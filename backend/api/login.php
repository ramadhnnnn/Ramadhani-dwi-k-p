<?php
session_start();
header("Access-Control-Allow-Origin: https://games.teluapp.org");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
// Include database configuration
include __DIR__ . '/../config/database.php';
// Check database connection
if (!$conn) {
    echo json_encode([
        "status" => "error", 
        "message" => "Database connection failed"
    ]);
    exit();
}
// Get and validate JSON input
$input = file_get_contents("php://input");
$data = json_decode($input);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        "status" => "error", 
        "message" => "Invalid JSON data"
    ]);
    exit();
}
// Validate required fields
if (!isset($data->username) || !isset($data->password)) {
    echo json_encode([
        "status" => "error",
        "message" => "Username and password are required"
    ]);
    exit();
}
// Basic input validation
$username = trim($data->username);
$password = $data->password;

if (strlen($username) < 3) {
    echo json_encode([
        "status" => "error",
        "message" => "Username must be at least 3 characters long"
    ]);
    exit();
}

if (strlen($password) < 6) {
    echo json_encode([
        "status" => "error",
        "message" => "Password must be at least 6 characters long"
    ]);
    exit();
}

// Prepare login logic
// Use prepared statements to prevent SQL injection
$query = "SELECT id, password, username FROM users WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Verify password
    if (password_verify($password, $user['password'])) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR']; // Store IP for additional security
        
        // Set session cookie parameters - PHP 7.3+ syntax
        $params = session_get_cookie_params();
        setcookie(session_name(), session_id(), [
            'expires' => time() + 86400, // 1 day
            'path' => '/', 
            'domain' => $params['domain'],
            'secure' => true,
            'httponly' => true,
            'samesite' => 'None' // Use 'None' for cross-site requests
        ]);
        
        echo json_encode([
            "status" => "success",
            "user_id" => $user['id'],
            "message" => "Login successful"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid password"
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "User not found"
    ]);
}

// Close database connection
$stmt->close();
$conn->close();
?>