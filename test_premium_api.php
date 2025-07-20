<?php
/**
 * Test Premium API
 * File này dùng để test các API premium
 */

// Cấu hình
$baseUrl = 'http://localhost/backendphp'; // Thay đổi URL theo môi trường
$testUserId = 1; // ID của user test

echo "🧪 Testing Premium API\n";
echo "=====================\n\n";

// Test 1: Lấy danh sách plans
echo "1. Testing GET /premium_api.php/plans\n";
$response = testAPI($baseUrl . '/premium_api.php/plans', 'GET');
echo "Response: " . $response . "\n\n";

// Test 2: Lấy payment methods
echo "2. Testing GET /premium_api.php/payment-methods\n";
$response = testAPI($baseUrl . '/premium_api.php/payment-methods', 'GET');
echo "Response: " . $response . "\n\n";

// Test 3: Mua gói premium
echo "3. Testing POST /premium_api.php/purchase\n";
$purchaseData = [
    'planId' => 2,
    'paymentMethod' => 'momo'
];
$response = testAPI($baseUrl . '/premium_api.php/purchase', 'POST', $purchaseData);
echo "Response: " . $response . "\n\n";

// Test 4: Lấy trạng thái premium
echo "4. Testing GET /premium_api.php/my-status\n";
$response = testAPI($baseUrl . '/premium_api.php/my-status', 'GET');
echo "Response: " . $response . "\n\n";

// Test 5: Lấy lịch sử giao dịch
echo "5. Testing GET /premium_api.php/transactions\n";
$response = testAPI($baseUrl . '/premium_api.php/transactions', 'GET');
echo "Response: " . $response . "\n\n";

// Test 6: Thiết lập premium cho user
echo "6. Testing POST /update_user_premium.php\n";
$setupData = [
    'userId' => $testUserId,
    'planId' => 2,
    'paymentMethod' => 'momo',
    'autoRenewal' => true
];
$response = testAPI($baseUrl . '/update_user_premium.php', 'POST', $setupData);
echo "Response: " . $response . "\n\n";

// Test 7: Kiểm tra trạng thái premium
echo "7. Testing GET /check_premium_status.php\n";
$response = testAPI($baseUrl . '/check_premium_status.php', 'GET');
echo "Response: " . $response . "\n\n";

// Test 8: Thiết lập dữ liệu premium
echo "8. Testing POST /setup_premium_data.php\n";
$setupAllData = [
    'planId' => 2,
    'paymentMethod' => 'momo'
];
$response = testAPI($baseUrl . '/setup_premium_data.php', 'POST', $setupAllData);
echo "Response: " . $response . "\n\n";

echo "✅ Testing completed!\n";

/**
 * Hàm test API
 */
function testAPI($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_error($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return "Error: " . $error;
    }
    
    curl_close($ch);
    
    // Format response
    $decoded = json_decode($response, true);
    if ($decoded) {
        return "HTTP $httpCode - " . json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    return "HTTP $httpCode - " . $response;
}

/**
 * Hàm test với mock token
 */
function testAPIWithToken($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_error($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return "Error: " . $error;
    }
    
    curl_close($ch);
    
    // Format response
    $decoded = json_decode($response, true);
    if ($decoded) {
        return "HTTP $httpCode - " . json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    return "HTTP $httpCode - " . $response;
}

/**
 * Hàm tạo mock JWT token
 */
function createMockToken($userId) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => $userId,
        'email' => 'test@example.com',
        'exp' => time() + 3600
    ]);
    
    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    // Mock signature
    $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, 'secret', true);
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64Header . "." . $base64Payload . "." . $base64Signature;
}

echo "\n🔑 Testing with Mock Token\n";
echo "=========================\n\n";

// Tạo mock token
$mockToken = createMockToken($testUserId);
echo "Mock Token: " . $mockToken . "\n\n";

// Test với token
echo "Testing GET /premium_api.php/my-status with token\n";
$response = testAPIWithToken($baseUrl . '/premium_api.php/my-status', 'GET', null, $mockToken);
echo "Response: " . $response . "\n\n";

echo "🎉 All tests completed!\n";
?> 