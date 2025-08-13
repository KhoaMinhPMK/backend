<?php
require_once 'config.php';

echo "<h1>Check OTP Status</h1>";

$userEmail = 'phamquochuy131106@gmail.com';
$otpToCheck = '361027';

echo "<h2>Checking OTP: $otpToCheck for user: $userEmail</h2>";

try {
    $pdo = getDatabaseConnection();
    
    // Find user
    $userSql = "SELECT userId, userName FROM user WHERE email = ? LIMIT 1";
    $userStmt = $pdo->prepare($userSql);
    $userStmt->execute([$userEmail]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ User not found<br>";
        exit;
    }
    
    echo "✅ User: {$user['userName']} (ID: {$user['userId']})<br><br>";
    
    // Check OTP status
    $otpSql = "SELECT * FROM password_reset_otp WHERE user_id = ? AND otp = ?";
    $otpStmt = $pdo->prepare($otpSql);
    $otpStmt->execute([$user['userId'], $otpToCheck]);
    $otpRecord = $otpStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$otpRecord) {
        echo "❌ OTP not found in database<br>";
        exit;
    }
    
    echo "<h3>OTP Details:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>ID</td><td>{$otpRecord['id']}</td></tr>";
    echo "<tr><td>OTP</td><td>{$otpRecord['otp']}</td></tr>";
    echo "<tr><td>User ID</td><td>{$otpRecord['user_id']}</td></tr>";
    echo "<tr><td>Expires At</td><td>{$otpRecord['expires_at']}</td></tr>";
    echo "<tr><td>Used</td><td>" . ($otpRecord['used'] ? 'Yes' : 'No') . "</td></tr>";
    echo "<tr><td>Used At</td><td>" . ($otpRecord['used_at'] ?? 'Not used') . "</td></tr>";
    echo "<tr><td>Created At</td><td>{$otpRecord['created_at']}</td></tr>";
    echo "</table><br>";
    
    // Check status
    $now = new DateTime();
    $expires = new DateTime($otpRecord['expires_at']);
    $isExpired = $now > $expires;
    $isUsed = $otpRecord['used'] == 1;
    
    echo "<h3>Status Check:</h3>";
    if ($isUsed) {
        echo "❌ OTP has been used<br>";
        echo "Used at: {$otpRecord['used_at']}<br>";
    } elseif ($isExpired) {
        echo "❌ OTP has expired<br>";
        echo "Expired at: {$otpRecord['expires_at']}<br>";
        echo "Current time: " . $now->format('Y-m-d H:i:s') . "<br>";
    } else {
        echo "✅ OTP is valid and can be used<br>";
        echo "Expires at: {$otpRecord['expires_at']}<br>";
        echo "Current time: " . $now->format('Y-m-d H:i:s') . "<br>";
    }
    
    // Test the verification query
    echo "<h3>Verification Query Test:</h3>";
    $verifySql = "SELECT * FROM password_reset_otp WHERE user_id = ? AND otp = ? AND expires_at > NOW() AND used = 0 ORDER BY created_at DESC LIMIT 1";
    $verifyStmt = $pdo->prepare($verifySql);
    $verifyStmt->execute([$user['userId'], $otpToCheck]);
    $verifyResult = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($verifyResult) {
        echo "✅ Verification query returns result - OTP should work<br>";
    } else {
        echo "❌ Verification query returns no result - OTP will fail<br>";
        
        // Check each condition
        echo "<h4>Individual Condition Check:</h4>";
        
        // Check user_id match
        $check1 = "SELECT COUNT(*) as count FROM password_reset_otp WHERE user_id = ? AND otp = ?";
        $stmt1 = $pdo->prepare($check1);
        $stmt1->execute([$user['userId'], $otpToCheck]);
        $result1 = $stmt1->fetch(PDO::FETCH_ASSOC);
        echo "User ID match: " . ($result1['count'] > 0 ? "✅" : "❌") . "<br>";
        
        // Check not expired
        $check2 = "SELECT COUNT(*) as count FROM password_reset_otp WHERE user_id = ? AND otp = ? AND expires_at > NOW()";
        $stmt2 = $pdo->prepare($check2);
        $stmt2->execute([$user['userId'], $otpToCheck]);
        $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);
        echo "Not expired: " . ($result2['count'] > 0 ? "✅" : "❌") . "<br>";
        
        // Check not used
        $check3 = "SELECT COUNT(*) as count FROM password_reset_otp WHERE user_id = ? AND otp = ? AND used = 0";
        $stmt3 = $pdo->prepare($check3);
        $stmt3->execute([$user['userId'], $otpToCheck]);
        $result3 = $stmt3->fetch(PDO::FETCH_ASSOC);
        echo "Not used: " . ($result3['count'] > 0 ? "✅" : "❌") . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>If OTP is used/expired, generate a new one</li>";
echo "<li>If OTP is valid, try the app again</li>";
echo "<li>Check the server logs for detailed error messages</li>";
echo "</ol>";
?> 