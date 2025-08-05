<?php
// Bao gồm file config
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Chỉ cho phép POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Method not allowed', 405);
    exit;
}

try {
    // Lấy kết nối database
    $conn = getDatabaseConnection();
    
    // Lấy dữ liệu JSON từ request body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Debug: log input data
    error_log('Check unique code input: ' . $input);
    
    // Kiểm tra JSON có hợp lệ không
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse('Invalid JSON format');
        exit;
    }
    
    // Lấy private key từ request
    $privateKey = isset($data['uniqueCode']) ? sanitizeInput($data['uniqueCode']) : null;
    
    if (!$privateKey) {
        sendErrorResponse('Private key is required');
        exit;
    }
    
    // Kiểm tra xem private key đã tồn tại chưa
    $sql = "SELECT COUNT(*) as count FROM user WHERE private_key = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        sendErrorResponse('Database prepare error');
        exit;
    }
    
    $stmt->execute([$privateKey]);
    $result = $stmt->fetch();
    
    $exists = $result['count'] > 0;
    
    // Trả về kết quả
    $responseData = [
        'exists' => $exists,
        'privateKey' => $privateKey
    ];
    
    sendSuccessResponse($responseData, $exists ? 'Private key already exists' : 'Private key is available');
    
} catch (Exception $e) {
    sendErrorResponse('Server error: ' . $e->getMessage());
}
?> 