<?php
require_once 'config.php';

echo "🧪 Testing Message Sending\n";
echo "==========================\n\n";

// Test configuration
$testData = [
    'sender_phone' => '0123456789',
    'receiver_phone' => '0987654321',
    'message_text' => 'Test message from API',
    'message_type' => 'text'
];

echo "📧 Test Data:\n";
echo "   Sender: {$testData['sender_phone']}\n";
echo "   Receiver: {$testData['receiver_phone']}\n";
echo "   Message: {$testData['message_text']}\n";
echo "   Type: {$testData['message_type']}\n\n";

try {
    // Test the send_message_complete.php endpoint
    $url = 'https://viegrand.site/backend/send_message_complete.php';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    echo "🔄 Sending request to: $url\n";
    echo "📤 Request data: " . json_encode($testData) . "\n\n";
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        echo "❌ cURL Error: $curlError\n";
        exit;
    }
    
    echo "📥 Response HTTP Code: $httpCode\n";
    echo "📥 Response Body: $response\n\n";
    
    $responseData = json_decode($response, true);
    
    if ($httpCode === 200 && $responseData && isset($responseData['success'])) {
        if ($responseData['success']) {
            echo "✅ Message sent successfully!\n";
            echo "   Message ID: " . ($responseData['data']['message_id'] ?? 'N/A') . "\n";
            echo "   Conversation ID: " . ($responseData['data']['conversation_id'] ?? 'N/A') . "\n";
            echo "   Socket Delivered: " . ($responseData['data']['socket_delivered'] ? 'Yes' : 'No') . "\n";
        } else {
            echo "❌ Message sending failed!\n";
            echo "   Error: " . ($responseData['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "❌ Invalid response format!\n";
        echo "   HTTP Code: $httpCode\n";
        echo "   Response: $response\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "🔧 Stack trace: " . $e->getTraceAsString() . "\n";
}
?> 