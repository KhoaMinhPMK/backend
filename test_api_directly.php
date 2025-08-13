<?php
require_once 'config.php';

echo "<h1>Test API Endpoint Directly</h1>";

// Test data
$testData = [
    'email' => 'phamquochuy131106@gmail.com',
    'otp' => '361027',
    'newPassword' => 'newpassword123'
];

echo "<h2>Testing with:</h2>";
echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT) . "</pre>";

// Simulate the API call
try {
    // Get JSON input (simulate what the API receives)
    $input = json_encode($testData);
    $data = json_decode($input, true);
    
    echo "<h3>Step 1: JSON Decode</h3>";
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "❌ JSON decode error: " . json_last_error_msg() . "<br>";
        exit;
    }
    echo "✅ JSON decoded successfully<br>";
    
    // Extract required fields
    $email = isset($data['email']) ? sanitizeInput($data['email']) : null;
    $otp = isset($data['otp']) ? sanitizeInput($data['otp']) : null;
    $newPassword = isset($data['newPassword']) ? $data['newPassword'] : null;
    
    echo "<h3>Step 2: Field Extraction</h3>";
    echo "Email: $email<br>";
    echo "OTP: $otp<br>";
    echo "New Password: " . substr($newPassword, 0, 3) . "***<br>";
    
    if (!$email || !$otp || !$newPassword) {
        echo "❌ Missing required fields<br>";
        exit;
    }
    echo "✅ All required fields present<br>";
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "❌ Invalid email format<br>";
        exit;
    }
    echo "✅ Email format valid<br>";
    
    // Validate password strength
    if (strlen($newPassword) < 6) {
        echo "❌ Password too short<br>";
        exit;
    }
    echo "✅ Password length valid<br>";
    
    // Get database connection
    $conn = getDatabaseConnection();
    echo "✅ Database connection successful<br>";
    
    // Check if user exists
    $checkUserSql = "SELECT userId, userName FROM user WHERE email = ? LIMIT 1";
    $checkUserStmt = $conn->prepare($checkUserSql);
    $checkUserStmt->execute([$email]);
    $user = $checkUserStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ User not found<br>";
        exit;
    }
    echo "✅ User found: {$user['userName']} (ID: {$user['userId']})<br>";
    
    // Verify OTP
    $verifyOtpSql = "SELECT * FROM password_reset_otp WHERE user_id = ? AND otp = ? AND expires_at > NOW() AND used = 0 ORDER BY created_at DESC LIMIT 1";
    $verifyOtpStmt = $conn->prepare($verifyOtpSql);
    $verifyOtpStmt->execute([$user['userId'], $otp]);
    $otpRecord = $verifyOtpStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$otpRecord) {
        echo "❌ OTP verification failed<br>";
        
        // Check what's wrong with the OTP
        $checkOtpSql = "SELECT * FROM password_reset_otp WHERE user_id = ? AND otp = ?";
        $checkOtpStmt = $conn->prepare($checkOtpSql);
        $checkOtpStmt->execute([$user['userId'], $otp]);
        $otpExists = $checkOtpStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($otpExists) {
            echo "OTP exists but: ";
            if ($otpExists['used']) {
                echo "❌ Already used<br>";
            }
            if (strtotime($otpExists['expires_at']) <= time()) {
                echo "❌ Expired (expired at: {$otpExists['expires_at']})<br>";
            }
        } else {
            echo "❌ OTP not found in database<br>";
        }
        exit;
    }
    
    echo "✅ OTP verification successful<br>";
    echo "OTP ID: {$otpRecord['id']}<br>";
    
    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    echo "✅ Password hashed<br>";
    
    // Update user password
    $updatePasswordSql = "UPDATE user SET password = ?, updated_at = NOW() WHERE userId = ?";
    $updatePasswordStmt = $conn->prepare($updatePasswordSql);
    $updatePasswordResult = $updatePasswordStmt->execute([$hashedPassword, $user['userId']]);
    
    if (!$updatePasswordResult) {
        echo "❌ Failed to update password<br>";
        exit;
    }
    echo "✅ Password updated successfully<br>";
    
    // Mark OTP as used
    $markOtpUsedSql = "UPDATE password_reset_otp SET used = 1, used_at = NOW() WHERE id = ?";
    $markOtpUsedStmt = $conn->prepare($markOtpUsedSql);
    $markOtpUsedStmt->execute([$otpRecord['id']]);
    echo "✅ OTP marked as used<br>";
    
    // Delete all other unused OTPs for this user
    $deleteOtherOtpsSql = "DELETE FROM password_reset_otp WHERE user_id = ? AND used = 0";
    $deleteOtherOtpsStmt = $conn->prepare($deleteOtherOtpsSql);
    $deleteOtherOtpsStmt->execute([$user['userId']]);
    echo "✅ Other OTPs cleaned up<br>";
    
    echo "<h2>🎉 SUCCESS! Password change completed</h2>";
    echo "<p>The API endpoint works correctly. The issue might be in the app's request format.</p>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>If this test passes, the issue is in the app</li>";
echo "<li>Check the app's network request format</li>";
echo "<li>Verify the app is sending the correct data</li>";
echo "</ol>";
?> 