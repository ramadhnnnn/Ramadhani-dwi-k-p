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

// Hapus semua variabel session
$_SESSION = array();

// Hapus cookie session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan session
session_destroy();

// Hapus cookie tambahan jika ada
// Ini akan menghapus semua cookie di domain utama dan subdomain
$domain = $_SERVER['HTTP_HOST'];
$domain = preg_replace('/^www\./', '', $domain); // Hilangkan 'www.' jika ada
$cookies = $_COOKIE;

foreach ($cookies as $name => $value) {
    setcookie($name, '', time() - 3600, '/');
    setcookie($name, '', time() - 3600, '/', $domain);
    setcookie($name, '', time() - 3600, '/', '.' . $domain);
    setcookie($name, '', time() - 3600, '/8puzzle');
    setcookie($name, '', time() - 3600, '/8puzzle', $domain);
    setcookie($name, '', time() - 3600, '/8puzzle', '.' . $domain);
}

// Kirim respons sukses
echo json_encode([
    "status" => "success",
    "message" => "Successfully logged out"
]);
?>