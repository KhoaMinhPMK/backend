<?php
require_once 'config.php';

echo "<h1>Simple OTP Test</h1>";

// Test data
$email = 'phamquochuy131106@gmail.com';
$otp = '361027'; // Replace with actual OTP
$newPassword = 'newpassword123';

echo "<h2>Testing with:</h2>";
echo "Email: $email<br>";
echo "OTP: $otp<br>";
echo "New Password: $newPassword<br><br>";

try {
    $pdo = getDatabaseConnection();
    
    // Step 1: Find user
    echo "<h3>Step 1: Finding User</h3>";
    $userSql = "SELECT userId, userName FROM user WHERE email = ? LIMIT 1";
    $userStmt = $pdo->prepare($userSql);
    $userStmt->execute([$email]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ User not found<br>";
        exit;
    }
    
    echo "✅ User found: {$user['userName']} (ID: {$user['userId']})<br><br>";
    
    // Step 2: Check OTP
    echo "<h3>Step 2: Checking OTP</h3>";
    $otpSql = "SELECT * FROM password_reset_otp WHERE user_id = ? AND otp = ? LIMIT 1";
    $otpStmt = $pdo->prepare($otpSql);
    $otpStmt->execute([$user['userId'], $otp]);
    $otpRecord = $otpStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$otpRecord) {
        echo "❌ OTP not found<br>";
        exit;
    }
    
    echo "✅ OTP found in database<br>";
    echo "OTP ID: {$otpRecord['id']}<br>";
    echo "Used: " . ($otpRecord['used'] ? 'Yes' : 'No') . "<br>";
    echo "Expires: {$otpRecord['expires_at']}<br>";
    
    // Check if used
    if ($otpRecord['used'] == 1) {
        echo "❌ OTP already used<br>";
        exit;
    }
    
    // Check if expired
    $now = new DateTime();
    $expires = new DateTime($otpRecord['expires_at']);
    if ($now > $expires) {
        echo "❌ OTP expired<br>";
        exit;
    }
    
    echo "✅ OTP is valid<br><br>";
    
    // Step 3: Update password
    echo "<h3>Step 3: Updating Password</h3>";
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    echo "✅ Password hashed<br>";
    
    $updateSql = "UPDATE user SET password = ?, updated_at = NOW() WHERE userId = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateResult = $updateStmt->execute([$hashedPassword, $user['userId']]);
    
    if (!$updateResult) {
        echo "❌ Failed to update password<br>";
        exit;
    }
    
    echo "✅ Password updated successfully<br><br>";
    
    // Step 4: Mark OTP as used
    echo "<h3>Step 4: Marking OTP as Used</h3>";
    $markUsedSql = "UPDATE password_reset_otp SET used = 1, used_at = NOW() WHERE id = ?";
    $markUsedStmt = $pdo->prepare($markUsedSql);
    $markUsedResult = $markUsedStmt->execute([$otpRecord['id']]);
    
    if ($markUsedResult) {
        echo "✅ OTP marked as used<br><br>";
    } else {
        echo "❌ Failed to mark OTP as used<br><br>";
    }
    
    // Step 5: Verify password was updated
    echo "<h3>Step 5: Verifying Password Update</h3>";
    $verifySql = "SELECT password FROM user WHERE userId = ?";
    $verifyStmt = $pdo->prepare($verifySql);
    $verifyStmt->execute([$user['userId']]);
    $updatedUser = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($updatedUser && password_verify($newPassword, $updatedUser['password'])) {
        echo "✅ Password verification successful!<br>";
        echo "The new password works correctly.<br><br>";
    } else {
        echo "❌ Password verification failed<br><br>";
    }
    
    echo "<h2>🎉 SUCCESS! Password change completed</h2>";
    echo "<p>The OTP verification and password update worked correctly.</p>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Try the password change in the app again</li>";
echo "<li>If it still fails, check the server logs</li>";
echo "<li>Generate a new OTP if needed</li>";
echo "</ol>";
?> 