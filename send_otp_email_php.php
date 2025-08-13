<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

require_once 'config.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $email = trim($input['email'] ?? '');
    
    // Validate email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT userId, name, email FROM user WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found with this email address');
    }
    
    // Generate 6-digit OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Store OTP in database with 5-minute expiration
    $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    // Delete any existing unused OTPs for this user
    $stmt = $pdo->prepare("DELETE FROM password_reset_otp WHERE user_id = ? AND used = 0");
    $stmt->execute([$user['userId']]);
    
    // Insert new OTP
    $stmt = $pdo->prepare("INSERT INTO password_reset_otp (user_id, otp, expires_at, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$user['userId'], $otp, $expiresAt]);
    
    // Send email using PHP mail()
    $emailSent = sendOTPEmailViaPHP($user['email'], $user['name'], $otp);
    
    if (!$emailSent) {
        // If email fails, delete the OTP
        $stmt = $pdo->prepare("DELETE FROM password_reset_otp WHERE user_id = ? AND otp = ?");
        $stmt->execute([$user['userId'], $otp]);
        throw new Exception('Failed to send email. Please try again later.');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'OTP sent successfully to your email'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Send OTP email using PHP mail() function
 */
function sendOTPEmailViaPHP($userEmail, $userName, $otp) {
    $subject = 'Mã OTP Đổi Mật Khẩu - VieGrand App';
    
    // Generate HTML email content
    $htmlContent = generatePasswordResetEmailHTML($userName, $otp);
    
    // Generate plain text content
    $textContent = generatePasswordResetEmailText($userName, $otp);
    
    // Email headers
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: VieGrand App <noreply@viegrandapp.com>',
        'Reply-To: support@viegrandapp.com',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    // Send email
    return mail($userEmail, $subject, $htmlContent, implode("\r\n", $headers));
}

/**
 * Generate HTML email template for password reset
 */
function generatePasswordResetEmailHTML($userName, $otp) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Mã OTP Đổi Mật Khẩu - VieGrand App</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background-color: #f4f4f4;
            }
            .container {
                background: white;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 30px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
                font-weight: 600;
            }
            .header p {
                margin: 10px 0 0 0;
                opacity: 0.9;
            }
            .content {
                padding: 30px;
            }
            .otp-code {
                background: #667eea;
                color: white;
                font-size: 32px;
                font-weight: bold;
                padding: 20px;
                text-align: center;
                border-radius: 8px;
                margin: 20px 0;
                letter-spacing: 5px;
            }
            .warning {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                color: #856404;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
            }
            .warning ul {
                margin: 10px 0;
                padding-left: 20px;
            }
            .warning li {
                margin: 5px 0;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                color: #666;
                font-size: 14px;
                padding: 20px;
                background-color: #f8f9fa;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🔐 Mã OTP Đổi Mật Khẩu</h1>
                <p>VieGrand App - Ứng dụng chăm sóc người cao tuổi</p>
            </div>
            
            <div class="content">
                <p>Xin chào <strong>' . htmlspecialchars($userName) . '</strong>,</p>
                
                <p>Bạn đã yêu cầu đổi mật khẩu cho tài khoản VieGrand App của mình.</p>
                
                <p>Mã OTP của bạn là:</p>
                
                <div class="otp-code">' . $otp . '</div>
                
                <div class="warning">
                    <strong>⚠️ Lưu ý quan trọng:</strong>
                    <ul>
                        <li>Mã OTP này chỉ có hiệu lực trong <strong>5 phút</strong></li>
                        <li>Không chia sẻ mã này với bất kỳ ai</li>
                        <li>Nếu bạn không yêu cầu đổi mật khẩu, vui lòng bỏ qua email này</li>
                    </ul>
                </div>
                
                <p>Nếu bạn gặp vấn đề, vui lòng liên hệ với chúng tôi.</p>
                
                <p>Trân trọng,<br>
                <strong>Đội ngũ VieGrand App</strong></p>
            </div>
            
            <div class="footer">
                <p>Email này được gửi tự động, vui lòng không trả lời.</p>
                <p>© 2024 VieGrand App. Tất cả quyền được bảo lưu.</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Generate plain text version
 */
function generatePasswordResetEmailText($userName, $otp) {
    return '
Mã OTP Đổi Mật Khẩu - VieGrand App

Xin chào ' . $userName . ',

Bạn đã yêu cầu đổi mật khẩu cho tài khoản VieGrand App của mình.

Mã OTP của bạn là: ' . $otp . '

⚠️ Lưu ý quan trọng:
- Mã OTP này chỉ có hiệu lực trong 5 phút
- Không chia sẻ mã này với bất kỳ ai
- Nếu bạn không yêu cầu đổi mật khẩu, vui lòng bỏ qua email này

Nếu bạn gặp vấn đề, vui lòng liên hệ với chúng tôi.

Trân trọng,
Đội ngũ VieGrand App

---
Email này được gửi tự động, vui lòng không trả lời.
© 2024 VieGrand App. Tất cả quyền được bảo lưu.';
}
?> 