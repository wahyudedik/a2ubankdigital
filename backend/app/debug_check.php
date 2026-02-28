<?php
// File debug untuk cek masalah
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Debug Check</h2>";

// 1. Cek PHP Version
echo "<h3>1. PHP Version</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Required: 8.0+<br><br>";

// 2. Cek file .env
echo "<h3>2. File .env</h3>";
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    echo "✅ File .env ADA di: " . $envPath . "<br>";
    echo "Ukuran: " . filesize($envPath) . " bytes<br><br>";
} else {
    echo "❌ File .env TIDAK ADA di: " . $envPath . "<br>";
    echo "SOLUSI: Copy .env.production jadi .env<br><br>";
}

// 3. Cek folder vendor
echo "<h3>3. Folder vendor</h3>";
$vendorPath = __DIR__ . '/vendor';
if (is_dir($vendorPath)) {
    echo "✅ Folder vendor ADA di: " . $vendorPath . "<br>";
    
    // Cek autoload.php
    $autoloadPath = $vendorPath . '/autoload.php';
    if (file_exists($autoloadPath)) {
        echo "✅ File autoload.php ADA<br><br>";
    } else {
        echo "❌ File autoload.php TIDAK ADA<br>";
        echo "SOLUSI: Upload ulang folder vendor/<br><br>";
    }
} else {
    echo "❌ Folder vendor TIDAK ADA di: " . $vendorPath . "<br>";
    echo "SOLUSI: Upload folder vendor/ dari local<br><br>";
}

// 4. Cek permissions
echo "<h3>4. Permissions</h3>";
echo "backend/app/: " . substr(sprintf('%o', fileperms(__DIR__)), -4) . "<br>";
if (file_exists($envPath)) {
    echo "backend/.env: " . substr(sprintf('%o', fileperms($envPath)), -4) . "<br>";
}
echo "Recommended: 755 untuk folder, 644 untuk .env<br><br>";

// 5. Test load .env
echo "<h3>5. Test Load .env</h3>";
if (file_exists($vendorPath . '/autoload.php')) {
    try {
        require_once $vendorPath . '/autoload.php';
        echo "✅ Autoload berhasil<br>";
        
        if (file_exists($envPath)) {
            $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
            $dotenv->load();
            echo "✅ .env berhasil di-load<br>";
            
            // Cek variabel penting
            echo "<br><strong>Variabel .env:</strong><br>";
            echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? '❌ TIDAK ADA') . "<br>";
            echo "DB_NAME: " . ($_ENV['DB_NAME'] ?? '❌ TIDAK ADA') . "<br>";
            echo "DB_USER: " . ($_ENV['DB_USER'] ?? '❌ TIDAK ADA') . "<br>";
            echo "DB_PASS: " . (isset($_ENV['DB_PASS']) ? '✅ ADA (hidden)' : '❌ TIDAK ADA') . "<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Tidak bisa test karena vendor tidak ada<br>";
}

echo "<br><hr>";
echo "<h3>KESIMPULAN:</h3>";
echo "Jika ada ❌, perbaiki dulu sebelum lanjut!";
?>
