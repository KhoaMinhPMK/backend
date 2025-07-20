<?php
require_once 'config.php';

echo "=== VIEGRAND BACKEND DEBUG SCRIPT ===\n\n";

// Test 1: Database Connection
echo "1. Testing Database Connection...\n";
try {
    $pdo = getDatabaseConnection();
    echo "✅ Database connection successful\n";
    
    // Test database exists
    $stmt = $pdo->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📊 Current database: " . $result['db_name'] . "\n";
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Test 2: Check if tables exist
echo "2. Checking Database Tables...\n";
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredTables = ['users', 'premium_plans', 'user_subscriptions', 'payment_transactions'];
    
    foreach ($requiredTables as $table) {
        if (in_array($table, $tables)) {
            echo "✅ Table '$table' exists\n";
        } else {
            echo "❌ Table '$table' missing\n";
        }
    }
    
    echo "\n📊 All tables in database:\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error checking tables: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Check users table structure
echo "3. Checking Users Table Structure...\n";
try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredColumns = ['id', 'fullName', 'email', 'password', 'phone', 'isPremium', 'premiumEndDate'];
    
    echo "📊 Users table columns:\n";
    foreach ($columns as $column) {
        $hasColumn = in_array($column['Field'], $requiredColumns) ? "✅" : "ℹ️";
        echo "  $hasColumn {$column['Field']} ({$column['Type']})\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error checking users table: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Test signup API with sample data
echo "4. Testing Signup API...\n";
$testData = [
    'fullName' => 'Test User Debug',
    'email' => 'test_debug_' . time() . '@example.com',
    'phone' => '0123456789',
    'password' => '123456'
];

echo "📊 Test data:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n";

try {
    // Simulate API call
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/signup';
    
    // Validate required fields
    $required_fields = ['fullName', 'email', 'phone', 'password'];
    validateRequiredFields($testData, $required_fields);
    echo "✅ Required fields validation passed\n";
    
    // Validate email
    validateEmail($testData['email']);
    echo "✅ Email validation passed\n";
    
    // Validate password
    validatePassword($testData['password']);
    echo "✅ Password validation passed\n";
    
    // Validate phone
    validatePhone($testData['phone']);
    echo "✅ Phone validation passed\n";
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$testData['email']]);
    if ($stmt->fetch()) {
        echo "⚠️ Email already exists\n";
    } else {
        echo "✅ Email is available\n";
    }
    
    // Test insert
    $hashedPassword = password_hash($testData['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO users (fullName, email, password, phone, role, active, isPremium, premiumTrialUsed, created_at, updated_at) 
        VALUES (?, ?, ?, ?, 'elderly', TRUE, FALSE, FALSE, NOW(), NOW())
    ");
    
    $stmt->execute([$testData['fullName'], $testData['email'], $hashedPassword, $testData['phone']]);
    $userId = $pdo->lastInsertId();
    
    echo "✅ User created successfully with ID: $userId\n";
    
    // Get user data
    $stmt = $pdo->prepare("
        SELECT id, fullName, email, phone, role, isPremium, premiumEndDate, created_at 
        FROM users WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "📊 Created user data:\n";
    echo json_encode($user, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "❌ Signup test failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Check premium plans
echo "5. Checking Premium Plans...\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM premium_plans WHERE isActive = TRUE");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📊 Active premium plans: " . $result['count'] . "\n";
    
    if ($result['count'] > 0) {
        $stmt = $pdo->query("SELECT id, name, price, duration, type FROM premium_plans WHERE isActive = TRUE ORDER BY sortOrder");
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($plans as $plan) {
            echo "  ✅ Plan: {$plan['name']} - {$plan['price']} VND - {$plan['duration']} days ({$plan['type']})\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error checking premium plans: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 6: Test stored procedures
echo "6. Testing Stored Procedures...\n";
try {
    $stmt = $pdo->query("SHOW PROCEDURE STATUS WHERE Db = 'viegrand_app'");
    $procedures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredProcedures = ['CreateSubscription', 'UpdateUserPremiumStatus', 'CheckExpiredSubscriptions', 'ActivatePremiumTrial'];
    
    echo "📊 Available stored procedures:\n";
    foreach ($procedures as $proc) {
        $isRequired = in_array($proc['Name'], $requiredProcedures) ? "✅" : "ℹ️";
        echo "  $isRequired {$proc['Name']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error checking stored procedures: " . $e->getMessage() . "\n";
}

echo "\n=== DEBUG COMPLETED ===\n";
?>
