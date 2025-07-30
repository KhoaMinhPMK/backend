<?php
// Test script để kiểm tra kết nối socket và gửi tin nhắn
require_once 'config.php';

echo "<h2>Test Socket Connection và Message Delivery</h2>";

// Test data
$testData = [
    'sender_phone' => '0123456789',
    'receiver_phone' => '0987654321',
    'message_text' => 'Test tin nhắn real-time - ' . date('Y-m-d H:i:s'),
    'message_type' => 'text'
];

echo "<h3>Test Data:</h3>";
echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT) . "</pre>";

// Test 1: Gửi tin nhắn qua API
echo "<h3>Test 1: Gửi tin nhắn qua API</h3>";
$url = 'https://viegrand.site/backend/send_message_complete.php';
$data = json_encode($testData);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>";

if ($error) {
    echo "<p style='color: red;'><strong>cURL Error:</strong> $error</p>";
} else {
    echo "<p><strong>API Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $jsonResponse = json_decode($response, true);
    if ($jsonResponse && isset($jsonResponse['success']) && $jsonResponse['success']) {
        echo "<p style='color: green;'><strong>✅ API call successful!</strong></p>";
        echo "<p>Message ID: " . ($jsonResponse['data']['message_id'] ?? 'N/A') . "</p>";
        echo "<p>Conversation ID: " . ($jsonResponse['data']['conversation_id'] ?? 'N/A') . "</p>";
        echo "<p>Socket Delivered: " . ($jsonResponse['data']['socket_delivered'] ? 'Yes' : 'No') . "</p>";
        
        if (!$jsonResponse['data']['socket_delivered']) {
            echo "<p style='color: orange;'><strong>⚠️ Socket delivery failed!</strong></p>";
        }
    } else {
        echo "<p style='color: red;'><strong>❌ API call failed!</strong></p>";
        echo "<p>Error: " . ($jsonResponse['message'] ?? 'Unknown error') . "</p>";
    }
}

// Test 2: Kiểm tra Node.js server trực tiếp
echo "<h3>Test 2: Kiểm tra Node.js server</h3>";
$socketServerUrl = 'https://chat.viegrand.site/send-message';
$secretKey = 'viegrand_super_secret_key_for_php_2025';

$socketData = [
    'sender_phone' => '0123456789',
    'receiver_phone' => '0987654321',
    'message_text' => 'Test tin nhắn trực tiếp - ' . date('Y-m-d H:i:s'),
    'conversation_id' => 'test_conv_' . time(),
    'message_id' => time(),
    'timestamp' => date('Y-m-d H:i:s'),
    'secret' => $secretKey,
];

$ch = curl_init($socketServerUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($socketData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen(json_encode($socketData))
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$socketResponse = curl_exec($ch);
$socketHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$socketError = curl_error($ch);
curl_close($ch);

echo "<p><strong>Socket Server HTTP Code:</strong> $socketHttpCode</p>";

if ($socketError) {
    echo "<p style='color: red;'><strong>Socket Server cURL Error:</strong> $socketError</p>";
} else {
    echo "<p><strong>Socket Server Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($socketResponse) . "</pre>";
    
    $socketJsonResponse = json_decode($socketResponse, true);
    if ($socketJsonResponse && isset($socketJsonResponse['success']) && $socketJsonResponse['success']) {
        echo "<p style='color: green;'><strong>✅ Socket server working!</strong></p>";
        echo "<p>Delivered: " . ($socketJsonResponse['delivered'] ? 'Yes' : 'No') . "</p>";
    } else {
        echo "<p style='color: red;'><strong>❌ Socket server error!</strong></p>";
        echo "<p>Error: " . ($socketJsonResponse['error'] ?? 'Unknown error') . "</p>";
    }
}

// Test 3: Kiểm tra database
echo "<h3>Test 3: Kiểm tra Database</h3>";
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check recent messages
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE sender_phone = ? OR receiver_phone = ? ORDER BY sent_at DESC LIMIT 3");
    $stmt->execute([$testData['sender_phone'], $testData['sender_phone']]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Recent Messages:</h4>";
    if ($messages) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Sender</th><th>Receiver</th><th>Message</th><th>Sent At</th></tr>";
        foreach ($messages as $msg) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($msg['id']) . "</td>";
            echo "<td>" . htmlspecialchars($msg['sender_phone']) . "</td>";
            echo "<td>" . htmlspecialchars($msg['receiver_phone']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($msg['message_text'], 0, 50)) . "...</td>";
            echo "<td>" . htmlspecialchars($msg['sent_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No messages found in database.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<h3>Kết luận:</h3>";
echo "<p>1. Nếu API call thành công nhưng socket delivery = false → Node.js server có vấn đề</p>";
echo "<p>2. Nếu socket server test thành công → PHP không gọi đúng endpoint</p>";
echo "<p>3. Nếu database có tin nhắn → API hoạt động, chỉ socket có vấn đề</p>";
?> 