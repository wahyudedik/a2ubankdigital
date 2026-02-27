<?php
// Test Database Connection
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');

echo "=== Testing Database Connection ===\n\n";

// Test 1: Check if .env file exists
echo "1. Checking .env file...\n";
$env_path = dirname(__DIR__) . '/.env';
if (file_exists($env_path)) {
    echo "   ✓ .env file found at: $env_path\n\n";
} else {
    echo "   ✗ .env file NOT found!\n\n";
    exit;
}

// Test 2: Load config
echo "2. Loading config.php...\n";
try {
    require_once 'config.php';
    echo "   ✓ Config loaded successfully\n\n";
} catch (Exception $e) {
    echo "   ✗ Error loading config: " . $e->getMessage() . "\n\n";
    exit;
}

// Test 3: Check environment variables
echo "3. Checking environment variables...\n";
echo "   DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NOT SET') . "\n";
echo "   DB_NAME: " . ($_ENV['DB_NAME'] ?? 'NOT SET') . "\n";
echo "   DB_USER: " . ($_ENV['DB_USER'] ?? 'NOT SET') . "\n";
echo "   DB_PASS: " . (isset($_ENV['DB_PASS']) ? '***' : 'NOT SET') . "\n\n";

// Test 4: Check PDO connection
echo "4. Testing database connection...\n";
if (isset($pdo)) {
    echo "   ✓ PDO object exists\n";
    
    try {
        // Test query
        $stmt = $pdo->query("SELECT VERSION() as version");
        $result = $stmt->fetch();
        echo "   ✓ Database connected successfully!\n";
        echo "   MySQL Version: " . $result['version'] . "\n\n";
        
        // Test tables
        echo "5. Checking database tables...\n";
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "   Found " . count($tables) . " tables\n";
        echo "   Tables: " . implode(', ', array_slice($tables, 0, 10)) . "...\n\n";
        
        // Test users table
        echo "6. Checking users table...\n";
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        echo "   Total users: " . $result['count'] . "\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'ACTIVE'");
        $result = $stmt->fetch();
        echo "   Active users: " . $result['count'] . "\n\n";
        
        // Sample active users
        echo "7. Sample active users:\n";
        $stmt = $pdo->query("SELECT id, email, full_name, role_id FROM users WHERE status = 'ACTIVE' LIMIT 5");
        $users = $stmt->fetchAll();
        foreach ($users as $user) {
            echo "   - ID: {$user['id']}, Email: {$user['email']}, Name: {$user['full_name']}, Role: {$user['role_id']}\n";
        }
        
        echo "\n=== ✓ ALL TESTS PASSED ===\n";
        echo "\nDatabase is connected and working properly!\n";
        echo "You can now use the application.\n";
        
    } catch (PDOException $e) {
        echo "   ✗ Database query failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ✗ PDO object not found!\n";
}
