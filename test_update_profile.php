<?php
// test_update_profile.php - Test update profile API

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Test Update Profile API</h1>";

// Simulate the exact same data from React Native
$testData = [
    'fullName' => 'Bbnn',
    'phone' => '2580741369',
    'age' => 64,
    'address' => 'J jb jnj',
    'gender' => 'Nam'
];

// Get test token (assume user ID 1 exists)
$testToken = '1_test_token_123456';

echo "<h2>Test Data:</h2>";
echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT) . "</pre>";

echo "<h2>Test Token:</h2>";
echo "<code>$testToken</code>";

// Test the API call
$url = 'http://localhost/viegrandApp/backendphp/update_profile.php';
$postData = json_encode($testData);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $testToken,
    'Content-Length: ' . strlen($postData)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

echo "<h2>Making Update Profile Request...</h2>";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo "<p style='color: red'>❌ CURL Error: " . htmlspecialchars($curlError) . "</p>";
    exit;
}

echo "<p><strong>HTTP Status Code:</strong> $httpCode</p>";

if ($response === false) {
    echo "<p style='color: red'>❌ No response received</p>";
    exit;
}

echo "<h2>Raw Response:</h2>";
echo "<textarea style='width: 100%; height: 200px;'>" . htmlspecialchars($response) . "</textarea>";

// Try to decode JSON
$jsonResponse = json_decode($response, true);

if ($jsonResponse === null) {
    echo "<p style='color: red'>❌ Invalid JSON response</p>";
    echo "<p>JSON Error: " . json_last_error_msg() . "</p>";
} else {
    echo "<h2>Parsed JSON Response:</h2>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    echo htmlspecialchars(json_encode($jsonResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "</pre>";
    
    // Analysis
    if ($httpCode === 400) {
        echo "<h2 style='color: red'>❌ Error Analysis:</h2>";
        if (isset($jsonResponse['error']['message'])) {
            echo "<p><strong>Error Message:</strong> " . htmlspecialchars($jsonResponse['error']['message']) . "</p>";
        }
        if (isset($jsonResponse['error'])) {
            echo "<p><strong>Full Error:</strong></p>";
            echo "<pre>" . htmlspecialchars(json_encode($jsonResponse['error'], JSON_PRETTY_PRINT)) . "</pre>";
        }
    } else if ($httpCode === 200) {
        echo "<h2 style='color: green'>✅ Success!</h2>";
    }
}

// Also test if user exists in database
echo "<h2>Database Check:</h2>";
require_once 'config.php';

try {
    $pdo = getDatabaseConnection();
    
    // Check if user ID 1 exists
    $stmt = $pdo->prepare("SELECT id, fullName, email, active FROM users WHERE id = 1");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p style='color: green'>✅ Test User ID 1 exists:</p>";
        echo "<ul>";
        echo "<li>ID: " . $user['id'] . "</li>";
        echo "<li>Name: " . htmlspecialchars($user['fullName']) . "</li>";
        echo "<li>Email: " . htmlspecialchars($user['email']) . "</li>";
        echo "<li>Active: " . ($user['active'] ? 'Yes' : 'No') . "</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red'>❌ Test User ID 1 does not exist</p>";
        
        // Show available users
        $stmt = $pdo->prepare("SELECT id, fullName, email FROM users LIMIT 5");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($users) {
            echo "<p><strong>Available users:</strong></p>";
            echo "<ul>";
            foreach ($users as $u) {
                echo "<li>ID: {$u['id']}, Name: " . htmlspecialchars($u['fullName']) . ", Email: " . htmlspecialchars($u['email']) . "</li>";
            }
            echo "</ul>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>Troubleshooting Steps:</h2>";
echo "<ol>";
echo "<li>Check if XAMPP/WAMP is running</li>";
echo "<li>Check if database 'viegrand_app' exists</li>";
echo "<li>Check if user with ID 1 exists and is active</li>";
echo "<li>Check if update_profile.php file exists</li>";
echo "<li>Check PHP error logs</li>";
echo "</ol>";
?>
