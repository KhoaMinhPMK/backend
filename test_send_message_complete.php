<?php
// File test để kiểm tra API send_message_complete.php
require_once 'config.php';

echo "<h2>Test API send_message_complete.php</h2>";

// Test data
$testData = [
    'sender_phone' => '0123456789',
    'receiver_phone' => '0987654321',
    'message_text' => 'Test tin nhắn từ PHP API - ' . date('Y-m-d H:i:s'),
    'message_type' => 'text'
];

echo "<h3>Test Data:</h3>";
echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT) . "</pre>";

// Make request to API
$url = 'http://localhost/backend/send_message_complete.php';
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

echo "<h3>Making request to: $url</h3>";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h3>Response:</h3>";
echo "<p><strong>HTTP Code:</strong> $httpCode</p>";

if ($error) {
    echo "<p><strong>cURL Error:</strong> $error</p>";
} else {
    echo "<p><strong>Response Body:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    // Try to decode JSON response
    $jsonResponse = json_decode($response, true);
    if ($jsonResponse) {
        echo "<h3>Parsed Response:</h3>";
        echo "<pre>" . json_encode($jsonResponse, JSON_PRETTY_PRINT) . "</pre>";
        
        if (isset($jsonResponse['success']) && $jsonResponse['success']) {
            echo "<p style='color: green;'><strong>✅ Test PASSED!</strong></p>";
            echo "<p>Message ID: " . ($jsonResponse['data']['message_id'] ?? 'N/A') . "</p>";
            echo "<p>Conversation ID: " . ($jsonResponse['data']['conversation_id'] ?? 'N/A') . "</p>";
            echo "<p>Socket Delivered: " . ($jsonResponse['data']['socket_delivered'] ? 'Yes' : 'No') . "</p>";
        } else {
            echo "<p style='color: red;'><strong>❌ Test FAILED!</strong></p>";
            echo "<p>Error: " . ($jsonResponse['message'] ?? 'Unknown error') . "</p>";
        }
    } else {
        echo "<p style='color: red;'><strong>❌ Invalid JSON response!</strong></p>";
    }
}

echo "<hr>";
echo "<h3>Database Check:</h3>";

// Check if message was saved to database
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check conversations table
    $stmt = $pdo->prepare("SELECT * FROM conversations WHERE participant1_phone = ? OR participant2_phone = ? ORDER BY last_activity DESC LIMIT 5");
    $stmt->execute([$testData['sender_phone'], $testData['sender_phone']]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Recent Conversations:</h4>";
    if ($conversations) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Participant 1</th><th>Participant 2</th><th>Last Activity</th></tr>";
        foreach ($conversations as $conv) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($conv['id']) . "</td>";
            echo "<td>" . htmlspecialchars($conv['participant1_phone']) . "</td>";
            echo "<td>" . htmlspecialchars($conv['participant2_phone']) . "</td>";
            echo "<td>" . htmlspecialchars($conv['last_activity']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No conversations found.</p>";
    }
    
    // Check messages table
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE sender_phone = ? OR receiver_phone = ? ORDER BY sent_at DESC LIMIT 5");
    $stmt->execute([$testData['sender_phone'], $testData['sender_phone']]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Recent Messages:</h4>";
    if ($messages) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Conversation ID</th><th>Sender</th><th>Receiver</th><th>Message</th><th>Sent At</th></tr>";
        foreach ($messages as $msg) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($msg['id']) . "</td>";
            echo "<td>" . htmlspecialchars($msg['conversation_id']) . "</td>";
            echo "<td>" . htmlspecialchars($msg['sender_phone']) . "</td>";
            echo "<td>" . htmlspecialchars($msg['receiver_phone']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($msg['message_text'], 0, 50)) . "...</td>";
            echo "<td>" . htmlspecialchars($msg['sent_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No messages found.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 