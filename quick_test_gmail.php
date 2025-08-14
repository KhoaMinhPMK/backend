<?php
echo "<h1>Quick Gmail SMTP Test</h1>";

// Check if PHPMailer is installed
if (!file_exists('PHPMailer/PHPMailer.php')) {
    echo "❌ PHPMailer not found. Please install it first:<br>";
    echo "<a href='install_phpmailer.php'>Install PHPMailer</a><br>";
    exit();
}

echo "✅ PHPMailer found<br>";

// Include PHPMailer
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

try {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = process.env.VIEGRAND_EMAIL;
    $mail->Password = process.env.VIEGRAND_PASSWORD;
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';
    
    // Recipients
    $mail->setFrom(process.env.VIEGRAND_EMAIL, process.env.VIEGRAND_APP_NAME);
    $mail->addAddress('pmkkhoaminh@gmail.com', 'Test User');
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Quick Gmail SMTP Test - VieGrand App';
    $mail->Body = "
    <html>
    <body>
        <h2>Quick Gmail SMTP Test</h2>
        <p>This is a quick test to verify Gmail SMTP is working correctly.</p>
        <p>If you receive this email, the Gmail SMTP setup is working!</p>
        <hr>
        <p><small>Sent from VieGrand App using Gmail SMTP</small></p>
    </body>
    </html>";
    
    $mail->AltBody = "
    Quick Gmail SMTP Test
    
    This is a quick test to verify Gmail SMTP is working correctly.
    If you receive this email, the Gmail SMTP setup is working!
    
    Sent from VieGrand App using Gmail SMTP";
    
    // Send email
    $mail->send();
    echo "✅ Test email sent successfully via Gmail SMTP!<br>";
    echo "<p>Please check your email inbox (and spam folder) for the test email.</p>";
    
} catch (\Exception $e) {
    echo "❌ Gmail SMTP test failed: " . $mail->ErrorInfo . "<br>";
    echo "<h3>Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Check your Gmail credentials</li>";
    echo "<li>Verify 2-Factor Authentication is enabled</li>";
    echo "<li>Confirm the App Password is correct</li>";
    echo "<li>Check your internet connection</li>";
    echo "</ul>";
}

echo "<h2>Next Steps</h2>";
echo "<p>If the test email was sent successfully:</p>";
echo "<ol>";
echo "<li>Test the complete OTP setup: <a href='test_otp_setup.php'>test_otp_setup.php</a></li>";
echo "<li>Test the password change functionality in the app</li>";
echo "</ol>";
?> 