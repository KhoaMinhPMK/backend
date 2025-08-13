<?php
/**
 * SendGrid Setup Script for VieGrand App
 * 
 * This script helps you set up SendGrid for sending OTP emails.
 * 
 * Prerequisites:
 * 1. Create a SendGrid account at https://sendgrid.com/
 * 2. Get your API key from SendGrid dashboard
 * 3. Verify your sender email address
 */

echo "=== SendGrid Setup for VieGrand App ===\n\n";

// Configuration
$configFile = 'send_otp_email.php';
$apiKey = '';
$fromEmail = '';
$fromName = 'VieGrand App';

echo "Please provide the following information:\n\n";

// Get API Key
echo "1. SendGrid API Key: ";
$handle = fopen("php://stdin", "r");
$apiKey = trim(fgets($handle));
fclose($handle);

if (empty($apiKey)) {
    echo "❌ API Key is required!\n";
    exit(1);
}

// Get From Email
echo "2. Verified Sender Email (must be verified in SendGrid): ";
$handle = fopen("php://stdin", "r");
$fromEmail = trim(fgets($handle));
fclose($handle);

if (empty($fromEmail) || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
    echo "❌ Valid email address is required!\n";
    exit(1);
}

// Get From Name (optional)
echo "3. Sender Name (optional, press Enter for default): ";
$handle = fopen("php://stdin", "r");
$fromNameInput = trim(fgets($handle));
fclose($handle);

if (!empty($fromNameInput)) {
    $fromName = $fromNameInput;
}

echo "\n=== Configuration Summary ===\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "From Email: $fromEmail\n";
echo "From Name: $fromName\n\n";

// Test SendGrid connection
echo "Testing SendGrid connection...\n";

$testData = [
    'personalizations' => [
        [
            'to' => [
                [
                    'email' => $fromEmail,
                    'name' => 'Test User'
                ]
            ],
            'subject' => 'SendGrid Test - VieGrand App'
        ]
    ],
    'from' => [
        'email' => $fromEmail,
        'name' => $fromName
    ],
    'content' => [
        [
            'type' => 'text/plain',
            'value' => 'This is a test email from VieGrand App to verify SendGrid configuration.'
        ]
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo "❌ cURL Error: $curlError\n";
    exit(1);
}

if ($httpCode === 202) {
    echo "✅ SendGrid connection successful!\n";
    echo "📧 Test email sent to: $fromEmail\n\n";
} else {
    echo "❌ SendGrid API Error: HTTP $httpCode\n";
    echo "Response: $response\n";
    exit(1);
}

// Update the configuration file
echo "Updating configuration file...\n";

$configContent = file_get_contents($configFile);
if ($configContent === false) {
    echo "❌ Could not read $configFile\n";
    exit(1);
}

// Replace the configuration values
$configContent = preg_replace(
    "/define\('SENDGRID_API_KEY', '.*?'\);/",
    "define('SENDGRID_API_KEY', '$apiKey');",
    $configContent
);

$configContent = preg_replace(
    "/define\('SENDGRID_FROM_EMAIL', '.*?'\);/",
    "define('SENDGRID_FROM_EMAIL', '$fromEmail');",
    $configContent
);

$configContent = preg_replace(
    "/define\('SENDGRID_FROM_NAME', '.*?'\);/",
    "define('SENDGRID_FROM_NAME', '$fromName');",
    $configContent
);

if (file_put_contents($configFile, $configContent) === false) {
    echo "❌ Could not write to $configFile\n";
    exit(1);
}

echo "✅ Configuration updated successfully!\n\n";

// Create database table
echo "Creating database table...\n";

require_once 'config.php';

try {
    $conn = getDatabaseConnection();
    
    $createTableSql = "
    CREATE TABLE IF NOT EXISTS `password_reset_otp` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `otp` varchar(6) NOT NULL,
      `expires_at` datetime NOT NULL,
      `used` tinyint(1) NOT NULL DEFAULT 0,
      `used_at` datetime NULL,
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_user_id` (`user_id`),
      KEY `idx_otp` (`otp`),
      KEY `idx_expires_at` (`expires_at`),
      KEY `idx_used` (`used`),
      CONSTRAINT `fk_password_reset_otp_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`userId`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $conn->exec($createTableSql);
    
    // Create index for better performance
    $indexSql = "CREATE INDEX IF NOT EXISTS `idx_user_otp_expires` ON `password_reset_otp` (`user_id`, `otp`, `expires_at`);";
    $conn->exec($indexSql);
    
    echo "✅ Database table created successfully!\n\n";
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "=== Setup Complete! ===\n\n";
echo "✅ SendGrid API configured\n";
echo "✅ Database table created\n";
echo "✅ Test email sent successfully\n\n";
echo "You can now use the password change functionality in the app.\n";
echo "Users will receive OTP emails when they request password changes.\n\n";
echo "Next steps:\n";
echo "1. Test the password change flow in the app\n";
echo "2. Monitor email delivery in SendGrid dashboard\n";
echo "3. Set up email templates if needed\n";
?> 