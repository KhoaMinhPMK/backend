<?php
// test_login_response.php - Test login API để kiểm tra response structure

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Test Login API Response Structure</h1>";

// Test data
$testCredentials = [
    'email' => 'pmkkhoaminh@gmail.com',
    'password' => '123123'
];

echo "<h2>Test Credentials:</h2>";
echo "<p>Email: " . htmlspecialchars($testCredentials['email']) . "</p>";
echo "<p>Password: " . htmlspecialchars($testCredentials['password']) . "</p>";

// Simulate API call
$url = 'http://localhost/viegrandApp/backendphp/login.php';

$postData = json_encode($testCredentials);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($postData)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

echo "<h2>Making Login Request...</h2>";

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
    exit;
}

echo "<h2>Parsed JSON Structure:</h2>";
echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
echo htmlspecialchars(json_encode($jsonResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "</pre>";

// Analyze structure for React Native
echo "<h2>Analysis for React Native:</h2>";

if (isset($jsonResponse['success']) && $jsonResponse['success']) {
    echo "<p style='color: green'>✅ Success: " . htmlspecialchars($jsonResponse['message'] ?? 'No message') . "</p>";
    
    if (isset($jsonResponse['data'])) {
        echo "<p>✅ Data field exists</p>";
        
        $data = $jsonResponse['data'];
        
        if (isset($data['access_token'])) {
            echo "<p>✅ access_token: " . htmlspecialchars(substr($data['access_token'], 0, 20)) . "...</p>";
        } else {
            echo "<p style='color: red'>❌ access_token missing</p>";
        }
        
        if (isset($data['user'])) {
            echo "<p>✅ user object exists</p>";
            
            $user = $data['user'];
            $userFields = [
                'id', 'fullName', 'email', 'role', 'phone', 
                'age', 'address', 'gender', 'active', 'premium',
                'createdAt', 'updatedAt'
            ];
            
            echo "<ul>";
            foreach ($userFields as $field) {
                if (isset($user[$field])) {
                    $value = $user[$field];
                    if (is_array($value)) {
                        echo "<li>✅ $field: [object]</li>";
                    } else {
                        echo "<li>✅ $field: " . htmlspecialchars(is_string($value) ? $value : json_encode($value)) . "</li>";
                    }
                } else {
                    echo "<li>⚠️ $field: missing</li>";
                }
            }
            echo "</ul>";
            
        } else {
            echo "<p style='color: red'>❌ user object missing</p>";
        }
        
    } else {
        echo "<p style='color: red'>❌ Data field missing</p>";
    }
    
} else {
    echo "<p style='color: red'>❌ API call failed</p>";
    if (isset($jsonResponse['error'])) {
        echo "<p>Error: " . htmlspecialchars(json_encode($jsonResponse['error'], JSON_PRETTY_PRINT)) . "</p>";
    }
}

echo "<h2>Expected React Native Usage:</h2>";
echo "<code style='background: #f5f5f5; padding: 10px; display: block;'>";
echo "// AuthAPI should return response.data.data<br>";
echo "const response = await apiClient.post('/login.php', data);<br>";
echo "return response.data.data; // This should contain {access_token, user}<br>";
echo "</code>";
?>
