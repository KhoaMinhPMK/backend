<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

// Sử dụng functions từ config.php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Method not allowed', 405);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendErrorResponse('Invalid JSON input');
    }
    
    $conversationId = trim($input['conversation_id'] ?? '');
    $userPhone = trim($input['user_phone'] ?? '');
    $messageIds = $input['message_ids'] ?? []; // Array of message IDs to mark as read
    
    if (empty($conversationId) || empty($userPhone)) {
        sendErrorResponse('Missing required fields: conversation_id, user_phone');
    }
    
    $pdo = getConnection();
    
    if (!empty($messageIds)) {
        // Đánh dấu specific messages
        $placeholders = str_repeat('?,', count($messageIds) - 1) . '?';
        $stmt = $pdo->prepare("
            UPDATE persistent_messages 
            SET is_read = 1, read_at = CURRENT_TIMESTAMP 
            WHERE id IN ($placeholders) 
            AND receiver_phone = ?
        ");
        
        $params = array_merge($messageIds, [$userPhone]);
        $stmt->execute($params);
        
        $affectedRows = $stmt->rowCount();
        
        sendSuccessResponse([
            'marked_count' => $affectedRows,
            'conversation_id' => $conversationId
        ], "Marked $affectedRows messages as read");
        
    } else {
        // Đánh dấu tất cả tin nhắn trong conversation
        $stmt = $pdo->prepare("
            UPDATE persistent_messages 
            SET is_read = 1, read_at = CURRENT_TIMESTAMP 
            WHERE conversation_id = ? 
            AND receiver_phone = ? 
            AND is_read = 0
        ");
        
        $stmt->execute([$conversationId, $userPhone]);
        $affectedRows = $stmt->rowCount();
        
        sendSuccessResponse([
            'marked_count' => $affectedRows,
            'conversation_id' => $conversationId
        ], "Marked $affectedRows messages as read");
    }
    
} catch (Exception $e) {
    error_log("Persistent mark read error: " . $e->getMessage());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}
?> 