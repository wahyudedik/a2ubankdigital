<?php
// File untuk debug error 500
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Test</h1>";

// Test 1: PHP Version
echo "<h2>1. PHP Version</h2>";
echo "PHP Version: " . phpversion() . "<br>";

// Test 2: Check __DIR__
echo "<h2>2. Directory Paths</h2>";
echo "__DIR__: " . __DIR__ . "<br>";
echo "dirname(__DIR__): " . dirname(__DIR__) . "<br>";

// Test 3: Check vendor locations
echo "<h2>3. Vendor Locations</h2>";
$vendor_paths = [
    __DIR__ . '/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php',
    dirname(dirname(__DIR__)) . '/vendor/autoload.php',
];

foreach ($vendor_paths as $path) {
    $exists = file_exists($path) ? '✅ EXISTS' : '❌ NOT FOUND';
    echo "$exists: $path<br>";
}

// Test 4: Check .env location
echo "<h2>4. .env Location</h2>";
$env_paths = [
    __DIR__ . '/.env',
    dirname(__DIR__) . '/.env',
    dirname(dirname(__DIR__)) . '/.env',
];

foreach ($env_paths as $path) {
    $exists = file_exists($path) ? '✅ EXISTS' : '❌ NOT FOUND';
    echo "$exists: $path<br>";
}

// Test 5: Try to load vendor
echo "<h2>5. Try Load Vendor</h2>";
try {
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
        echo "✅ Loaded from: " . __DIR__ . '/vendor/autoload.php<br>';
    } elseif (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
        require_once dirname(__DIR__) . '/vendor/autoload.php';
        echo "✅ Loaded from: " . dirname(__DIR__) . '/vendor/autoload.php<br>';
    } elseif (file_exists(dirname(dirname(__DIR__)) . '/vendor/autoload.php')) {
        require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
        echo "✅ Loaded from: " . dirname(dirname(__DIR__)) . '/vendor/autoload.php<br>';
    } else {
        echo "❌ Vendor autoload not found!<br>";
    }
} catch (Exception $e) {
    echo "❌ Error loading vendor: " . $e->getMessage() . "<br>";
}

// Test 6: Check if Dotenv class exists
echo "<h2>6. Check Dotenv Class</h2>";
if (class_exists('Dotenv\Dotenv')) {
    echo "✅ Dotenv class exists<br>";
} else {
    echo "❌ Dotenv class NOT found<br>";
}

// Test 7: Try to load .env
echo "<h2>7. Try Load .env</h2>";
try {
    if (class_exists('Dotenv\Dotenv')) {
        $base_path = dirname(__DIR__);
        echo "BASE_PATH: $base_path<br>";
        
        if (file_exists($base_path . '/.env')) {
            $dotenv = Dotenv\Dotenv::createImmutable($base_path);
            $dotenv->load();
            echo "✅ .env loaded successfully<br>";
            
            // Test 8: Check env variables
            echo "<h2>8. Environment Variables</h2>";
            echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NOT SET') . "<br>";
            echo "DB_NAME: " . ($_ENV['DB_NAME'] ?? 'NOT SET') . "<br>";
            echo "DB_USER: " . ($_ENV['DB_USER'] ?? 'NOT SET') . "<br>";
            echo "DB_PASS: " . (isset($_ENV['DB_PASS']) ? '***' : 'NOT SET') . "<br>";
        } else {
            echo "❌ .env file not found at: $base_path/.env<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Error loading .env: " . $e->getMessage() . "<br>";
}

echo "<h2>✅ Debug Complete</h2>";
?>
