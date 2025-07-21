<?php
// debug_auth.php - Debug authentication only

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Debug Authentication</h1>";

require_once 'config.php';

// Show all headers received
echo "<h2>All Headers Received:</h2>";
echo "<pre>";

// Method 1: getallheaders()
if (function_exists('getallheaders')) {
    echo "getallheaders():\n";
    $headers = getallheaders();
    print_r($headers);
} else {
    echo "getallheaders() not available\n";
}

// Method 2: $_SERVER
echo "\n\$_SERVER headers:\n";
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        echo "$key: $value\n";
    }
}
echo "</pre>";

// Test with manual token
echo "<h2>Manual Token Test:</h2>";
$testToken = '1_test_token_123456';

try {
    $pdo = getDatabaseConnection();
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, fullName, email, active FROM users WHERE id = 1");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p style='color: green'>✅ User ID 1 found: " . htmlspecialchars($user['fullName']) . "</p>";
        echo "<p>Active: " . ($user['active'] ? 'Yes' : 'No') . "</p>";
        
        if (!$user['active']) {
            echo "<p style='color: red'>❌ User is not active!</p>";
        }
    } else {
        echo "<p style='color: red'>❌ User ID 1 not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test token parsing
echo "<h2>Token Parsing Test:</h2>";
echo "<p>Test Token: <code>$testToken</code></p>";

$tokenParts = explode('_', $testToken, 2);
echo "<p>Token Parts: " . count($tokenParts) . "</p>";
echo "<p>User ID: " . (isset($tokenParts[0]) ? $tokenParts[0] : 'N/A') . "</p>";
echo "<p>Hash: " . (isset($tokenParts[1]) ? substr($tokenParts[1], 0, 10) . '...' : 'N/A') . "</p>";

// Test Authorization header formats
echo "<h2>Authorization Header Format Tests:</h2>";
$testHeaders = [
    'Bearer ' . $testToken,
    'bearer ' . $testToken,
    'BEARER ' . $testToken,
    $testToken
];

foreach ($testHeaders as $header) {
    echo "<p>Testing: <code>" . htmlspecialchars($header) . "</code></p>";
    if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
        echo "<p style='color: green'>✅ Matches! Token: " . substr($matches[1], 0, 20) . "...</p>";
    } else {
        echo "<p style='color: red'>❌ No match</p>";
    }
}
?>
