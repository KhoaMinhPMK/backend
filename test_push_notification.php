<?php
require_once 'send_push_notification.php';

// Test push notification
$testEmail = 'test@example.com'; // Replace with a real user email
$testTitle = 'Test Push Notification';
$testBody = 'This is a test push notification from VieGrand app!';
$testData = [
    'type' => 'message',
    'conversation_id' => 'test_conv_123',
    'sender_phone' => '0123456789',
    'message_text' => 'Test message content'
];

echo "🧪 Testing push notification...\n";
echo "📧 Email: $testEmail\n";
echo "📱 Title: $testTitle\n";
echo "💬 Body: $testBody\n";

$result = sendPushNotification($testEmail, $testTitle, $testBody, $testData);

echo "\n📊 Result:\n";
echo "Success: " . ($result['success'] ? '✅ Yes' : '❌ No') . "\n";
echo "Message: " . $result['message'] . "\n";

if (isset($result['fcm_response'])) {
    echo "FCM Response: " . json_encode($result['fcm_response'], JSON_PRETTY_PRINT) . "\n";
}

echo "\n🎯 To test with a real user:\n";
echo "1. Update the \$testEmail variable with a real user email\n";
echo "2. Make sure the user has a device token saved\n";
echo "3. Run this test again\n";
?> 