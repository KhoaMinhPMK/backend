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
    
    if (empty($userPhone)) {
        sendErrorResponse('Missing required parameter: user_phone');
    }
    
    $pdo = getConnection();
    
    // Lấy conversations của user
    $stmt = $pdo->prepare("
        SELECT 
            c.id as conversation_id,
            c.participant1_phone,
            c.participant2_phone,
            c.last_activity,
            c.created_at,
            m.message_text as last_message,
            m.sent_at as last_message_time,
            m.sender_phone as last_message_sender,
            CASE 
                WHEN c.participant1_phone = ? THEN c.participant2_phone
                ELSE c.participant1_phone
            END as other_participant_phone,
            u.name as other_participant_name,
            (SELECT COUNT(*) FROM persistent_messages 
             WHERE conversation_id = c.id 
             AND receiver_phone = ? 
             AND is_read = 0) as unread_count
        FROM persistent_conversations c
        LEFT JOIN persistent_messages m ON c.last_message_id = m.id
        LEFT JOIN user u ON (
            CASE 
                WHEN c.participant1_phone = ? THEN c.participant2_phone
                ELSE c.participant1_phone
            END = u.phone
        )
        WHERE c.participant1_phone = ? OR c.participant2_phone = ?
        ORDER BY c.last_activity DESC
    ");
    
    $stmt->execute([$userPhone, $userPhone, $userPhone, $userPhone, $userPhone]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Xử lý dữ liệu
    foreach ($conversations as &$conversation) {
        $conversation['other_participant_name'] = $conversation['other_participant_name'] ?: 'Người dùng';
        $conversation['other_participant_avatar'] = generateAvatarFromName($conversation['other_participant_name']);
        $conversation['last_message'] = $conversation['last_message'] ?: 'Chưa có tin nhắn';
        $conversation['unread_count'] = (int)$conversation['unread_count'];
        
        // Format thời gian
        if ($conversation['last_message_time']) {
            $conversation['last_message_time_formatted'] = formatTimeAgo($conversation['last_message_time']);
        } else {
            $conversation['last_message_time_formatted'] = '';
        }
    }
    
    sendSuccessResponse([
        'conversations' => $conversations,
        'total' => count($conversations)
    ], 'Conversations retrieved successfully');
    
} catch (Exception $e) {
    error_log("Persistent get conversations error: " . $e->getMessage());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}

function generateAvatarFromName($name) {
    return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=random';
}

function formatTimeAgo($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Vừa xong';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' phút trước';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' giờ trước';
    } else {
        return date('d/m/Y', $time);
    }
}
?> 