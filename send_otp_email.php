<?php
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Method not allowed', 'Method not allowed', 405);
    exit;
}

// SendGrid configuration
define('SENDGRID_API_KEY', 'YOUR_SENDGRID_API_KEY'); // Replace with your SendGrid API key
define('SENDGRID_FROM_EMAIL', 'noreply@viegrand.com'); // Replace with your verified sender email
define('SENDGRID_FROM_NAME', 'VieGrand App');

/**
 * Send OTP email using SendGrid
 */
function sendOTPEmail($userEmail, $userName, $otp) {
    try {
        // Prepare email content
        $subject = 'Mã xác thực đổi mật khẩu - VieGrand App';
        $htmlContent = generateOTPEmailHTML($userName, $otp);
        $textContent = generateOTPEmailText($userName, $otp);
        
        // Prepare SendGrid payload
        $data = [
            'personalizations' => [
                [
                    'to' => [
                        [
                            'email' => $userEmail,
                            'name' => $userName
                        ]
                    ],
                    'subject' => $subject
                ]
            ],
            'from' => [
                'email' => SENDGRID_FROM_EMAIL,
                'name' => SENDGRID_FROM_NAME
            ],
            'content' => [
                [
                    'type' => 'text/html',
                    'value' => $htmlContent
                ],
                [
                    'type' => 'text/plain',
                    'value' => $textContent
                ]
            ]
        ];
        
        // Send via SendGrid API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . SENDGRID_API_KEY,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log("❌ cURL Error sending OTP email: $curlError");
            return [
                'success' => false,
                'message' => 'Failed to send email: ' . $curlError
            ];
        }
        
        if ($httpCode === 202) {
            error_log("✅ OTP email sent successfully to: $userEmail");
            return [
                'success' => true,
                'message' => 'OTP email sent successfully'
            ];
        } else {
            error_log("❌ SendGrid API error: HTTP $httpCode, Response: $response");
            return [
                'success' => false,
                'message' => 'Failed to send email: HTTP ' . $httpCode
            ];
        }
        
    } catch (Exception $e) {
        error_log("❌ Exception sending OTP email: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to send email: ' . $e->getMessage()
        ];
    }
}

/**
 * Generate HTML email content
 */
function generateOTPEmailHTML($userName, $otp) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Mã xác thực đổi mật khẩu</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #007AFF, #0D4C92); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .otp-box { background: white; border: 2px solid #007AFF; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
            .otp-code { font-size: 32px; font-weight: bold; color: #007AFF; letter-spacing: 5px; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🔐 Mã xác thực đổi mật khẩu</h1>
                <p>VieGrand App - Bảo mật tài khoản của bạn</p>
            </div>
            <div class="content">
                <p>Xin chào <strong>' . htmlspecialchars($userName) . '</strong>,</p>
                
                <p>Chúng tôi nhận được yêu cầu đổi mật khẩu cho tài khoản VieGrand của bạn.</p>
                
                <div class="otp-box">
                    <p><strong>Mã xác thực của bạn:</strong></p>
                    <div class="otp-code">' . $otp . '</div>
                    <p><small>Mã này có hiệu lực trong 5 phút</small></p>
                </div>
                
                <div class="warning">
                    <p><strong>⚠️ Lưu ý quan trọng:</strong></p>
                    <ul>
                        <li>Không chia sẻ mã này với bất kỳ ai</li>
                        <li>Mã chỉ có hiệu lực trong 5 phút</li>
                        <li>Nếu bạn không yêu cầu đổi mật khẩu, vui lòng bỏ qua email này</li>
                    </ul>
                </div>
                
                <p>Nếu bạn gặp vấn đề, vui lòng liên hệ với chúng tôi.</p>
                
                <p>Trân trọng,<br><strong>Đội ngũ VieGrand</strong></p>
            </div>
            <div class="footer">
                <p>Email này được gửi tự động, vui lòng không trả lời.</p>
                <p>&copy; 2024 VieGrand App. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Generate plain text email content
 */
function generateOTPEmailText($userName, $otp) {
    return "Mã xác thực đổi mật khẩu - VieGrand App

Xin chào $userName,

Chúng tôi nhận được yêu cầu đổi mật khẩu cho tài khoản VieGrand của bạn.

Mã xác thực của bạn: $otp
(Mã này có hiệu lực trong 5 phút)

Lưu ý quan trọng:
- Không chia sẻ mã này với bất kỳ ai
- Mã chỉ có hiệu lực trong 5 phút
- Nếu bạn không yêu cầu đổi mật khẩu, vui lòng bỏ qua email này

Nếu bạn gặp vấn đề, vui lòng liên hệ với chúng tôi.

Trân trọng,
Đội ngũ VieGrand

---
Email này được gửi tự động, vui lòng không trả lời.
© 2024 VieGrand App. All rights reserved.";
}

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Validate JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse('Invalid JSON format', 'Bad request', 400);
        exit;
    }
    
    // Extract required fields
    $email = isset($data['email']) ? sanitizeInput($data['email']) : null;
    
    if (!$email) {
        sendErrorResponse('Email is required', 'Bad request', 400);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendErrorResponse('Invalid email format', 'Bad request', 400);
        exit;
    }
    
    // Get database connection
    $conn = getDatabaseConnection();
    
    // Check if user exists
    $checkUserSql = "SELECT userId, userName FROM user WHERE email = ? LIMIT 1";
    $checkUserStmt = $conn->prepare($checkUserSql);
    $checkUserStmt->execute([$email]);
    $user = $checkUserStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        sendErrorResponse('User not found with this email', 'Not found', 404);
        exit;
    }
    
    // Generate 6-digit OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Store OTP in database with expiration (5 minutes)
    $expiryTime = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    // First, delete any existing OTP for this user
    $deleteOtpSql = "DELETE FROM password_reset_otp WHERE user_id = ?";
    $deleteOtpStmt = $conn->prepare($deleteOtpSql);
    $deleteOtpStmt->execute([$user['userId']]);
    
    // Insert new OTP
    $insertOtpSql = "INSERT INTO password_reset_otp (user_id, otp, expires_at, created_at) VALUES (?, ?, ?, NOW())";
    $insertOtpStmt = $conn->prepare($insertOtpSql);
    $insertOtpResult = $insertOtpStmt->execute([$user['userId'], $otp, $expiryTime]);
    
    if (!$insertOtpResult) {
        sendErrorResponse('Failed to store OTP', 'Internal server error', 500);
        exit;
    }
    
    // Send OTP email
    $emailResult = sendOTPEmail($email, $user['userName'], $otp);
    
    if ($emailResult['success']) {
        sendSuccessResponse([
            'message' => 'OTP sent successfully',
            'expires_in' => 300 // 5 minutes in seconds
        ], 'OTP sent to your email address');
    } else {
        // If email fails, delete the OTP
        $deleteOtpStmt->execute([$user['userId']]);
        sendErrorResponse($emailResult['message'], 'Email sending failed', 500);
    }
    
} catch (Exception $e) {
    error_log("❌ Error in send_otp_email.php: " . $e->getMessage());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?> 