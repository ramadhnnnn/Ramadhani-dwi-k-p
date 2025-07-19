<?php
$host = "localhost"; // Biasanya localhost untuk Hostinger
$username = "u524099158_puzzle"; // Ganti dengan username database Anda
$password = "RAMAdhani123"; // Ganti dengan password database Anda
$dbname = "u524099158_puzzle"; // Ganti dengan nama database Anda

// Tambahkan penanganan error yang lebih baik
try {
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Tidak dapat terhubung ke database"]);
        exit();
    }
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Tidak dapat terhubung ke database"]);
    exit();
}
?>