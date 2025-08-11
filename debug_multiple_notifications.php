<?php
require_once 'config.php';
require_once 'send_push_notification.php';

echo "🔍 Debug Multiple Notifications\n";
echo "===============================\n\n";

// Test configuration
$senderEmail = 'ss@gmail.com';
$receiverEmail = 'z@gmail.com';

echo "📧 Test Configuration:\n";
echo "   Sender: $senderEmail\n";
echo "   Receiver: $receiverEmail\n\n";

try {
    $conn = getDatabaseConnection();
    
    // Get user information
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
    
    echo "✅ Sender: {$sender['userName']} (Phone: {$sender['phone']})\n";
    echo "✅ Receiver: {$receiver['userName']} (Phone: {$receiver['phone']})\n";
    echo "📱 Receiver Device Token: " . ($receiver['device_token'] ? substr($receiver['device_token'], 0, 50) . '...' : 'NULL') . "\n\n";
    
    if (!$receiver['device_token']) {
        echo "❌ Receiver has no device token!\n";
        exit;
    }
    
    // Test single push notification
    echo "📤 Testing single push notification...\n";
    
    $testMessage = "Single notification test - " . date('Y-m-d H:i:s');
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
            'conversation.id' => 'conv_test_123',
            'sender.phone' => $sender['phone'],
            'receiver.phone' => $receiver['phone'],
            'message.id' => 'msg_' . time(),
            'message.text' => $testMessage,
            'message.type' => 'text',
            'sender.name' => $sender['userName'],
            'receiver.name' => $receiver['userName'],
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
    
    echo "\n🎯 Expected Result:\n";
    echo "✅ Only ONE notification should appear on the device\n";
    echo "✅ If multiple notifications appear, the issue is in the frontend\n";
    echo "✅ If no notification appears, the issue is in the backend\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "🔧 Stack trace: " . $e->getTraceAsString() . "\n";
}
?> 