<?php
// Konfigurasi database
$host = "localhost";
$username = "u524099158_games";
$password = "CVd/Y?Rl:Cn6";     
$dbname = "u524099158_games";

// Membuat koneksi ke database
$conn = new mysqli($host, $username, $password, $dbname);

// Cek apakah koneksi berhasil
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Set timezone (untuk menghindari masalah waktu)
date_default_timezone_set('Asia/Jakarta');
?>
