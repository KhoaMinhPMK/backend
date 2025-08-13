<?php
require_once 'config.php';

echo "<h1>OTP Setup Test</h1>";

// Test 1: Check if OTP table exists
echo "<h2>1. Checking OTP Table</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'password_reset_otp'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✅ OTP table exists<br>";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE password_reset_otp");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ OTP table does not exist<br>";
        echo "<p>Please run the SQL script: <code>create_otp_table.sql</code></p>";
    }
} catch (Exception $e) {
    echo "❌ Error checking table: " . $e->getMessage() . "<br>";
}

// Test 2: Check if mail function is available
echo "<h2>2. Checking Mail Function</h2>";
if (function_exists('mail')) {
    echo "✅ PHP mail() function is available<br>";
} else {
    echo "❌ PHP mail() function is not available<br>";
}

// Test 3: Check mail configuration
echo "<h2>3. Mail Configuration</h2>";
$mailConfig = ini_get('sendmail_path');
if ($mailConfig) {
    echo "✅ Sendmail path: $mailConfig<br>";
} else {
    echo "⚠️ Sendmail path not configured<br>";
}

// Test 4: Test with a sample user
echo "<h2>4. Testing with Sample User</h2>";
try {
    $stmt = $pdo->prepare("SELECT userId, name, email FROM user WHERE email LIKE '%@%' LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ Found test user: {$user['name']} ({$user['email']})<br>";
        
        // Generate test OTP
        $testOtp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        
        // Insert test OTP
        $stmt = $pdo->prepare("INSERT INTO password_reset_otp (user_id, otp, expires_at, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user['userId'], $testOtp, $expiresAt]);
        
        echo "✅ Test OTP inserted: $testOtp<br>";
        
        // Test email sending
        $subject = 'Test OTP - VieGrand App';
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: VieGrand App <noreply@viegrandapp.com>',
            'Reply-To: support@viegrandapp.com',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        $htmlContent = "
        <html>
        <body>
            <h2>Test OTP Email</h2>
            <p>This is a test email for OTP functionality.</p>
            <p><strong>Test OTP: $testOtp</strong></p>
            <p>This OTP expires in 5 minutes.</p>
        </body>
        </html>";
        
        $emailSent = mail($user['email'], $subject, $htmlContent, implode("\r\n", $headers));
        
        if ($emailSent) {
            echo "✅ Test email sent successfully<br>";
        } else {
            echo "❌ Test email failed to send<br>";
        }
        
        // Clean up test OTP
        $stmt = $pdo->prepare("DELETE FROM password_reset_otp WHERE user_id = ? AND otp = ?");
        $stmt->execute([$user['userId'], $testOtp]);
        echo "✅ Test OTP cleaned up<br>";
        
    } else {
        echo "❌ No users found with email addresses<br>";
    }
} catch (Exception $e) {
    echo "❌ Error testing with user: " . $e->getMessage() . "<br>";
}

// Test 5: Show current OTP records
echo "<h2>5. Current OTP Records</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM password_reset_otp ORDER BY created_at DESC LIMIT 5");
    $otps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($otps) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>OTP</th><th>Expires</th><th>Used</th><th>Created</th></tr>";
        foreach ($otps as $otp) {
            echo "<tr>";
            echo "<td>{$otp['id']}</td>";
            echo "<td>{$otp['user_id']}</td>";
            echo "<td>{$otp['otp']}</td>";
            echo "<td>{$otp['expires_at']}</td>";
            echo "<td>" . ($otp['used'] ? 'Yes' : 'No') . "</td>";
            echo "<td>{$otp['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No OTP records found<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking OTP records: " . $e->getMessage() . "<br>";
}

echo "<h2>Setup Instructions</h2>";
echo "<ol>";
echo "<li>If the OTP table doesn't exist, run: <code>mysql -u your_username -p your_database < create_otp_table.sql</code></li>";
echo "<li>Make sure your server has mail() function enabled</li>";
echo "<li>Configure your server's mail settings if needed</li>";
echo "<li>Test the password change functionality in the app</li>";
echo "</ol>";

echo "<h2>Next Steps</h2>";
echo "<p>If all tests pass, you can now use the password change feature in the app:</p>";
echo "<ol>";
echo "<li>Go to Settings → Bảo mật</li>";
echo "<li>Enter your email</li>";
echo "<li>Check your email for OTP</li>";
echo "<li>Enter OTP and new password</li>";
echo "</ol>";
?> 