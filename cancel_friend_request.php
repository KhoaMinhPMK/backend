<?php
// Bao gồm file config
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Chỉ cho phép POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Method not allowed', 'Method not allowed', 405);
    exit;
}

try {
    // Lấy kết nối database
    $conn = getDatabaseConnection();
    
    // Lấy dữ liệu JSON từ request body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Debug: log input data
    error_log('Cancel friend request input: ' . $input);
    
    // Kiểm tra JSON có hợp lệ không
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse('Invalid JSON format', 'Bad request', 400);
        exit;
    }
    
    // Lấy parameters
    $fromPhone = isset($data['from_phone']) ? sanitizeInput($data['from_phone']) : null;
    $toPhone = isset($data['to_phone']) ? sanitizeInput($data['to_phone']) : null;
    $requestId = isset($data['request_id']) ? (int)$data['request_id'] : null;
    
    if (!$fromPhone || !$toPhone) {
        sendErrorResponse('Both from_phone and to_phone are required', 'Bad request', 400);
        exit;
    }
    
    // Validate phone format
    $fromPhone = preg_replace('/[^0-9+]/', '', $fromPhone);
    $toPhone = preg_replace('/[^0-9+]/', '', $toPhone);
    
    if (strlen($fromPhone) < 10 || strlen($toPhone) < 10) {
        sendErrorResponse('Invalid phone number format', 'Bad request', 400);
        exit;
    }
    
    // Tìm và xóa friend request
    if ($requestId) {
        // Xóa theo ID (nếu có)
        $deleteSql = "DELETE FROM friend_requests WHERE id = ? AND from_phone = ? AND to_phone = ? AND status = 'pending'";
        $deleteStmt = $conn->prepare($deleteSql);
        $result = $deleteStmt->execute([$requestId, $fromPhone, $toPhone]);
    } else {
        // Xóa theo phone numbers
        $deleteSql = "DELETE FROM friend_requests WHERE from_phone = ? AND to_phone = ? AND status = 'pending'";
        $deleteStmt = $conn->prepare($deleteSql);
        $result = $deleteStmt->execute([$fromPhone, $toPhone]);
    }
    
    if (!$deleteStmt) {
        sendErrorResponse('Database prepare error', 'Internal server error', 500);
        exit;
    }
    
    if ($result && $deleteStmt->rowCount() > 0) {
        // Lấy thông tin người nhận để trả về
        $getUserSql = "SELECT userId, userName FROM user WHERE phone = ? LIMIT 1";
        $getUserStmt = $conn->prepare($getUserSql);
        $getUserStmt->execute([$toPhone]);
        $toUser = $getUserStmt->fetch(PDO::FETCH_ASSOC);
        
        $responseData = [
            'from_phone' => $fromPhone,
            'to_phone' => $toPhone,
            'to_name' => $toUser['userName'] ?? 'Unknown',
            'cancelled_at' => date('Y-m-d H:i:s')
        ];
        
        // Debug: log response data
        error_log('Cancel friend request response: ' . json_encode($responseData));
        
        sendSuccessResponse($responseData, 'Friend request cancelled successfully');
        
    } else {
        sendErrorResponse('Friend request not found or already processed', 'Not found', 404);
    }
    
} catch (Exception $e) {
    error_log('Cancel friend request error: ' . $e->getMessage());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?> 