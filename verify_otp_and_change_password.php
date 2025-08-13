<?php
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Debug: Log the incoming request
error_log("🔍 verify_otp_and_change_password.php - Request received");
error_log("🔍 Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("🔍 Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));

// Get JSON input
$input = file_get_contents('php://input');
error_log("🔍 Raw input: " . $input);

// Also log to a file for easier debugging
$debugLog = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
    'raw_input' => $input,
    'headers' => getallheaders()
];
file_put_contents('debug_request.log', json_encode($debugLog, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Method not allowed', 'Method not allowed', 405);
    exit;
}

try {
    // Debug: Log the incoming request
    error_log("🔍 verify_otp_and_change_password.php - Request received");
    error_log("🔍 Request method: " . $_SERVER['REQUEST_METHOD']);
    error_log("🔍 Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
    
    // Get JSON input
    $input = file_get_contents('php://input');
    error_log("🔍 Raw input: " . $input);
    
    $data = json_decode($input, true);
    
    // Validate JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse('Invalid JSON format', 'Bad request', 400);
        exit;
    }
    
    // Extract required fields
    $email = isset($data['email']) ? sanitizeInput($data['email']) : null;
    $otp = isset($data['otp']) ? sanitizeInput($data['otp']) : null;
    $newPassword = isset($data['newPassword']) ? $data['newPassword'] : null;
    
    // Debug: Log extracted fields
    error_log("🔍 Extracted fields - Email: $email, OTP: $otp, Password length: " . strlen($newPassword));
    
    if (!$email || !$otp || !$newPassword) {
        sendErrorResponse('Email, OTP, and new password are required', 'Bad request', 400);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendErrorResponse('Invalid email format', 'Bad request', 400);
        exit;
    }
    
    // Validate password strength (minimum 6 characters)
    if (strlen($newPassword) < 6) {
        sendErrorResponse('Password must be at least 6 characters long', 'Bad request', 400);
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
    
    // Simple OTP verification - just check if OTP exists and matches
    $verifyOtpSql = "SELECT * FROM password_reset_otp WHERE user_id = ? AND otp = ? LIMIT 1";
    $verifyOtpStmt = $conn->prepare($verifyOtpSql);
    $verifyOtpStmt->execute([$user['userId'], $otp]);
    $otpRecord = $verifyOtpStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$otpRecord) {
        error_log("❌ OTP not found - Email: $email, OTP: $otp");
        sendErrorResponse('OTP không tồn tại', 'Unauthorized', 401);
        exit;
    }
    
    // Check if OTP is already used
    if ($otpRecord['used'] == 1) {
        error_log("❌ OTP already used - Email: $email, OTP: $otp");
        sendErrorResponse('OTP đã được sử dụng', 'Unauthorized', 401);
        exit;
    }
    
    // Check if OTP is expired
    $now = new DateTime();
    $expires = new DateTime($otpRecord['expires_at']);
    if ($now > $expires) {
        error_log("❌ OTP expired - Email: $email, OTP: $otp, Expired: {$otpRecord['expires_at']}");
        sendErrorResponse('OTP đã hết hạn', 'Unauthorized', 401);
        exit;
    }
    
    error_log("✅ OTP verification successful - Email: $email, OTP: $otp");
    
    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    error_log("✅ Password hashed successfully for user: {$user['userId']}");
    
    // Update user password
    $updatePasswordSql = "UPDATE user SET password = ?, updated_at = NOW() WHERE userId = ?";
    $updatePasswordStmt = $conn->prepare($updatePasswordSql);
    $updatePasswordResult = $updatePasswordStmt->execute([$hashedPassword, $user['userId']]);
    
    if (!$updatePasswordResult) {
        error_log("❌ Failed to update password for user: {$user['userId']}");
        sendErrorResponse('Không thể cập nhật mật khẩu', 'Internal server error', 500);
        exit;
    }
    
    error_log("✅ Password updated successfully for user: {$user['userId']}");
    
    // Mark OTP as used
    $markOtpUsedSql = "UPDATE password_reset_otp SET used = 1, used_at = NOW() WHERE id = ?";
    $markOtpUsedStmt = $conn->prepare($markOtpUsedSql);
    $markOtpUsedStmt->execute([$otpRecord['id']]);
    
    // Delete all other unused OTPs for this user
    $deleteOtherOtpsSql = "DELETE FROM password_reset_otp WHERE user_id = ? AND used = 0";
    $deleteOtherOtpsStmt = $conn->prepare($deleteOtherOtpsSql);
    $deleteOtherOtpsStmt->execute([$user['userId']]);
    
    // Log the password change
    error_log("✅ Password changed successfully for user: $email (User ID: {$user['userId']})");
    
    sendSuccessResponse([
        'message' => 'Password changed successfully',
        'user' => [
            'userId' => (int)$user['userId'],
            'userName' => $user['userName'],
            'email' => $email
        ]
    ], 'Password changed successfully');
    
} catch (Exception $e) {
    error_log("❌ Error in verify_otp_and_change_password.php: " . $e->getMessage());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?> 