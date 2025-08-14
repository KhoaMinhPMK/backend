<?php
require_once 'config.php';

echo "<h1>Quick OTP Check</h1>";

$userEmail = process.env.VIEGRAND_EMAIL;
$otpToCheck = '361027';

echo "<h2>Checking OTP: $otpToCheck</h2>";

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
    
    echo "✅ User: {$user['userName']} (ID: {$user['userId']})<br>";
    
    // Check OTP
    $otpSql = "SELECT * FROM password_reset_otp WHERE user_id = ? AND otp = ?";
    $otpStmt = $pdo->prepare($otpSql);
    $otpStmt->execute([$user['userId'], $otpToCheck]);
    $otpRecord = $otpStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$otpRecord) {
        echo "❌ OTP not found<br>";
        exit;
    }
    
    echo "<h3>OTP Status:</h3>";
    echo "OTP: {$otpRecord['otp']}<br>";
    echo "Used: " . ($otpRecord['used'] ? 'Yes' : 'No') . "<br>";
    echo "Expires: {$otpRecord['expires_at']}<br>";
    
    $now = new DateTime();
    $expires = new DateTime($otpRecord['expires_at']);
    $isExpired = $now > $expires;
    $isUsed = $otpRecord['used'] == 1;
    
    if ($isUsed) {
        echo "<h3>❌ OTP has been used</h3>";
        echo "You need to generate a new OTP.<br>";
        echo "<p><a href='send_otp_email_gmail.php'>Generate new OTP</a></p>";
    } elseif ($isExpired) {
        echo "<h3>❌ OTP has expired</h3>";
        echo "You need to generate a new OTP.<br>";
        echo "<p><a href='send_otp_email_gmail.php'>Generate new OTP</a></p>";
    } else {
        echo "<h3>✅ OTP is valid</h3>";
        echo "The OTP should work. The issue might be in the app request.<br>";
        echo "<p><a href='debug_app_request.php'>Debug app request</a></p>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<h2>Debug Files:</h2>";
echo "<ul>";
echo "<li><a href='debug_request.log' target='_blank'>View request log</a></li>";
echo "<li><a href='debug_app_request.php'>Debug app request</a></li>";
echo "</ul>";
?> 