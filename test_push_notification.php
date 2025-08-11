<?php
require_once 'config.php';
require_once 'send_push_notification.php';

// Test configuration
$testEmail = 'a@gmail.com'; // Replace with a real user email from your database

echo "🧪 Testing push notification...\n";
echo "📧 Email: $testEmail\n";
echo "📱 Title: Test Push Notification\n";
echo "💬 Body: This is a test push notification from VieGrand app!\n\n";

try {
    // First, let's check if the user exists and has a device token
    $conn = getDatabaseConnection();
    
    // Check if device_token column exists
    $checkColumnSql = "SHOW COLUMNS FROM user LIKE 'device_token'";
    $checkColumnStmt = $conn->prepare($checkColumnSql);
    $checkColumnStmt->execute();
    $columnExists = $checkColumnStmt->rowCount() > 0;
    
    if (!$columnExists) {
        echo "❌ Device token column does not exist in user table!\n";
        echo "🔧 Please run the SQL command to add the column:\n";
        echo "ALTER TABLE user ADD COLUMN devi54 Unknown column 'userId' in 'field list' 🔧 Stack ce_token VARCHAR(255) DEFAULT NULL;\n\n";
        exit;
    }
    
    // Get user info including device token
    $userSql = "SELECT userId, email, device_token FROM user WHERE email = ?";
    $userStmt = $conn->prepare($userSql);
    $userStmt->execute([$testEmail]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ User not found with email: $testEmail\n";
        echo "🔧 Please update the \$testEmail variable with a real user email\n\n";
        exit;
    }
    
    echo "👤 User found:\n";
    echo "   ID: {$user['userId']}\n";
    echo "   Email: {$user['email']}\n";
    echo "   Device Token: " . ($user['device_token'] ? substr($user['device_token'], 0, 50) . '...' : 'NULL') . "\n\n";
    
    if (!$user['device_token']) {
        echo "❌ No device token found for user\n";
        echo "🔧 To fix this:\n";
        echo "   1. Make sure the user has logged into the app\n";
        echo "   2. The app should automatically save the device token\n";
        echo "   3. Check if the updateDeviceToken API is working\n\n";
        exit;
    }
    
    // Test push notification
    $result = sendPushNotification(
        $testEmail,
        'Test Push Notification',
        'This is a test push notification from VieGrand app!',
        [
            'type' => 'test',
            'timestamp' => time()
        ]
    );
    
    echo "📊 Result: " . ($result['success'] ? '✅ Success' : '❌ Failed') . "\n";
    if (isset($result['message'])) {
        echo "💬 Message: {$result['message']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "🔧 Stack trace: " . $e->getTraceAsString() . "\n";
}
?> 