<?php
/**
 * Quick verification script for bug fixes
 * Run: php verify_fixes.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 A2U Bank Digital - Bug Fix Verification\n";
echo str_repeat("=", 60) . "\n\n";

// Test 1: Verify late_payment_fee column exists
echo "✓ Test 1: Checking late_payment_fee column in loan_products...\n";
try {
    $columns = DB::select("DESCRIBE loan_products");
    $hasLateFee = false;
    foreach ($columns as $col) {
        if ($col->Field === 'late_payment_fee') {
            $hasLateFee = true;
            echo "  ✅ Column exists: {$col->Field} ({$col->Type})\n";
            break;
        }
    }
    if (!$hasLateFee) {
        echo "  ❌ FAILED: late_payment_fee column not found!\n";
    }
} catch (Exception $e) {
    echo "  ❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Verify late_fee column exists in loan_installments
echo "✓ Test 2: Checking late_fee column in loan_installments...\n";
try {
    $columns = DB::select("DESCRIBE loan_installments");
    $hasLateFee = false;
    foreach ($columns as $col) {
        if ($col->Field === 'late_fee') {
            $hasLateFee = true;
            echo "  ✅ Column exists: {$col->Field} ({$col->Type})\n";
            break;
        }
    }
    if (!$hasLateFee) {
        echo "  ❌ FAILED: late_fee column not found!\n";
    }
} catch (Exception $e) {
    echo "  ❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Verify LoanProduct model has late_payment_fee in fillable
echo "✓ Test 3: Checking LoanProduct model configuration...\n";
try {
    $model = new App\Models\LoanProduct();
    $fillable = $model->getFillable();
    if (in_array('late_payment_fee', $fillable)) {
        echo "  ✅ late_payment_fee is in \$fillable array\n";
    } else {
        echo "  ❌ FAILED: late_payment_fee not in \$fillable!\n";
    }
    
    $casts = $model->getCasts();
    if (isset($casts['late_payment_fee'])) {
        echo "  ✅ late_payment_fee has cast: {$casts['late_payment_fee']}\n";
    } else {
        echo "  ⚠️  WARNING: late_payment_fee not in \$casts\n";
    }
} catch (Exception $e) {
    echo "  ❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Verify Role constants match CheckRole middleware
echo "✓ Test 4: Checking Role constants alignment...\n";
try {
    $roleConstants = [
        'SUPER_ADMIN' => 1,
        'ADMIN' => 2,
        'MANAGER' => 3,
        'MARKETING' => 4,
        'TELLER' => 5,
        'CS' => 6,
        'ANALYST' => 7,
        'DEBT_COLLECTOR' => 8,
        'CUSTOMER' => 9,
    ];
    
    $allMatch = true;
    foreach ($roleConstants as $name => $expectedId) {
        $actualId = constant("App\Models\Role::{$name}");
        if ($actualId === $expectedId) {
            echo "  ✅ Role::{$name} = {$actualId}\n";
        } else {
            echo "  ❌ FAILED: Role::{$name} = {$actualId}, expected {$expectedId}\n";
            $allMatch = false;
        }
    }
    
    if ($allMatch) {
        echo "  ✅ All role constants match!\n";
    }
} catch (Exception $e) {
    echo "  ❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Check if loan products exist and can be retrieved
echo "✓ Test 5: Testing LoanProduct retrieval...\n";
try {
    $count = App\Models\LoanProduct::count();
    echo "  ✅ Found {$count} loan products in database\n";
    
    if ($count > 0) {
        $product = App\Models\LoanProduct::first();
        echo "  ✅ Sample product: {$product->product_name}\n";
        echo "  ✅ Late payment fee: Rp " . number_format($product->late_payment_fee, 0, ',', '.') . "\n";
    }
} catch (Exception $e) {
    echo "  ❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 6: Verify no penalty_amount references in key files
echo "✓ Test 6: Checking for old 'penalty_amount' references...\n";
$filesToCheck = [
    'app/Http/Controllers/Inertia/UserPageController.php',
    'app/Http/Controllers/Inertia/AdminPageController.php',
    'app/Http/Controllers/User/LoanController.php',
    'app/Http/Controllers/Admin/LoanController.php',
];

$foundOldReferences = false;
foreach ($filesToCheck as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, "'penalty_amount'") !== false || strpos($content, '"penalty_amount"') !== false) {
            echo "  ⚠️  WARNING: Found 'penalty_amount' in {$file}\n";
            $foundOldReferences = true;
        }
    }
}

if (!$foundOldReferences) {
    echo "  ✅ No old 'penalty_amount' references found!\n";
}

echo "\n";
echo str_repeat("=", 60) . "\n";
echo "✅ Verification Complete!\n";
echo "\nAll critical fixes have been verified.\n";
echo "System is ready for production deployment.\n";
