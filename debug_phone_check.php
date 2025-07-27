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
    
    $userEmail = isset($data['email']) ? sanitizeInput($data['email']) : null;
    
    if (!$userEmail) {
        sendErrorResponse('Email is required', 'Bad request', 400);
        exit;
    }
    
    // Lấy tất cả user trong database
    $sql = "SELECT userId, userName, email, phone, relative_phone FROM user WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userEmail]);
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        sendErrorResponse('User not found', 'Not found', 404);
        exit;
    }
    
    // Lấy tất cả notifications cho phone này
    $notificationsSql = "SELECT id, type, title, body, user_phone, created_at FROM notifications WHERE user_phone = ? ORDER BY created_at DESC LIMIT 5";
    $notificationsStmt = $conn->prepare($notificationsSql);
    $notificationsStmt->execute([$user['phone']]);
    $notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy tất cả friend requests
    $friendRequestsSql = "SELECT * FROM friend_status WHERE user_phone = ? OR friend_phone = ? ORDER BY created_at DESC LIMIT 5";
    $friendRequestsStmt = $conn->prepare($friendRequestsSql);
    $friendRequestsStmt->execute([$user['phone'], $user['phone']]);
    $friendRequests = $friendRequestsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendSuccessResponse([
        'user' => $user,
        'notifications' => $notifications,
        'friendRequests' => $friendRequests
    ], 'Debug data retrieved successfully');
    
} catch (Exception $e) {
    error_log('Debug phone check error: ' . $e->getMessage());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?> 