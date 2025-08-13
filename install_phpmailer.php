<?php
echo "<h1>PHPMailer Installation</h1>";

// Create PHPMailer directory
$phpmailerDir = 'PHPMailer';
if (!is_dir($phpmailerDir)) {
    mkdir($phpmailerDir, 0755, true);
    echo "✅ Created PHPMailer directory<br>";
} else {
    echo "✅ PHPMailer directory already exists<br>";
}

// Download PHPMailer files
$files = [
    'PHPMailer.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/PHPMailer.php',
    'SMTP.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/SMTP.php',
    'Exception.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/Exception.php'
];

foreach ($files as $filename => $url) {
    $filepath = $phpmailerDir . '/' . $filename;
    
    if (!file_exists($filepath)) {
        echo "📥 Downloading $filename...<br>";
        $content = file_get_contents($url);
        
        if ($content !== false) {
            file_put_contents($filepath, $content);
            echo "✅ Downloaded $filename successfully<br>";
        } else {
            echo "❌ Failed to download $filename<br>";
        }
    } else {
        echo "✅ $filename already exists<br>";
    }
}

echo "<h2>Installation Complete!</h2>";
echo "<p>PHPMailer has been installed. You can now use Gmail SMTP for sending emails.</p>";
echo "<p>Next steps:</p>";
echo "<ol>";
echo "<li>Configure your Gmail App Password</li>";
echo "<li>Update the email configuration in send_otp_email_gmail.php</li>";
echo "<li>Test the email functionality</li>";
echo "</ol>";
?> 