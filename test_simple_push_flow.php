<?php
require_once 'config.php';

echo "🧪 Testing Simple Push Notification Flow\n";
echo "========================================\n\n";

// Test configuration - Update these with real user emails
$senderEmail = 'ss@gmail.com'; // Alex
$receiverEmail = 'z@gmail.com'; // Brian (update this)

echo "📧 Test Configuration:\n";
echo "   Alex (Sender): $senderEmail\n";
echo "   Brian (Receiver): $receiverEmail\n\n";

try {
    $conn = getDatabaseConnection();
    
    // Step 1: Get user information
    echo "1️⃣ Getting user information...\n";
    
    $senderSql = "SELECT id, email, phone, userName FROM user WHERE email = ?";
    $senderStmt = $conn->prepare($senderSql);
    $senderStmt->execute([$senderEmail]);
    $sender = $senderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sender) {
        echo "❌ Alex (sender) not found: $senderEmail\n";
        exit;
    }
    
    $receiverSql = "SELECT id, email, phone, userName, device_token FROM user WHERE email = ?";
    $receiverStmt = $conn->prepare($receiverSql);
    $receiverStmt->execute([$receiverEmail]);
    $receiver = $receiverStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$receiver) {
        echo "❌ Brian (receiver) not found: $receiverEmail\n";
        echo "🔧 Please update the \$receiverEmail variable with a real user\n";
        exit;
    }
    
    echo "   ✅ Alex: {$sender['userName']} ({$sender['phone']})\n";
    echo "   ✅ Brian: {$receiver['userName']} ({$receiver['phone']})\n";
    echo "   📱 Brian's Device Token: " . ($receiver['device_token'] ? substr($receiver['device_token'], 0, 50) . '...' : 'NULL') . "\n\n";
    
    if (!$receiver['device_token']) {
        echo "❌ Brian has no device token!\n";
        echo "🔧 Brian needs to log into the app to get a device token\n";
        exit;
    }
    
    // Step 2: Test the send_message_complete.php API
    echo "2️⃣ Testing send_message_complete.php API...\n";
    
    $testMessage = "Hello Brian! This is a test message from Alex - " . date('Y-m-d H:i:s');
    
    $messageData = [
        'sender_phone' => $sender['phone'],
        'receiver_phone' => $receiver['phone'],
        'message_text' => $testMessage,
        'message_type' => 'text'
    ];
    
    echo "   📤 Message: $testMessage\n";
    echo "   📤 From: {$sender['phone']} to {$receiver['phone']}\n";
    
    // Call the actual API
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
            echo "   📱 Brian should receive a push notification with:\n";
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
    echo "✅ User information retrieved\n";
    echo ($httpCode === 200 && $responseData && $responseData['success'] ? "✅ Message sent and push notification triggered" : "❌ Message sending failed") . "\n";
    echo "\n📱 Next steps:\n";
    echo "1. Make sure Brian's device has the app open\n";
    echo "2. Brian should receive a push notification\n";
    echo "3. Tap the notification to open the chat\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "🔧 Stack trace: " . $e->getTraceAsString() . "\n";
}
?> 