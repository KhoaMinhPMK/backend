<?php
echo "<h1>Gmail Email Test</h2>";

// Include required files
require_once 'config.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

echo "<h2>1. Testing Database Connection</h2>";
try {
    $pdo = getDatabaseConnection();
    echo "✅ Database connection successful<br>";
    
    // Get a test user
    $stmt = $pdo->prepare("SELECT userId, userName, email FROM user WHERE email LIKE 'pmkkhoaminh@gmail.com' LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ Found test user: {$user['userName']} ({$user['email']})<br>";
    } else {
        echo "❌ No users found with email addresses<br>";
        exit();
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    exit();
}

echo "<h2>2. Testing Gmail Configuration</h2>";

// Check if Gmail credentials are configured
$gmailFile = 'send_otp_email_gmail.php';
if (file_exists($gmailFile)) {
    $content = file_get_contents($gmailFile);
    
    if (strpos($content, 'your-email@gmail.com') !== false) {
        echo "⚠️ Gmail credentials not configured yet<br>";
        echo "<p>Please update the credentials in <code>send_otp_email_gmail.php</code>:</p>";
        echo "<ol>";
        echo "<li>Replace <code>your-email@gmail.com</code> with your actual Gmail</li>";
        echo "<li>Replace <code>your-16-char-app-password</code> with your Gmail App Password</li>";
        echo "</ol>";
        echo "<p>Then refresh this page to test email sending.</p>";
        exit();
    } else {
        echo "✅ Gmail credentials appear to be configured<br>";
    }
} else {
    echo "❌ Gmail email file not found<br>";
    exit();
}

echo "<h2>3. Testing Email Sending</h2>";

// Generate test OTP
$testOtp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
$testEmail = $user['email'];
$testUserName = $user['userName'];

echo "📧 Sending test email to: $testEmail<br>";
echo "🔢 Test OTP: $testOtp<br>";

try {
    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = process.env.VIEGRAND_EMAIL;
    $mail->Password = process.env.VIEGRAND_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';
    
    // Recipients
    $mail->setFrom(process.env.VIEGRAND_EMAIL, process.env.VIEGRAND_APP_NAME);
    $mail->addAddress($testEmail, $testUserName);
    $mail->addReplyTo('support@viegrandapp.com', 'VieGrand Support');
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email - VieGrand App OTP';
    $mail->Body = "
    <html>
    <body>
        <h2>Test Email from VieGrand App</h2>
        <p>This is a test email to verify Gmail SMTP configuration.</p>
        <p><strong>Test OTP: $testOtp</strong></p>
        <p>If you receive this email, the Gmail SMTP setup is working correctly!</p>
        <hr>
        <p><small>Sent from VieGrand App using Gmail SMTP</small></p>
    </body>
    </html>";
    
    $mail->AltBody = "
    Test Email from VieGrand App
    
    This is a test email to verify Gmail SMTP configuration.
    Test OTP: $testOtp
    
    If you receive this email, the Gmail SMTP setup is working correctly!
    
    Sent from VieGrand App using Gmail SMTP";
    
    // Send email
    $mail->send();
    echo "✅ Test email sent successfully!<br>";
    echo "<p>Please check your email inbox (and spam folder) for the test email.</p>";
    
} catch (Exception $e) {
    echo "❌ Email sending failed: " . $mail->ErrorInfo . "<br>";
    echo "<h3>Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Check your Gmail credentials</li>";
    echo "<li>Verify 2-Factor Authentication is enabled</li>";
    echo "<li>Confirm the App Password is correct</li>";
    echo "<li>Check your internet connection</li>";
    echo "</ul>";
}

echo "<h2>4. Next Steps</h2>";
echo "<p>If the test email was sent successfully:</p>";
echo "<ol>";
echo "<li>Test the password change functionality in the app</li>";
echo "<li>Go to Settings → Bảo mật</li>";
echo "<li>Enter your email address</li>";
echo "<li>Check your email for the OTP</li>";
echo "<li>Complete the password change process</li>";
echo "</ol>";

echo "<h2>5. Troubleshooting</h2>";
echo "<p>If you encounter issues:</p>";
echo "<ul>";
echo "<li><strong>Authentication failed:</strong> Check your Gmail App Password</li>";
echo "<li><strong>Connection failed:</strong> Check your internet connection</li>";
echo "<li><strong>Email not received:</strong> Check spam folder</li>";
echo "<li><strong>PHPMailer errors:</strong> Run <a href='test_phpmailer.php'>PHPMailer test</a></li>";
echo "</ul>";
?> 