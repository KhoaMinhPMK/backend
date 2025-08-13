<?php
echo "<h1>Database Connection Test</h1>";

// Test 1: Check if config.php exists
echo "<h2>1. Checking config.php</h2>";
if (file_exists('config.php')) {
    echo "✅ config.php exists<br>";
} else {
    echo "❌ config.php not found<br>";
    exit();
}

// Test 2: Include config.php
echo "<h2>2. Including config.php</h2>";
try {
    require_once 'config.php';
    echo "✅ config.php included successfully<br>";
} catch (Exception $e) {
    echo "❌ Error including config.php: " . $e->getMessage() . "<br>";
    exit();
}

// Test 3: Check if getDatabaseConnection function exists
echo "<h2>3. Checking getDatabaseConnection function</h2>";
if (function_exists('getDatabaseConnection')) {
    echo "✅ getDatabaseConnection function exists<br>";
} else {
    echo "❌ getDatabaseConnection function not found<br>";
    exit();
}

// Test 4: Test database connection
echo "<h2>4. Testing database connection</h2>";
try {
    $pdo = getDatabaseConnection();
    echo "✅ Database connection successful<br>";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    if ($result && $result['test'] == 1) {
        echo "✅ Database query test successful<br>";
    } else {
        echo "❌ Database query test failed<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    echo "<h3>Debug Information:</h3>";
    echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'Not defined') . "<br>";
    echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'Not defined') . "<br>";
    echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'Not defined') . "<br>";
    echo "DB_PASS: " . (defined('DB_PASS') ? (DB_PASS ? 'Set' : 'Empty') : 'Not defined') . "<br>";
}

// Test 5: Check if user table exists
echo "<h2>5. Checking user table</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'user'");
    if ($stmt->rowCount() > 0) {
        echo "✅ User table exists<br>";
        
        // Count users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM user");
        $result = $stmt->fetch();
        echo "📊 Total users: " . $result['count'] . "<br>";
        
        // Show sample users with emails
        $stmt = $pdo->query("SELECT userId, name, email FROM user WHERE email IS NOT NULL AND email != '' LIMIT 3");
        $users = $stmt->fetchAll();
        if (count($users) > 0) {
            echo "📧 Sample users with emails:<br>";
            foreach ($users as $user) {
                echo "- {$user['name']} ({$user['email']})<br>";
            }
        } else {
            echo "⚠️ No users found with email addresses<br>";
        }
    } else {
        echo "❌ User table does not exist<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking user table: " . $e->getMessage() . "<br>";
}

echo "<h2>Next Steps</h2>";
echo "<p>If all tests pass, you can now:</p>";
echo "<ol>";
echo "<li>Run the OTP setup test: <a href='test_otp_setup.php'>test_otp_setup.php</a></li>";
echo "<li>Test the password change functionality in the app</li>";
echo "</ol>";
?> 