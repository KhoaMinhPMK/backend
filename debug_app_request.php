<?php
require_once 'config.php';

echo "<h1>Debug App Request</h1>";
echo "<p>This script will capture and analyze the exact request from your app.</p>";
echo "<p>Make a request from your app to change password, then refresh this page to see the captured data.</p>";

// Create a log file to capture requests
$logFile = 'app_request_log.txt';

// Capture the request data
$requestData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'NOT_SET',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'NOT_SET',
    'raw_input' => file_get_contents('php://input'),
    'post_data' => $_POST,
    'get_data' => $_GET,
    'headers' => getallheaders()
];

// Write to log file
file_put_contents($logFile, json_encode($requestData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

// Display current log
if (file_exists($logFile)) {
    echo "<h2>Captured Request Data:</h2>";
    $logContent = file_get_contents($logFile);
    $requests = explode("\n\n", trim($logContent));
    
    if (count($requests) > 0) {
        $latestRequest = json_decode($requests[count($requests) - 1], true);
        
        if ($latestRequest) {
            echo "<h3>Latest Request:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            echo "<tr><td>Timestamp</td><td>{$latestRequest['timestamp']}</td></tr>";
            echo "<tr><td>Method</td><td>{$latestRequest['method']}</td></tr>";
            echo "<tr><td>Content-Type</td><td>{$latestRequest['content_type']}</td></tr>";
            echo "<tr><td>Raw Input</td><td><pre>" . htmlspecialchars($latestRequest['raw_input']) . "</pre></td></tr>";
            echo "</table>";
            
            // Try to parse JSON
            if (!empty($latestRequest['raw_input'])) {
                $jsonData = json_decode($latestRequest['raw_input'], true);
                if ($jsonData) {
                    echo "<h3>Parsed JSON Data:</h3>";
                    echo "<pre>" . json_encode($jsonData, JSON_PRETTY_PRINT) . "</pre>";
                    
                    // Validate the data
                    echo "<h3>Data Validation:</h3>";
                    $email = $jsonData['email'] ?? null;
                    $otp = $jsonData['otp'] ?? null;
                    $newPassword = $jsonData['newPassword'] ?? null;
                    
                    echo "Email: " . ($email ? "✅ $email" : "❌ Missing") . "<br>";
                    echo "OTP: " . ($otp ? "✅ $otp" : "❌ Missing") . "<br>";
                    echo "New Password: " . ($newPassword ? "✅ " . substr($newPassword, 0, 3) . "***" : "❌ Missing") . "<br>";
                    
                    if ($email && $otp && $newPassword) {
                        echo "<p>✅ All required fields are present</p>";
                        
                        // Test the OTP
                        try {
                            $pdo = getDatabaseConnection();
                            
                            // Find user
                            $userSql = "SELECT userId, userName FROM user WHERE email = ? LIMIT 1";
                            $userStmt = $pdo->prepare($userSql);
                            $userStmt->execute([$email]);
                            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($user) {
                                echo "✅ User found: {$user['userName']} (ID: {$user['userId']})<br>";
                                
                                // Check OTP
                                $otpSql = "SELECT * FROM password_reset_otp WHERE user_id = ? AND otp = ? AND expires_at > NOW() AND used = 0 ORDER BY created_at DESC LIMIT 1";
                                $otpStmt = $pdo->prepare($otpSql);
                                $otpStmt->execute([$user['userId'], $otp]);
                                $otpRecord = $otpStmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($otpRecord) {
                                    echo "✅ OTP is valid and can be used<br>";
                                } else {
                                    echo "❌ OTP verification failed<br>";
                                    
                                    // Check what's wrong
                                    $checkOtpSql = "SELECT * FROM password_reset_otp WHERE user_id = ? AND otp = ?";
                                    $checkOtpStmt = $pdo->prepare($checkOtpSql);
                                    $checkOtpStmt->execute([$user['userId'], $otp]);
                                    $otpExists = $checkOtpStmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    if ($otpExists) {
                                        if ($otpExists['used']) {
                                            echo "❌ OTP has been used<br>";
                                        }
                                        if (strtotime($otpExists['expires_at']) <= time()) {
                                            echo "❌ OTP has expired<br>";
                                        }
                                    } else {
                                        echo "❌ OTP not found in database<br>";
                                    }
                                }
                            } else {
                                echo "❌ User not found with email: $email<br>";
                            }
                        } catch (Exception $e) {
                            echo "❌ Database error: " . $e->getMessage() . "<br>";
                        }
                    } else {
                        echo "<p>❌ Missing required fields</p>";
                    }
                } else {
                    echo "<p>❌ Failed to parse JSON data</p>";
                    echo "<p>JSON Error: " . json_last_error_msg() . "</p>";
                }
            }
        }
    }
} else {
    echo "<p>No requests captured yet. Make a request from your app first.</p>";
}

echo "<h2>Instructions:</h2>";
echo "<ol>";
echo "<li>Keep this page open</li>";
echo "<li>Go to your app and try to change password</li>";
echo "<li>Come back to this page and refresh it</li>";
echo "<li>You'll see the exact request data from your app</li>";
echo "</ol>";

echo "<h2>Clear Log</h2>";
echo "<p><a href='?clear=1'>Clear captured data</a></p>";

// Clear log if requested
if (isset($_GET['clear']) && $_GET['clear'] == '1') {
    if (file_exists($logFile)) {
        unlink($logFile);
    }
    echo "<script>location.reload();</script>";
}
?> 