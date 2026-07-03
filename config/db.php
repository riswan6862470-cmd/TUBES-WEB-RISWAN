<?php
// ============================================================
// Smart Waste Management System - Database Configuration
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'waste_management_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Database Error</title>
        <style>
            body { font-family: Arial, sans-serif; background: #1a1a2e; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .error-box { background: #fff; border-radius: 12px; padding: 40px; text-align: center; max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,0.5); }
            h2 { color: #e74c3c; } p { color: #555; } code { background: #f5f5f5; padding: 5px 10px; border-radius: 5px; font-size: 13px; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h2>⚠️ Koneksi Database Gagal</h2>
            <p>Pastikan XAMPP MySQL sudah aktif dan database <strong>waste_management_db</strong> sudah dibuat.</p>
            <p><code>' . $conn->connect_error . '</code></p>
            <p>Import file <strong>database.sql</strong> ke phpMyAdmin terlebih dahulu.</p>
        </div>
    </body>
    </html>
    ');
}

$conn->set_charset('utf8mb4');

// ============================================================
// Helper: sanitize input
// ============================================================
function sanitize($conn, $data) {
    return $conn->real_escape_string(trim(htmlspecialchars($data)));
}

// ============================================================
// Helper: redirect
// ============================================================
function redirect($url) {
    header("Location: $url");
    exit();
}

// ============================================================
// Helper: is admin logged in
// ============================================================
function isAdmin() {
    return isset($_SESSION['admin_id']);
}

// ============================================================
// Helper: is user logged in
// ============================================================
function isUser() {
    return isset($_SESSION['user_id']);
}

// ============================================================
// Helper: require admin login
// ============================================================
function requireAdmin() {
    if (!isAdmin()) {
        redirect('../login.php');
    }
}

// ============================================================
// Helper: require user login
// ============================================================
function requireUser() {
    if (!isUser()) {
        redirect('../login.php');
    }
}
?>
