<?php
// check_login.php - Script untuk memeriksa autentikasi pengguna
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

// Periksa apakah session ada
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "Not authenticated"
    ]);
    exit;
}

// Tambahan keamanan: periksa IP address untuk mencegah session hijacking
if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
    // IP address berubah, kemungkinan session hijacking
    // Hancurkan session dan beritahu pengguna untuk login kembali
    session_unset();
    session_destroy();
    
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "Session invalid. Please login again."
    ]);
    exit;
}

// Tambahan keamanan: periksa masa berlaku session
$session_lifetime = 86400; // 24 jam dalam detik
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $session_lifetime)) {
    // Session telah kadaluarsa
    session_unset();
    session_destroy();
    
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "Session expired. Please login again."
    ]);
    exit;
}

// Jika semua pemeriksaan berhasil, perbarui waktu login untuk memperpanjang session
$_SESSION['login_time'] = time();

// Berikan respons sukses
echo json_encode([
    "status" => "success",
    "user_id" => $_SESSION['user_id'],
    "username" => $_SESSION['username']
]);
?>