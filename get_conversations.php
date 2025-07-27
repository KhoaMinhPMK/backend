<?php
require_once 'config.php';
setCorsHeaders();

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
        sendErrorResponse('User phone is required', 'Bad request', 400);
        exit;
    }
    
    // Lấy tất cả conversations mà user tham gia
    $sql = "
        SELECT 
            c.id,
            c.participant1_phone,
            c.participant2_phone,
            c.last_activity,
            -- Xác định người tham gia khác
            CASE 
                WHEN c.participant1_phone = ? THEN c.participant2_phone
                ELSE c.participant1_phone
            END as other_participant_phone,
            -- Lấy tên người tham gia khác
            CASE 
                WHEN c.participant1_phone = ? THEN u2.userName
                ELSE u1.userName
            END as other_participant_name,
            -- Lấy tin nhắn cuối cùng
            m.message_text as last_message,
            m.sent_at as last_message_time
        FROM conversations c
        LEFT JOIN user u1 ON c.participant1_phone = u1.phone
        LEFT JOIN user u2 ON c.participant2_phone = u2.phone
        LEFT JOIN messages m ON c.last_message_id = m.id
        WHERE c.participant1_phone = ? OR c.participant2_phone = ?
        ORDER BY c.last_activity DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userPhone, $userPhone, $userPhone, $userPhone]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dữ liệu trả về
    $formattedConversations = [];
    foreach ($conversations as $conv) {
        $formattedConversations[] = [
            'id' => $conv['id'],
            'otherParticipantPhone' => $conv['other_participant_phone'],
            'otherParticipantName' => $conv['other_participant_name'] ?? 'Người dùng',
            'lastMessage' => $conv['last_message'] ?? 'Chưa có tin nhắn',
            'lastMessageTime' => $conv['last_message_time'] ?? $conv['last_activity'],
            'avatar' => $conv['other_participant_name'] ? substr($conv['other_participant_name'], 0, 2) : 'U'
        ];
    }
    
    sendSuccessResponse([
        'conversations' => $formattedConversations,
        'count' => count($formattedConversations)
    ], 'Conversations retrieved successfully');
    
} catch (Exception $e) {
    error_log('Get conversations error: ' . $e->getMessage());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?> 