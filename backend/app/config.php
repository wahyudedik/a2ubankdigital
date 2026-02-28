<?php
// File: app/config.php (VERSI FINAL & STABIL)
// Penjelasan: Menyesuaikan path vendor sesuai struktur server.

// --- 1. Aktifkan Error Reporting (HAPUS DI PRODUKSI) ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- 2. Definisikan Path Root Proyek ---
// __DIR__ adalah folder 'app', jadi dirname(__DIR__) adalah folder root proyek Anda.
define('BASE_PATH', dirname(__DIR__));

// --- 3. Autoloader Composer ---
// Cek apakah vendor ada di app/ atau di parent directory
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} elseif (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
} else {
    die('Error: Composer autoload not found. Please run "composer install"');
}

// --- 4. Memuat Variabel Lingkungan (.env) ---
// Path ke file .env sudah benar karena .env ada di root (BASE_PATH).
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

// --- 5. Penanganan Header & CORS ---
if (php_sapi_name() !== 'cli') {
    $allowed_origins = array_map('trim', explode(',', $_ENV['ALLOWED_ORIGINS'] ?? ''));
    $request_origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if (in_array($request_origin, $allowed_origins)) {
        header("Access-Control-Allow-Origin: " . $request_origin);
    }

    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit();
    }
    header("Content-Type: application/json");
}

// --- 6. Konfigurasi Koneksi Database (PDO) ---
$db_host = $_ENV['DB_HOST'];
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        die(json_encode(['status' => 'error', 'message' => 'Koneksi database gagal. Detail: ' . $e->getMessage()]));
    } else {
        die("Koneksi database gagal: " . $e->getMessage() . "\n");
    }
}

