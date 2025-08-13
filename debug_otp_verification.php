<?php
require_once 'config.php';

echo "<h1>Debug OTP Verification Process</h1>";

// Test data - replace with actual values from your app
$testEmail = 'pmkkhoaminh@gmail.com';
$testOtp = '123456'; // Replace with actual OTP from database

echo "<h2>1. Testing Database Connection</h2>";
try {
    $pdo = getDatabaseConnection();
    echo "✅ Database connection successful<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h2>2. Finding User</h2>";
try {
    $checkUserSql = "SELECT userId, userName, email FROM user WHERE email = ? LIMIT 1";
    $checkUserStmt = $pdo->prepare($checkUserSql);
    $checkUserStmt->execute([$testEmail]);
    $user = $checkUserStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ Found user: {$user['userName']} (ID: {$user['userId']})<br>";
        echo "Email: {$user['email']}<br>";
    } else {
        echo "❌ User not found with email: $testEmail<br>";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Error finding user: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h2>3. Checking OTP Records</h2>";
try {
    $otpSql = "SELECT * FROM password_reset_otp WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
    $otpStmt = $pdo->prepare($otpSql);
    $otpStmt->execute([$user['userId']]);
    $otpRecords = $otpStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($otpRecords) > 0) {
        echo "✅ Found " . count($otpRecords) . " OTP records for user<br>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>OTP</th><th>Expires</th><th>Used</th><th>Created</th><th>Status</th></tr>";
        
        foreach ($otpRecords as $record) {
            $now = new DateTime();
            $expires = new DateTime($record['expires_at']);
            $isExpired = $now > $expires;
            $isValid = !$record['used'] && !$isExpired;
            
            $status = $isValid ? "✅ Valid" : ($record['used'] ? "❌ Used" : "❌ Expired");
            
            echo "<tr>";
            echo "<td>{$record['id']}</td>";
            echo "<td>{$record['otp']}</td>";
            echo "<td>{$record['expires_at']}</td>";
            echo "<td>" . ($record['used'] ? 'Yes' : 'No') . "</td>";
            echo "<td>{$record['created_at']}</td>";
            echo "<td>$status</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ No OTP records found for user<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking OTP records: " . $e->getMessage() . "<br>";
}

echo "<h2>4. Testing OTP Verification Query</h2>";
try {
    $verifyOtpSql = "SELECT * FROM password_reset_otp WHERE user_id = ? AND otp = ? AND expires_at > NOW() AND used = 0 ORDER BY created_at DESC LIMIT 1";
    $verifyOtpStmt = $pdo->prepare($verifyOtpSql);
    $verifyOtpStmt->execute([$user['userId'], $testOtp]);
    $otpRecord = $verifyOtpStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($otpRecord) {
        echo "✅ OTP verification successful!<br>";
        echo "OTP ID: {$otpRecord['id']}<br>";
        echo "OTP: {$otpRecord['otp']}<br>";
        echo "Expires: {$otpRecord['expires_at']}<br>";
    } else {
        echo "❌ OTP verification failed<br>";
        echo "Possible reasons:<br>";
        echo "<ul>";
        echo "<li>OTP doesn't exist</li>";
        echo "<li>OTP has expired</li>";
        echo "<li>OTP has already been used</li>";
        echo "<li>OTP doesn't match</li>";
        echo "</ul>";
        
        // Let's check each condition separately
        echo "<h3>Detailed OTP Check:</h3>";
        
        // Check if OTP exists
        $checkOtpSql = "SELECT * FROM password_reset_otp WHERE user_id = ? AND otp = ?";
        $checkOtpStmt = $pdo->prepare($checkOtpSql);
        $checkOtpStmt->execute([$user['userId'], $testOtp]);
        $otpExists = $checkOtpStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($otpExists) {
            echo "✅ OTP exists in database<br>";
            
            // Check if expired
            $now = new DateTime();
            $expires = new DateTime($otpExists['expires_at']);
            if ($now > $expires) {
                echo "❌ OTP has expired (expired at: {$otpExists['expires_at']})<br>";
            } else {
                echo "✅ OTP has not expired<br>";
            }
            
            // Check if used
            if ($otpExists['used']) {
                echo "❌ OTP has already been used<br>";
            } else {
                echo "✅ OTP has not been used<br>";
            }
        } else {
            echo "❌ OTP does not exist in database<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Error in OTP verification: " . $e->getMessage() . "<br>";
}

echo "<h2>5. Testing Password Update (Simulation)</h2>";
try {
    $newPassword = 'testpassword123';
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    echo "✅ Password hashing successful<br>";
    echo "Original password: $newPassword<br>";
    echo "Hashed password: " . substr($hashedPassword, 0, 20) . "...<br>";
    
    // Note: We're not actually updating the password here, just testing the hash
    echo "✅ Password update simulation successful<br>";
} catch (Exception $e) {
    echo "❌ Error in password update: " . $e->getMessage() . "<br>";
}

echo "<h2>6. API Test</h2>";
echo "<p>To test the actual API endpoint, you can use this curl command:</p>";
echo "<pre>";
echo "curl -X POST https://viegrand.site/backend/verify_otp_and_change_password.php \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\n";
echo "    \"email\": \"$testEmail\",\n";
echo "    \"otp\": \"$testOtp\",\n";
echo "    \"newPassword\": \"newpassword123\"\n";
echo "  }'";
echo "</pre>";

echo "<h2>7. Troubleshooting Steps</h2>";
echo "<ol>";
echo "<li>Make sure you're using the correct OTP from the database</li>";
echo "<li>Check that the OTP hasn't expired (5 minutes)</li>";
echo "<li>Verify the OTP hasn't been used already</li>";
echo "<li>Ensure the email matches exactly</li>";
echo "<li>Check the app logs for any additional error details</li>";
echo "</ol>";

echo "<h2>8. Generate New OTP for Testing</h2>";
echo "<p><a href='send_otp_email_gmail.php' target='_blank'>Send new OTP email</a></p>";
echo "<p><a href='test_otp_setup.php' target='_blank'>Run complete OTP test</a></p>";
?> 