<?php
require_once 'config.php';

// DEBUG: Log raw request data
error_log("=== GET_USER_BY_PRIVATE_KEY.PHP CALLED ===");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Raw input: " . file_get_contents('php://input'));

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("❌ Method not allowed: " . $_SERVER['REQUEST_METHOD']);
    sendErrorResponse('Method not allowed', 'Method not allowed', 405);
    exit;
}

try {
    $conn = getDatabaseConnection();
    error_log("✅ Database connection established");
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    error_log("Parsed input data: " . print_r($data, true));
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("❌ JSON decode error: " . json_last_error_msg());
        sendErrorResponse('Invalid JSON format', 'Bad request', 400);
        exit;
    }
    
    // Required parameters
    $privateKey = isset($data['private_key']) ? sanitizeInput($data['private_key']) : null;
    
    error_log("Parameters - privateKey: $privateKey");
    
    if (!$privateKey) {
        error_log("❌ Missing required parameters");
        sendErrorResponse('private_key is required', 'Bad request', 400);
        exit;
    }
    
    // Get user by private key
    $getUserSql = "SELECT userId, userName, email, phone, age, gender, role, private_key FROM user WHERE private_key = ?";
    $stmt = $conn->prepare($getUserSql);
    $stmt->execute([$privateKey]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        error_log("❌ User not found with private key: $privateKey");
        sendErrorResponse('User not found with this private key', 'Not found', 404);
        exit;
    }
    
    error_log("✅ User found: " . json_encode($user));
    
    $responseData = [
        'user' => $user
    ];
    
    error_log("✅ Get user by private key completed successfully");
    sendSuccessResponse($responseData, 'User data retrieved successfully');
    
} catch (Exception $e) {
    error_log("❌ Exception in get_user_by_private_key.php: " . $e->getMessage());
    sendErrorResponse('Internal server error', 'Internal server error', 500);
}
?>
