<?php
// Bao gồm file config
require_once 'config.php';

// DEBUG: Log raw request data
error_log("=== GET_USER_PHONE.PHP CALLED ===");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
error_log("Raw POST data: " . print_r($_POST, true));
error_log("Raw input: " . file_get_contents('php://input'));

// Set CORS headers
setCorsHeaders();

// Chỉ cho phép POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("❌ Method not allowed: " . $_SERVER['REQUEST_METHOD']);
    sendErrorResponse('Method not allowed', 'Method not allowed', 405);
    exit;
}

try {
    // Lấy kết nối database
    $conn = getDatabaseConnection();
    error_log("✅ Database connection established");
    
    // Lấy dữ liệu JSON từ request body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    error_log("Parsed input data: " . print_r($data, true));
    
    // Kiểm tra JSON có hợp lệ không
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("❌ JSON decode error: " . json_last_error_msg());
        sendErrorResponse('Invalid JSON format', 'Bad request', 400);
        exit;
    }
    
    // Email là bắt buộc để lấy số điện thoại
    $email = isset($data['email']) ? sanitizeInput($data['email']) : null;
    
    error_log("Email parameter: " . ($email ?? 'NULL'));
    
    if (!$email) {
        error_log("❌ Missing email parameter");
        sendErrorResponse('Email is required to get user phone', 'Bad request', 400);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log("❌ Invalid email format: " . $email);
        sendErrorResponse('Invalid email format', 'Bad request', 400);
        exit;
    }
    
    error_log("✅ Looking up phone for email: " . $email);
    
    // Tìm user theo email - chỉ lấy phone
    $sql = "SELECT phone, userName, email FROM user WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("❌ Database prepare error");
        sendErrorResponse('Database prepare error', 'Internal server error', 500);
        exit;
    }
    
    error_log("✅ SQL prepared, executing with email: " . $email);
    
    // Execute với parameter
    $result = $stmt->execute([$email]);
    
    if ($result) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("Database query result: " . print_r($user, true));
        
        if ($user) {
            error_log("✅ User found with data: " . json_encode($user));
            
            if ($user['phone']) {
                // User tồn tại và có số điện thoại
                $responseData = [
                    'phone' => $user['phone'],
                    'userName' => $user['userName'],
                    'email' => $user['email']
                ];
                
                error_log("✅ Found phone for user: " . $user['phone']);
                error_log("Response data: " . json_encode($responseData));
                sendSuccessResponse($responseData, 'User phone retrieved successfully');
                
            } else {
                error_log("❌ User found but phone is NULL/empty");
                error_log("User data details: email=" . $user['email'] . ", userName=" . $user['userName'] . ", phone=" . ($user['phone'] ?? 'NULL'));
                sendErrorResponse('User has no phone number', 'Not found', 404);
            }
        } else {
            error_log("❌ User not found with email: " . $email);
            sendErrorResponse('User not found with this email', 'Not found', 404);
        }
    } else {
        error_log("❌ Database query failed");
        $errorInfo = $stmt->errorInfo();
        error_log("PDO Error Info: " . print_r($errorInfo, true));
        sendErrorResponse('Failed to query database', 'Database error', 500);
    }
    
} catch (Exception $e) {
    error_log('❌ Get user phone error: ' . $e->getMessage());
    error_log('❌ Stack trace: ' . $e->getTraceAsString());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?> 