<?php
require_once 'config.php';

echo "🧪 Testing Complete Push Notification Flow\n";
echo "==========================================\n\n";

// Test configuration
$senderEmail = 'ss@gmail.com'; // Sender
$receiverEmail = 'test@example.com'; // Receiver (replace with real user)

echo "📧 Test Configuration:\n";
echo "   Sender: $senderEmail\n";
echo "   Receiver: $receiverEmail\n\n";

try {
    $conn = getDatabaseConnection();
    
    // Step 1: Get sender and receiver info
    echo "1️⃣ Getting user information...\n";
    
    $senderSql = "SELECT id, email, phone, userName FROM user WHERE email = ?";
    $senderStmt = $conn->prepare($senderSql);
    $senderStmt->execute([$senderEmail]);
    $sender = $senderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sender) {
        echo "❌ Sender not found: $senderEmail\n";
        exit;
    }
    
    $receiverSql = "SELECT id, email, phone, userName, device_token FROM user WHERE email = ?";
    $receiverStmt = $conn->prepare($receiverSql);
    $receiverStmt->execute([$receiverEmail]);
    $receiver = $receiverStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$receiver) {
        echo "❌ Receiver not found: $receiverEmail\n";
        echo "🔧 Please update the \$receiverEmail variable with a real user\n";
        exit;
    }
    
    echo "   ✅ Sender: {$sender['userName']} ({$sender['phone']})\n";
    echo "   ✅ Receiver: {$receiver['userName']} ({$receiver['phone']})\n";
    echo "   📱 Device Token: " . ($receiver['device_token'] ? substr($receiver['device_token'], 0, 50) . '...' : 'NULL') . "\n\n";
    
    if (!$receiver['device_token']) {
        echo "❌ Receiver has no device token!\n";
        echo "🔧 The receiver needs to log into the app to get a device token\n";
        exit;
    }
    
    // Step 2: Test message sending
    echo "2️⃣ Testing message sending...\n";
    
    $testMessage = "Test message from push notification flow - " . date('Y-m-d H:i:s');
    
    $messageData = [
        'sender_phone' => $sender['phone'],
        'receiver_phone' => $receiver['phone'],
        'message_text' => $testMessage,
        'message_type' => 'text'
    ];
    
    echo "   📤 Sending message: $testMessage\n";
    
    // Simulate the message sending process
    $conversationId = 'conv_' . md5(min($sender['phone'], $receiver['phone']) . max($sender['phone'], $receiver['phone']));
    
    // Insert message
    $insertSql = "INSERT INTO messages (conversation_id, sender_phone, receiver_phone, message_text, message_type, requires_friendship, friendship_status) VALUES (?, ?, ?, ?, ?, 1, '')";
    $insertStmt = $conn->prepare($insertSql);
    $insertResult = $insertStmt->execute([$conversationId, $sender['phone'], $receiver['phone'], $testMessage, 'text']);
    
    if ($insertResult) {
        $messageId = $conn->lastInsertId();
        echo "   ✅ Message inserted with ID: $messageId\n";
    } else {
        echo "   ❌ Failed to insert message\n";
        exit;
    }
    
    // Step 3: Test push notification
    echo "\n3️⃣ Testing push notification...\n";
    
    require_once 'send_push_notification.php';
    
    $notificationTitle = "Tin nhắn mới từ {$sender['userName']}";
    $notificationBody = $testMessage;
    
    $pushResult = sendPushNotification(
        $receiverEmail,
        $notificationTitle,
        $notificationBody,
        [
            'type' => 'message',
            'conversation_id' => $conversationId,
            'sender_phone' => $sender['phone'],
            'receiver_phone' => $receiver['phone'],
            'message_id' => $messageId,
            'message_text' => $testMessage,
            'message_type' => 'text'
        ]
    );
    
    if ($pushResult['success']) {
        echo "   ✅ Push notification sent successfully!\n";
        echo "   📱 Title: $notificationTitle\n";
        echo "   💬 Body: $notificationBody\n";
    } else {
        echo "   ❌ Push notification failed: {$pushResult['message']}\n";
    }
    
    // Step 4: Summary
    echo "\n🎯 Test Summary:\n";
    echo "✅ User information retrieved\n";
    echo "✅ Message inserted into database\n";
    echo ($pushResult['success'] ? "✅ Push notification sent" : "❌ Push notification failed") . "\n";
    echo "\n🚀 Push notification flow is " . ($pushResult['success'] ? "working correctly!" : "not working") . "\n";
    
    if ($pushResult['success']) {
        echo "\n📱 Next steps:\n";
        echo "1. Open the app on the receiver's device\n";
        echo "2. Make sure the receiver is NOT in the chat screen\n";
        echo "3. Send a real message from the sender\n";
        echo "4. Verify the push notification appears\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "🔧 Stack trace: " . $e->getTraceAsString() . "\n";
}
?> 