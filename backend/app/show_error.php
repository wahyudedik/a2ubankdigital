<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Backend Error Debug</h1><hr>";

echo "<h2>1. PHP Version</h2>";
echo "PHP: " . phpversion() . "<br><hr>";

echo "<h2>2. Paths</h2>";
echo "__DIR__: " . __DIR__ . "<br>";
echo "BASE_PATH: " . dirname(__DIR__) . "<br><hr>";

echo "<h2>3. Files Check</h2>";
$files = [
    __DIR__ . '/vendor/autoload.php',
    dirname(__DIR__) . '/.env',
];
foreach ($files as $f) {
    echo (file_exists($f) ? '✅' : '❌') . " $f<br>";
}
echo "<hr>";

echo "<h2>4. Load Config</h2>";
try {
    require_once __DIR__ . '/config.php';
    echo "✅ Config loaded!<br>";
    if (isset($pdo)) {
        echo "✅ Database connected!<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>
