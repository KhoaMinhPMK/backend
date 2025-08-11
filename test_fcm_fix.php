<?php
require_once 'config.php';
require_once 'send_push_notification.php';

echo "🧪 Testing FCM Data Key Fix\n";
echo "===========================\n\n";

// Test configuration
$testEmail = 'z@gmail.com'; // Use the receiver email

echo "📧 Test Configuration:\n";
echo "   Email: $testEmail\n\n";

try {
    $conn = getDatabaseConnection();
    
    // Check if user exists and has device token
    $userSql = "SELECT userId, email, device_token FROM user WHERE email = ?";
    $userStmt = $conn->prepare($userSql);
    $userStmt->execute([$testEmail]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !$user['device_token']) {
        echo "❌ User not found or no device token: $testEmail\n";
        exit;
    }
    
    echo "✅ User found with device token\n";
    echo "📱 Device Token: " . substr($user['device_token'], 0, 50) . "...\n\n";
    
    // Test with valid FCM data keys (using dots instead of underscores)
    echo "📤 Testing push notification with valid FCM data keys...\n";
    
    $result = sendPushNotification(
        $testEmail,
        'FCM Fix Test',
        'Testing push notification with valid data keys - ' . date('Y-m-d H:i:s'),
        [
            'type' => 'message',
            'conversation.id' => 'conv_test_123',
            'sender.phone' => '1112223333',
            'receiver.phone' => '0000465723',
            'message.id' => 'msg_456',
            'message.text' => 'Test message content',
            'message.type' => 'text',
            'sender.name' => 'Test Sender',
            'receiver.name' => 'Test Receiver',
            'timestamp' => date('Y-m-d H:i:s')
        ]
    );
    
    echo "📥 Result: " . ($result['success'] ? '✅ Success' : '❌ Failed') . "\n";
    if (isset($result['message'])) {
        echo "📥 Message: {$result['message']}\n";
    }
    if (isset($result['fcm_response'])) {
        echo "📥 FCM Response: " . json_encode($result['fcm_response']) . "\n";
    }
    
    echo "\n🎯 Expected Result:\n";
    echo "✅ Push notification should be sent successfully\n";
    echo "✅ No FCM data key errors\n";
    echo "📱 Check device for notification\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "🔧 Stack trace: " . $e->getTraceAsString() . "\n";
}
?> 