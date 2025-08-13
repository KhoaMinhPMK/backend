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
    // Get database connection
    $pdo = getDatabaseConnection();
    
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
    $stmt = $pdo->prepare("SELECT userId, userName, email FROM user WHERE email = ?");
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
    
    // For development/testing, we'll return the OTP in the response
    // In production, you would send this via email
    echo json_encode([
        'success' => true,
        'message' => 'OTP generated successfully',
        'otp' => $otp, // Remove this in production
        'expires_at' => $expiresAt,
        'note' => 'In production, this OTP would be sent via email. For testing, it is returned in the response.'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 