<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

// Sử dụng functions từ config.php

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendErrorResponse('Method not allowed', 405);
}

try {
    error_log("🔍 persistent_get_messages.php - Request received");
    error_log("🔍 persistent_get_messages.php - GET params: " . json_encode($_GET));
    
    $conversationId = $_GET['conversation_id'] ?? '';
    $userPhone = $_GET['user_phone'] ?? '';
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    
    error_log("🔍 persistent_get_messages.php - Parsed params: conversationId=$conversationId, userPhone=$userPhone, limit=$limit, offset=$offset");
    
    if (empty($conversationId) || empty($userPhone)) {
        error_log("❌ persistent_get_messages.php - Missing required parameters");
        sendErrorResponse('Missing required parameters: conversation_id, user_phone');
    }
    
    $pdo = getDatabaseConnection();
    
    // Lấy tin nhắn từ conversation
    $stmt = $pdo->prepare("
        SELECT 
            m.id,
            m.conversation_id,
            m.sender_phone,
            m.receiver_phone,
            m.message_text,
            m.message_type,
            m.file_url,
            m.is_read,
            m.sent_at,
            m.read_at,
            u1.name as sender_name,
            u2.name as receiver_name
        FROM persistent_messages m
        LEFT JOIN user u1 ON m.sender_phone = u1.phone
        LEFT JOIN user u2 ON m.receiver_phone = u2.phone
        WHERE m.conversation_id = ?
        ORDER BY m.sent_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $stmt->bindParam(1, $conversationId, PDO::PARAM_STR);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->bindParam(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Xử lý dữ liệu
    foreach ($messages as &$message) {
        $message['is_own_message'] = $message['sender_phone'] === $userPhone;
        $message['sender_name'] = $message['sender_name'] ?: 'Người dùng';
        $message['receiver_name'] = $message['receiver_name'] ?: 'Người dùng';
        
        // Tạo avatar từ tên
        $message['sender_avatar'] = generateAvatarFromName($message['sender_name']);
        $message['receiver_avatar'] = generateAvatarFromName($message['receiver_name']);
    }
    
    // Đảo ngược thứ tự để tin nhắn cũ ở trên
    $messages = array_reverse($messages);
    
    sendSuccessResponse([
        'messages' => $messages,
        'total' => count($messages),
        'conversation_id' => $conversationId
    ], 'Messages retrieved successfully');
    
} catch (Exception $e) {
    error_log("Persistent get messages error: " . $e->getMessage());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}

function generateAvatarFromName($name) {
    // Tạo avatar từ tên (có thể dùng thư viện hoặc API)
    return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=random';
}
?> 