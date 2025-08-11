<?php
require_once 'config.php';
require_once 'send_push_notification.php';

echo "🔍 Debug Message Push Notification\n";
echo "==================================\n\n";

// Test configuration
$senderEmail = 'ss@gmail.com';
$receiverEmail = 'z@gmail.com';

echo "📧 Test Configuration:\n";
echo "   Sender: $senderEmail\n";
echo "   Receiver: $receiverEmail\n\n";

try {
    $conn = getDatabaseConnection();
    
    // Step 1: Check if users exist and get their info
    echo "1️⃣ Checking users...\n";
    
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
    
    echo "   ✅ Sender: {$sender['userName']} ({$sender['phone']})\n";
    echo "   ✅ Receiver: {$receiver['userName']} ({$receiver['phone']})\n";
    echo "   📱 Receiver Device Token: " . ($receiver['device_token'] ? substr($receiver['device_token'], 0, 50) . '...' : 'NULL') . "\n\n";
    
    if (!$receiver['device_token']) {
        echo "❌ Receiver has no device token!\n";
        exit;
    }
    
    // Step 2: Test direct push notification (like send_message_complete.php does)
    echo "2️⃣ Testing direct push notification...\n";
    
    $testMessage = "Debug test message - " . date('Y-m-d H:i:s');
    $notificationTitle = "Tin nhắn mới từ {$sender['userName']}";
    
    echo "   📤 Title: $notificationTitle\n";
    echo "   📤 Body: $testMessage\n";
    echo "   📤 To: {$receiver['email']}\n";
    
    $push_result = sendPushNotification(
        $receiver['email'],
        $notificationTitle,
        $testMessage,
        [
            'type' => 'message',
            'conversation_id' => 'test_123',
            'sender_phone' => $sender['phone'],
            'receiver_phone' => $receiver['phone'],
            'message_id' => 'test_456',
            'message_text' => $testMessage,
            'message_type' => 'text',
            'sender_name' => $sender['userName'],
            'receiver_name' => $receiver['userName'],
            'timestamp' => date('Y-m-d H:i:s')
        ]
    );
    
    echo "   📥 Push Result: " . ($push_result['success'] ? '✅ Success' : '❌ Failed') . "\n";
    if (isset($push_result['message'])) {
        echo "   📥 Message: {$push_result['message']}\n";
    }
    if (isset($push_result['fcm_response'])) {
        echo "   📥 FCM Response: " . json_encode($push_result['fcm_response']) . "\n";
    }
    
    // Step 3: Test the actual send_message_complete.php API
    echo "\n3️⃣ Testing send_message_complete.php API...\n";
    
    $messageData = [
        'sender_phone' => $sender['phone'],
        'receiver_phone' => $receiver['phone'],
        'message_text' => "API test message - " . date('Y-m-d H:i:s'),
        'message_type' => 'text'
    ];
    
    echo "   📤 API Data: " . json_encode($messageData) . "\n";
    
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
    } else {
        echo "   📥 HTTP Code: $httpCode\n";
        echo "   📥 Response: $response\n";
        
        $responseData = json_decode($response, true);
        if ($responseData && isset($responseData['success'])) {
            echo "   📥 API Success: " . ($responseData['success'] ? 'Yes' : 'No') . "\n";
        }
    }
    
    echo "\n🎯 Summary:\n";
    echo "✅ Direct push notification test completed\n";
    echo "✅ API call test completed\n";
    echo "\n📱 Expected Results:\n";
    echo "1. Direct push notification should work if Firebase is configured correctly\n";
    echo "2. API call should also trigger a push notification\n";
    echo "3. Check the device for push notifications\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "🔧 Stack trace: " . $e->getTraceAsString() . "\n";
}
?> 