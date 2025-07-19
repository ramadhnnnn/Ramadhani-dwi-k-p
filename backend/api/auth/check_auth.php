<?php
// check_auth.php
session_start();
header("Access-Control-Allow-Origin: https://games.teluapp.org");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "Not authenticated"
    ]);
    exit;
}

echo json_encode([
    "status" => "success",
    "user_id" => $_SESSION['user_id']
]);
?>