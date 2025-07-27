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
    $userPhone = $_GET['user_phone'] ?? '';
    $conversationId = $_GET['conversation_id'] ?? ''; // Optional: specific conversation
    
    if (empty($userPhone)) {
        sendErrorResponse('Missing required parameter: user_phone');
    }
    
    $pdo = getConnection();
    
    if (!empty($conversationId)) {
        // Lấy unread count cho specific conversation
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as unread_count
            FROM persistent_messages 
            WHERE conversation_id = ? 
            AND receiver_phone = ? 
            AND is_read = 0
        ");
        $stmt->execute([$conversationId, $userPhone]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        sendSuccessResponse([
            'unread_count' => (int)$result['unread_count'],
            'conversation_id' => $conversationId
        ], 'Unread count retrieved successfully');
        
    } else {
        // Lấy tổng unread count cho tất cả conversations
        $stmt = $pdo->prepare("
            SELECT 
                c.id as conversation_id,
                COUNT(m.id) as unread_count,
                u.name as sender_name
            FROM persistent_conversations c
            LEFT JOIN persistent_messages m ON c.id = m.conversation_id 
                AND m.receiver_phone = ? 
                AND m.is_read = 0
            LEFT JOIN user u ON (
                CASE 
                    WHEN c.participant1_phone = ? THEN c.participant2_phone
                    ELSE c.participant1_phone
                END = u.phone
            )
            WHERE (c.participant1_phone = ? OR c.participant2_phone = ?)
            GROUP BY c.id
            HAVING unread_count > 0
            ORDER BY c.last_activity DESC
        ");
        
        $stmt->execute([$userPhone, $userPhone, $userPhone, $userPhone]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $totalUnread = 0;
        $conversationUnread = [];
        
        foreach ($results as $row) {
            $totalUnread += (int)$row['unread_count'];
            $conversationUnread[] = [
                'conversation_id' => $row['conversation_id'],
                'unread_count' => (int)$row['unread_count'],
                'sender_name' => $row['sender_name'] ?: 'Người dùng'
            ];
        }
        
        sendSuccessResponse([
            'total_unread' => $totalUnread,
            'conversations' => $conversationUnread
        ], 'Unread counts retrieved successfully');
    }
    
} catch (Exception $e) {
    error_log("Persistent get unread count error: " . $e->getMessage());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}
?> 