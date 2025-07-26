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
    
    $userPhone = isset($data['user_phone']) ? sanitizeInput($data['user_phone']) : null;
    
    if (!$userPhone) {
        sendErrorResponse('User phone number is required', 'Bad request', 400);
        exit;
    }
    
    // Lấy thông báo cho người dùng, sắp xếp theo thời gian mới nhất
    // Có thể thêm phân trang (pagination) ở đây trong tương lai
    $sql = "SELECT id, type, title, body, data, is_read, created_at, read_at 
            FROM notifications 
            WHERE user_phone = ? 
            ORDER BY created_at DESC
            LIMIT 100"; // Giới hạn 100 thông báo gần nhất
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userPhone]);
    
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Chuyển đổi các trường cho client
    $formattedNotifications = array_map(function($notif) {
        return [
            'id' => (int)$notif['id'],
            'type' => $notif['type'],
            'title' => $notif['title'],
            'body' => $notif['body'],
            'data' => json_decode($notif['data'], true), // Decode JSON string thành object
            'isRead' => (bool)$notif['is_read'],
            'createdAt' => $notif['created_at'],
            'readAt' => $notif['read_at'],
        ];
    }, $notifications);
    
    sendSuccessResponse(['notifications' => $formattedNotifications], 'Notifications retrieved successfully');
    
} catch (Exception $e) {
    error_log('Get notifications error: ' . $e->getMessage());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?> 