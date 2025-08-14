<?php
require_once 'config.php';

echo "<h1>Find Current OTP for User</h1>";

$userEmail = process.env.VIEGRAND_EMAIL;

echo "<h2>Looking for OTP for: $userEmail</h2>";

try {
    $pdo = getDatabaseConnection();
    
    // Find the user
    $userSql = "SELECT userId, userName, email FROM user WHERE email = ? LIMIT 1";
    $userStmt = $pdo->prepare($userSql);
    $userStmt->execute([$userEmail]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ User not found with email: $userEmail<br>";
        exit;
    }
    
    echo "✅ Found user: {$user['userName']} (ID: {$user['userId']})<br>";
    
    // Find all OTP records for this user
    $otpSql = "SELECT * FROM password_reset_otp WHERE user_id = ? ORDER BY created_at DESC";
    $otpStmt = $pdo->prepare($otpSql);
    $otpStmt->execute([$user['userId']]);
    $otpRecords = $otpStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($otpRecords) == 0) {
        echo "❌ No OTP records found for this user<br>";
        echo "<p><a href='send_otp_email_gmail.php'>Generate new OTP</a></p>";
        exit;
    }
    
    echo "<h3>OTP Records Found:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>OTP</th><th>Expires</th><th>Used</th><th>Created</th><th>Status</th></tr>";
    
    $validOtps = [];
    
    foreach ($otpRecords as $record) {
        $now = new DateTime();
        $expires = new DateTime($record['expires_at']);
        $isExpired = $now > $expires;
        $isValid = !$record['used'] && !$isExpired;
        
        $status = $isValid ? "✅ Valid" : ($record['used'] ? "❌ Used" : "❌ Expired");
        
        if ($isValid) {
            $validOtps[] = $record;
        }
        
        echo "<tr>";
        echo "<td>{$record['id']}</td>";
        echo "<td><strong>{$record['otp']}</strong></td>";
        echo "<td>{$record['expires_at']}</td>";
        echo "<td>" . ($record['used'] ? 'Yes' : 'No') . "</td>";
        echo "<td>{$record['created_at']}</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if (count($validOtps) > 0) {
        echo "<h3>✅ Valid OTPs Available:</h3>";
        foreach ($validOtps as $otp) {
            echo "<p><strong>Use this OTP: {$otp['otp']}</strong></p>";
            echo "<p>Expires: {$otp['expires_at']}</p>";
        }
        
        echo "<h3>Test API Call:</h3>";
        echo "<p>You can test the API with this curl command:</p>";
        echo "<pre>";
        echo "curl -X POST https://viegrand.site/backend/verify_otp_and_change_password.php \\\n";
        echo "  -H 'Content-Type: application/json' \\\n";
        echo "  -d '{\n";
        echo "    \"email\": \"$userEmail\",\n";
        echo "    \"otp\": \"{$validOtps[0]['otp']}\",\n";
        echo "    \"newPassword\": \"newpassword123\"\n";
        echo "  }'";
        echo "</pre>";
        
    } else {
        echo "<h3>❌ No Valid OTPs Found</h3>";
        echo "<p>All OTPs are either expired or already used.</p>";
        echo "<p><a href='send_otp_email_gmail.php'>Generate new OTP</a></p>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Copy the valid OTP from above</li>";
echo "<li>Use it in the app to change password</li>";
echo "<li>If no valid OTPs, generate a new one</li>";
echo "</ol>";
?> 