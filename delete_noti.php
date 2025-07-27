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
    $userPhone = isset($data['user_phone']) ? sanitizeInput($data['user_phone']) : null;
    
    if (!$notificationIds || !is_array($notificationIds) || empty($notificationIds) || !$userPhone) {
        sendErrorResponse('An array of notification_ids and user_phone are required', 'Bad request', 400);
        exit;
    }
    
    // Log request để debug
    error_log('🔄 Delete notifications request: ' . json_encode([
        'user_phone' => $userPhone,
        'notification_ids' => $notificationIds,
        'count' => count($notificationIds)
    ]));
    
    // Đảm bảo tất cả ID đều là số nguyên để tránh SQL injection
    $sanitizedIds = array_map('intval', $notificationIds);
    $placeholders = implode(',', array_fill(0, count($sanitizedIds), '?'));
    
    // Xóa thông báo, chỉ cho phép user xóa thông báo của chính mình
    $sql = "DELETE FROM notifications 
            WHERE id IN ($placeholders) AND user_phone = ?";
            
    $stmt = $conn->prepare($sql);
    
    // Gắn các tham số
    $params = array_merge($sanitizedIds, [$userPhone]);
    $stmt->execute($params);
    
    $deletedRows = $stmt->rowCount();
    
    error_log('✅ Delete notifications result: ' . $deletedRows . ' rows deleted');
    
    if ($deletedRows > 0) {
        sendSuccessResponse(['deletedCount' => $deletedRows], "Đã xóa $deletedRows thông báo thành công");
    } else {
        // Có thể không tìm thấy ID nào khớp với user
        sendSuccessResponse(['deletedCount' => 0], 'Không tìm thấy thông báo để xóa');
    }
    
} catch (Exception $e) {
    error_log('❌ Delete notification error: ' . $e->getMessage());
    error_log('❌ Stack trace: ' . $e->getTraceAsString());
    sendErrorResponse('Lỗi server: ' . $e->getMessage(), 'Internal server error', 500);
}
?>
