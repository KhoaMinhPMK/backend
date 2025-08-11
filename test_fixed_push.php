<?php
require_once 'config.php';

echo "🧪 Testing Fixed Push Notification\n";
echo "==================================\n\n";

// Test configuration
$senderEmail = 'ss@gmail.com';
$receiverEmail = 'z@gmail.com';

echo "📧 Test Configuration:\n";
echo "   Sender: $senderEmail\n";
echo "   Receiver: $receiverEmail\n\n";

try {
    $conn = getDatabaseConnection();
    
    // Step 1: Verify users exist with correct column names
    echo "1️⃣ Verifying users with correct column names...\n";
    
    $senderSql = "SELECT userId, email, phone, userName FROM user WHERE email = ?";
    $senderStmt = $conn->prepare($senderSql);
    $senderStmt->execute([$senderEmail]);
    $sender = $senderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sender) {
        echo "❌ Sender not found: $senderEmail\n";
        exit;
    }
    
    $receiverSql = "SELECT userId, email, phone, userName, device_token FROM user WHERE email = ?";
    $receiverStmt = $conn->prepare($receiverSql);
    $receiverStmt->execute([$receiverEmail]);
    $receiver = $receiverStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$receiver) {
        echo "❌ Receiver not found: $receiverEmail\n";
        exit;
    }
    
    echo "   ✅ Sender: {$sender['userName']} (ID: {$sender['userId']}, Phone: {$sender['phone']})\n";
    echo "   ✅ Receiver: {$receiver['userName']} (ID: {$receiver['userId']}, Phone: {$receiver['phone']})\n";
    echo "   📱 Receiver Device Token: " . ($receiver['device_token'] ? substr($receiver['device_token'], 0, 50) . '...' : 'NULL') . "\n\n";
    
    if (!$receiver['device_token']) {
        echo "❌ Receiver has no device token!\n";
        echo "🔧 Receiver needs to log into the app to get a device token\n";
        exit;
    }
    
    // Step 2: Test the send_message_complete.php API
    echo "2️⃣ Testing send_message_complete.php API...\n";
    
    $testMessage = "Fixed test message - " . date('Y-m-d H:i:s');
    
    $messageData = [
        'sender_phone' => $sender['phone'],
        'receiver_phone' => $receiver['phone'],
        'message_text' => $testMessage,
        'message_type' => 'text'
    ];
    
    echo "   📤 Message: $testMessage\n";
    echo "   📤 From: {$sender['phone']} to {$receiver['phone']}\n";
    
    $url = 'https://viegrand.site/backend/send_message_complete.php';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($messageData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        echo "   ❌ cURL Error: $curlError\n";
        exit;
    }
    
    echo "   📥 Response HTTP Code: $httpCode\n";
    echo "   📥 Response: $response\n\n";
    
    $responseData = json_decode($response, true);
    
    if ($httpCode === 200 && $responseData && isset($responseData['success'])) {
        if ($responseData['success']) {
            echo "✅ Message sent successfully!\n";
            echo "   Message ID: " . ($responseData['data']['message_id'] ?? 'N/A') . "\n";
            echo "   Conversation ID: " . ($responseData['data']['conversation_id'] ?? 'N/A') . "\n";
            echo "   Socket Delivered: " . ($responseData['data']['socket_delivered'] ? 'Yes' : 'No') . "\n";
            
            echo "\n🎯 Expected Result:\n";
            echo "   📱 Receiver should receive a push notification with:\n";
            echo "   📱 Title: 'Tin nhắn mới từ {$sender['userName']}'\n";
            echo "   📱 Body: '$testMessage'\n";
            echo "   📱 Timestamp: " . date('Y-m-d H:i:s') . "\n";
            
        } else {
            echo "❌ Message sending failed!\n";
            echo "   Error: " . ($responseData['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "❌ Invalid response format!\n";
        echo "   HTTP Code: $httpCode\n";
        echo "   Response: $response\n";
    }
    
    echo "\n🚀 Test Summary:\n";
    echo "✅ User verification with correct column names completed\n";
    echo ($httpCode === 200 && $responseData && $responseData['success'] ? "✅ Message sent and push notification should be triggered" : "❌ Message sending failed") . "\n";
    echo "\n📱 Next steps:\n";
    echo "1. Check the receiver's device for push notification\n";
    echo "2. If no notification, check the server logs for errors\n";
    echo "3. Verify Firebase service account is properly configured\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "🔧 Stack trace: " . $e->getTraceAsString() . "\n";
}
?> 