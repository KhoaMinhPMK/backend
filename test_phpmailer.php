<?php
echo "<h1>PHPMailer Installation Test</h1>";

// Test 1: Check if PHPMailer directory exists
echo "<h2>1. Checking PHPMailer Directory</h2>";
$phpmailerDir = 'PHPMailer';
if (is_dir($phpmailerDir)) {
    echo "✅ PHPMailer directory exists<br>";
} else {
    echo "❌ PHPMailer directory not found<br>";
    echo "<p>Please run: <a href='install_phpmailer.php'>install_phpmailer.php</a></p>";
    exit();
}

// Test 2: Check if required files exist
echo "<h2>2. Checking Required Files</h2>";
$requiredFiles = [
    'PHPMailer.php',
    'SMTP.php',
    'Exception.php'
];

foreach ($requiredFiles as $file) {
    $filepath = $phpmailerDir . '/' . $file;
    if (file_exists($filepath)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file not found<br>";
    }
}

// Test 3: Try to include PHPMailer
echo "<h2>3. Testing PHPMailer Inclusion</h2>";
try {
    require_once 'PHPMailer/PHPMailer.php';
    require_once 'PHPMailer/SMTP.php';
    require_once 'PHPMailer/Exception.php';
    
    // Use statements need to be at the top level, so we'll test without them
    // In the actual implementation, these will be at the top of the file
    
    echo "✅ PHPMailer classes loaded successfully<br>";
    
    // Test 4: Create PHPMailer instance
    echo "<h2>4. Testing PHPMailer Instance</h2>";
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    echo "✅ PHPMailer instance created successfully<br>";
    
    // Test 5: Check SMTP settings
    echo "<h2>5. SMTP Configuration Test</h2>";
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Port = 587;
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    
    echo "✅ SMTP configuration set successfully<br>";
    echo "Host: smtp.gmail.com<br>";
    echo "Port: 587<br>";
    echo "Security: STARTTLS<br>";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<h2>Next Steps</h2>";
echo "<p>If all tests pass, you can now:</p>";
echo "<ol>";
echo "<li>Configure your Gmail credentials in <code>send_otp_email_gmail.php</code></li>";
echo "<li>Test email sending: <a href='test_gmail_email.php'>test_gmail_email.php</a></li>";
echo "<li>Test the password change functionality in the app</li>";
echo "</ol>";
?> 