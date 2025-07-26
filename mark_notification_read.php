<?php
require_once 'config.php';

setCorsHeaders();

// Chỉ cho phép POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Method not allowed', 'Method not allowed', 405);
    exit;
}

try {
    $conn = getDatabaseConnection();
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse('Invalid JSON format', 'Bad request', 400);
        exit;
    }
    
    $notificationIds = isset($data['notification_ids']) ? $data['notification_ids'] : null;
    $userPhone = isset($data['user_phone']) ? sanitizeInput($data['user_phone']) : null; // Để bảo mật, chỉ cho phép user tự đánh dấu của mình
    
    if (!$notificationIds || !is_array($notificationIds) || empty($notificationIds) || !$userPhone) {
        sendErrorResponse('An array of notification_ids and user_phone are required', 'Bad request', 400);
        exit;
    }
    
    // Đảm bảo tất cả ID đều là số nguyên để tránh SQL injection
    $sanitizedIds = array_map('intval', $notificationIds);
    $placeholders = implode(',', array_fill(0, count($sanitizedIds), '?'));
    
    $sql = "UPDATE notifications 
            SET is_read = TRUE, read_at = NOW() 
            WHERE id IN ($placeholders) AND user_phone = ?";
            
    $stmt = $conn->prepare($sql);
    
    // Gắn các tham số
    $params = array_merge($sanitizedIds, [$userPhone]);
    $stmt->execute($params);
    
    $affectedRows = $stmt->rowCount();
    
    if ($affectedRows > 0) {
        sendSuccessResponse(['markedAsReadCount' => $affectedRows], 'Notifications marked as read');
    } else {
        // Có thể không tìm thấy ID nào khớp, hoặc chúng đã được đọc
        sendSuccessResponse(['markedAsReadCount' => 0], 'No matching unread notifications found to update');
    }
    
} catch (Exception $e) {
    error_log('Mark notification read error: ' . $e->getMessage());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?> 