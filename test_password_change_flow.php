<?php
require_once 'config.php';

echo "<h1>Test Password Change Flow</h1>";

$userEmail = 'phamquochuy131106@gmail.com';
$testOtp = '123456'; // Replace with actual OTP
$newPassword = 'newpassword123';

echo "<h2>Testing with:</h2>";
echo "<ul>";
echo "<li>Email: $userEmail</li>";
echo "<li>OTP: $testOtp</li>";
echo "<li>New Password: $newPassword</li>";
echo "</ul>";

try {
    $pdo = getDatabaseConnection();
    echo "✅ Database connection successful<br><br>";
    
    // Step 1: Find user
    echo "<h3>Step 1: Finding User</h3>";
    $userSql = "SELECT userId, userName, email FROM user WHERE email = ? LIMIT 1";
    $userStmt = $pdo->prepare($userSql);
    $userStmt->execute([$userEmail]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ User not found<br>";
        exit;
    }
    
    echo "✅ User found: {$user['userName']} (ID: {$user['userId']})<br><br>";
    
    // Step 2: Check OTP
    echo "<h3>Step 2: Checking OTP</h3>";
    $otpSql = "SELECT * FROM password_reset_otp WHERE user_id = ? AND otp = ? AND expires_at > NOW() AND used = 0 ORDER BY created_at DESC LIMIT 1";
    $otpStmt = $pdo->prepare($otpSql);
    $otpStmt->execute([$user['userId'], $testOtp]);
    $otpRecord = $otpStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$otpRecord) {
        echo "❌ OTP verification failed<br>";
        
        // Check what OTPs exist
        $allOtpsSql = "SELECT * FROM password_reset_otp WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
        $allOtpsStmt = $pdo->prepare($allOtpsSql);
        $allOtpsStmt->execute([$user['userId']]);
        $allOtps = $allOtpsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($allOtps) > 0) {
            echo "<h4>Available OTPs:</h4>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>OTP</th><th>Expires</th><th>Used</th><th>Status</th></tr>";
            
            foreach ($allOtps as $otp) {
                $now = new DateTime();
                $expires = new DateTime($otp['expires_at']);
                $isExpired = $now > $expires;
                $isValid = !$otp['used'] && !$isExpired;
                
                $status = $isValid ? "✅ Valid" : ($otp['used'] ? "❌ Used" : "❌ Expired");
                
                echo "<tr>";
                echo "<td>{$otp['otp']}</td>";
                echo "<td>{$otp['expires_at']}</td>";
                echo "<td>" . ($otp['used'] ? 'Yes' : 'No') . "</td>";
                echo "<td>$status</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "❌ No OTP records found<br>";
        }
        
        echo "<p><a href='send_otp_email_gmail.php'>Generate new OTP</a></p>";
        exit;
    }
    
    echo "✅ OTP verification successful<br>";
    echo "OTP ID: {$otpRecord['id']}<br>";
    echo "OTP: {$otpRecord['otp']}<br>";
    echo "Expires: {$otpRecord['expires_at']}<br><br>";
    
    // Step 3: Hash password
    echo "<h3>Step 3: Hashing Password</h3>";
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    echo "✅ Password hashed successfully<br>";
    echo "Original: $newPassword<br>";
    echo "Hashed: " . substr($hashedPassword, 0, 20) . "...<br><br>";
    
    // Step 4: Update password
    echo "<h3>Step 4: Updating Password</h3>";
    $updateSql = "UPDATE user SET password = ?, updated_at = NOW() WHERE userId = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateResult = $updateStmt->execute([$hashedPassword, $user['userId']]);
    
    if (!$updateResult) {
        echo "❌ Failed to update password<br>";
        exit;
    }
    
    echo "✅ Password updated successfully<br><br>";
    
    // Step 5: Mark OTP as used
    echo "<h3>Step 5: Marking OTP as Used</h3>";
    $markUsedSql = "UPDATE password_reset_otp SET used = 1, used_at = NOW() WHERE id = ?";
    $markUsedStmt = $pdo->prepare($markUsedSql);
    $markUsedResult = $markUsedStmt->execute([$otpRecord['id']]);
    
    if (!$markUsedResult) {
        echo "❌ Failed to mark OTP as used<br>";
    } else {
        echo "✅ OTP marked as used<br><br>";
    }
    
    // Step 6: Clean up other OTPs
    echo "<h3>Step 6: Cleaning Up Other OTPs</h3>";
    $cleanupSql = "DELETE FROM password_reset_otp WHERE user_id = ? AND used = 0";
    $cleanupStmt = $pdo->prepare($cleanupSql);
    $cleanupResult = $cleanupStmt->execute([$user['userId']]);
    
    if ($cleanupResult) {
        $deletedCount = $cleanupStmt->rowCount();
        echo "✅ Cleaned up $deletedCount unused OTPs<br><br>";
    } else {
        echo "❌ Failed to clean up OTPs<br><br>";
    }
    
    // Step 7: Verify password was updated
    echo "<h3>Step 7: Verifying Password Update</h3>";
    $verifySql = "SELECT password FROM user WHERE userId = ?";
    $verifyStmt = $pdo->prepare($verifySql);
    $verifyStmt->execute([$user['userId']]);
    $updatedUser = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($updatedUser && password_verify($newPassword, $updatedUser['password'])) {
        echo "✅ Password verification successful!<br>";
        echo "The new password works correctly.<br><br>";
    } else {
        echo "❌ Password verification failed<br>";
    }
    
    echo "<h2>🎉 Password Change Flow Completed Successfully!</h2>";
    echo "<p>The password has been updated and the OTP has been marked as used.</p>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Try logging in with the new password</li>";
echo "<li>Test the app password change feature</li>";
echo "</ol>";
?> 