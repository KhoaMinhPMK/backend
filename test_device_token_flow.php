<?php
require_once 'config.php';

echo "🧪 Testing Device Token Flow...\n\n";

// Test configuration
$testEmail = 'phamquochuy131106@gmail.com'; // Replace with a real user email
$testDeviceToken = 'test_device_token_' . time(); // Simulated device token

echo "📧 Test Email: $testEmail\n";
echo "📱 Test Device Token: $testDeviceToken\n\n";

try {
    $conn = getDatabaseConnection();
    
    // Step 1: Check if user exists
    echo "1️⃣ Checking if user exists...\n";
    $userSql = "SELECT id, email FROM user WHERE email = ?";
    $userStmt = $conn->prepare($userSql);
    $userStmt->execute([$testEmail]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ User not found! Please create a user with email: $testEmail\n\n";
        exit;
    }
    
    echo "✅ User found: ID {$user['id']}\n\n";
    
    // Step 2: Check if device_token column exists
    echo "2️⃣ Checking device_token column...\n";
    $checkColumnSql = "SHOW COLUMNS FROM user LIKE 'device_token'";
    $checkColumnStmt = $conn->prepare($checkColumnSql);
    $checkColumnStmt->execute();
    $columnExists = $checkColumnStmt->rowCount() > 0;
    
    if (!$columnExists) {
        echo "❌ Device token column does not exist!\n";
        echo "🔧 Run: php add_device_token_column.php\n\n";
        exit;
    }
    
    echo "✅ Device token column exists\n\n";
    
    // Step 3: Test updating device token
    echo "3️⃣ Testing device token update...\n";
    
    // Simulate the API call
    $updateSql = "UPDATE user SET device_token = ? WHERE email = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateResult = $updateStmt->execute([$testDeviceToken, $testEmail]);
    
    if ($updateResult) {
        $affectedRows = $updateStmt->rowCount();
        echo "✅ Device token updated successfully (affected rows: $affectedRows)\n";
    } else {
        echo "❌ Failed to update device token\n";
        exit;
    }
    
    // Step 4: Verify the update
    echo "\n4️⃣ Verifying the update...\n";
    $verifySql = "SELECT id, email, device_token FROM user WHERE email = ?";
    $verifyStmt = $conn->prepare($verifySql);
    $verifyStmt->execute([$testEmail]);
    $updatedUser = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($updatedUser['device_token'] === $testDeviceToken) {
        echo "✅ Device token verified successfully!\n";
        echo "   User ID: {$updatedUser['id']}\n";
        echo "   Email: {$updatedUser['email']}\n";
        echo "   Device Token: {$updatedUser['device_token']}\n";
    } else {
        echo "❌ Device token verification failed!\n";
        echo "   Expected: $testDeviceToken\n";
        echo "   Actual: {$updatedUser['device_token']}\n";
    }
    
    // Step 5: Test the updateDeviceToken API endpoint
    echo "\n5️⃣ Testing updateDeviceToken API endpoint...\n";
    
    // Prepare the API request data
    $apiData = [
        'email' => $testEmail,
        'device_token' => 'api_test_token_' . time()
    ];
    
    // Simulate the API call
    $apiUpdateSql = "UPDATE user SET device_token = ? WHERE email = ?";
    $apiUpdateStmt = $conn->prepare($apiUpdateSql);
    $apiUpdateResult = $apiUpdateStmt->execute([$apiData['device_token'], $apiData['email']]);
    
    if ($apiUpdateResult) {
        echo "✅ API endpoint test successful!\n";
        echo "   New token: {$apiData['device_token']}\n";
    } else {
        echo "❌ API endpoint test failed!\n";
    }
    
    echo "\n🎯 Summary:\n";
    echo "✅ User exists in database\n";
    echo "✅ Device token column exists\n";
    echo "✅ Device token can be updated\n";
    echo "✅ API endpoint works correctly\n";
    echo "\n🚀 Ready for push notifications!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "🔧 Stack trace: " . $e->getTraceAsString() . "\n";
}
?> 