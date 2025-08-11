<?php
require_once 'config.php';
require_once 'send_push_notification.php';

echo "🔍 Debug Push Notification in Message API\n";
echo "=========================================\n\n";

// Test configuration - same as test_fixed_push.php
$senderEmail = 'ss@gmail.com';
$receiverEmail = 'z@gmail.com';

echo "📧 Test Configuration:\n";
echo "   Sender: $senderEmail\n";
echo "   Receiver: $receiverEmail\n\n";

try {
    $conn = getDatabaseConnection();
    
    // Step 1: Get user information (same as send_message_complete.php)
    echo "1️⃣ Getting user information...\n";
    
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
    
    echo "   ✅ Sender: {$sender['userName']} (Phone: {$sender['phone']})\n";
    echo "   ✅ Receiver: {$receiver['userName']} (Phone: {$receiver['phone']})\n";
    echo "   📱 Receiver Device Token: " . ($receiver['device_token'] ? substr($receiver['device_token'], 0, 50) . '...' : 'NULL') . "\n\n";
    
    if (!$receiver['device_token']) {
        echo "❌ Receiver has no device token!\n";
        exit;
    }
    
    // Step 2: Simulate the exact logic from send_message_complete.php
    echo "2️⃣ Simulating send_message_complete.php push notification logic...\n";
    
    $sender_phone = $sender['phone'];
    $receiver_phone = $receiver['phone'];
    $message_text = "Debug test message - " . date('Y-m-d H:i:s');
    $conversation_id = 'conv_test_' . time();
    $message_id = 'msg_' . time();
    $message_type = 'text';
    
    echo "   📤 Message: $message_text\n";
    echo "   📤 From: $sender_phone to $receiver_phone\n";
    echo "   📤 Conversation ID: $conversation_id\n";
    echo "   📤 Message ID: $message_id\n";
    
    // Step 3: Get receiver email by phone (same as send_message_complete.php)
    echo "\n3️⃣ Getting receiver email by phone...\n";
    
    $getReceiverEmailSql = "SELECT userId, email, userName FROM user WHERE phone = ?";
    $getReceiverEmailStmt = $conn->prepare($getReceiverEmailSql);
    $getReceiverEmailStmt->execute([$receiver_phone]);
    $receiverData = $getReceiverEmailStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$receiverData || !$receiverData['email']) {
        echo "❌ No receiver email found for phone: $receiver_phone\n";
        exit;
    }
    
    echo "   ✅ Receiver email found: {$receiverData['email']}\n";
    echo "   ✅ Receiver name: {$receiverData['userName']}\n";
    
    // Step 4: Get sender name by phone (same as send_message_complete.php)
    echo "\n4️⃣ Getting sender name by phone...\n";
    
    $getSenderNameSql = "SELECT userId, userName FROM user WHERE phone = ?";
    $getSenderNameStmt = $conn->prepare($getSenderNameSql);
    $getSenderNameStmt->execute([$sender_phone]);
    $senderData = $getSenderNameStmt->fetch(PDO::FETCH_ASSOC);
    
    $senderName = $senderData ? $senderData['userName'] : 'Người thân';
    $receiverName = $receiverData['userName'] ? $receiverData['userName'] : 'Người dùng';
    
    echo "   ✅ Sender name: $senderName\n";
    echo "   ✅ Receiver name: $receiverName\n";
    
    // Step 5: Create notification (same as send_message_complete.php)
    echo "\n5️⃣ Creating notification...\n";
    
    $notificationTitle = "Tin nhắn mới từ $senderName";
    $notificationBody = $message_text;
    
    echo "   📱 Title: $notificationTitle\n";
    echo "   📱 Body: $notificationBody\n";
    echo "   📱 To: {$receiverData['email']}\n";
    
    // Step 6: Send push notification (same as send_message_complete.php)
    echo "\n6️⃣ Sending push notification...\n";
    
    $push_result = sendPushNotification(
        $receiverData['email'],
        $notificationTitle,
        $notificationBody,
        [
            'type' => 'message',
            'conversation.id' => $conversation_id,
            'sender.phone' => $sender_phone,
            'receiver.phone' => $receiver_phone,
            'message.id' => $message_id,
            'message.text' => $message_text,
            'message.type' => $message_type,
            'sender.name' => $senderName,
            'receiver.name' => $receiverName,
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
    
    echo "\n🎯 Summary:\n";
    echo "✅ Simulated send_message_complete.php push notification logic\n";
    echo ($push_result['success'] ? "✅ Push notification should be sent" : "❌ Push notification failed") . "\n";
    echo "\n📱 Expected Result:\n";
    echo "1. If this test works, the issue is in the API call\n";
    echo "2. If this test fails, the issue is in the push notification logic\n";
    echo "3. Check the device for push notification\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "🔧 Stack trace: " . $e->getTraceAsString() . "\n";
}
?> 