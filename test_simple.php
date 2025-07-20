<?php
/**
 * Test đơn giản cho Premium API
 */

// Cấu hình
$baseUrl = 'http://localhost/backendphp'; // Thay đổi URL theo môi trường

echo "🧪 Testing Premium API - Simple Test\n";
echo "====================================\n\n";

// Test 1: Kiểm tra kết nối database
echo "1. Testing Database Connection\n";
$response = testAPI($baseUrl . '/config.php');
echo "Response: " . substr($response, 0, 200) . "...\n\n";

// Test 2: Lấy danh sách plans
echo "2. Testing GET /premium_api.php/plans\n";
$response = testAPI($baseUrl . '/premium_api.php/plans');
echo "Response: " . $response . "\n\n";

// Test 3: Lấy payment methods
echo "3. Testing GET /premium_api.php/payment-methods\n";
$response = testAPI($baseUrl . '/premium_api.php/payment-methods');
echo "Response: " . $response . "\n\n";

// Test 4: Khởi tạo dữ liệu premium
echo "4. Testing POST /init_premium_data.php\n";
$response = testAPI($baseUrl . '/init_premium_data.php', 'POST');
echo "Response: " . $response . "\n\n";

// Test 5: Lấy danh sách plans sau khi khởi tạo
echo "5. Testing GET /premium_api.php/plans (after init)\n";
$response = testAPI($baseUrl . '/premium_api.php/plans');
echo "Response: " . $response . "\n\n";

echo "✅ Simple test completed!\n";

/**
 * Hàm test API đơn giản
 */
function testAPI($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
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
    
    return "HTTP $httpCode - " . substr($response, 0, 500);
}

/**
 * Hàm kiểm tra database trực tiếp
 */
function checkDatabase() {
    try {
        require_once 'config.php';
        $pdo = getDatabaseConnection();
        
        // Kiểm tra bảng premium_plans
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM premium_plans");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Database check: " . $result['count'] . " premium plans found\n";
        
        // Lấy danh sách plans
        $stmt = $pdo->prepare("SELECT id, name, price FROM premium_plans ORDER BY sortOrder");
        $stmt->execute();
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Plans in database:\n";
        foreach ($plans as $plan) {
            echo "  - ID: {$plan['id']}, Name: {$plan['name']}, Price: {$plan['price']}\n";
        }
        
    } catch (Exception $e) {
        echo "Database error: " . $e->getMessage() . "\n";
    }
}

echo "\n🔍 Direct Database Check\n";
echo "======================\n";
checkDatabase();
?> 