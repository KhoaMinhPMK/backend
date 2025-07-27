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
    
    // Log request để debug
    error_log('🔄 Get conversations request for phone: ' . $userPhone);
    
    // Query lấy conversations với last message info
    // Conversation ID format: smaller_phone|larger_phone để consistent
    $sql = "
        SELECT 
            c.id as conversation_id,
            c.participant1_phone,
            c.participant2_phone,
            c.last_activity,
            m.content as last_message,
            m.sender_phone,
            m.created_at as last_message_time,
            u.userName as other_user_name,
            CASE 
                WHEN c.participant1_phone = ? THEN c.participant2_phone
                ELSE c.participant1_phone 
            END as other_user_phone
        FROM conversations c
        LEFT JOIN messages m ON c.last_message_id = m.id
        LEFT JOIN user u ON (
            CASE 
                WHEN c.participant1_phone = ? THEN c.participant2_phone = u.phone
                ELSE c.participant1_phone = u.phone
            END
        )
        WHERE c.participant1_phone = ? OR c.participant2_phone = ?
        ORDER BY c.last_activity DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userPhone, $userPhone, $userPhone, $userPhone]);
    
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dữ liệu cho frontend
    $formattedConversations = array_map(function($conv) use ($userPhone) {
        // Generate avatar từ tên người chat (2 chữ cái đầu)
        $nameWords = explode(' ', trim($conv['other_user_name'] ?? 'Unknown'));
        $avatar = '';
        if (count($nameWords) >= 2) {
            // Lấy chữ cái đầu của từ đầu và từ cuối
            $avatar = strtoupper(substr($nameWords[0], 0, 1) . substr($nameWords[count($nameWords)-1], 0, 1));
        } else {
            // Nếu chỉ có 1 từ, lấy 2 chữ cái đầu
            $avatar = strtoupper(substr($nameWords[0] ?? 'UN', 0, 2));
        }
        
        // Format thời gian last message
        $timeAgo = 'Chưa có tin nhắn';
        if ($conv['last_message_time']) {
            $lastMessageTime = new DateTime($conv['last_message_time']);
            $now = new DateTime();
            $diff = $now->diff($lastMessageTime);
            
            if ($diff->days > 0) {
                if ($diff->days == 1) {
                    $timeAgo = 'Hôm qua';
                } else {
                    $timeAgo = $lastMessageTime->format('d/m/Y');
                }
            } else if ($diff->h > 0) {
                $timeAgo = $diff->h . ' giờ trước';
            } else if ($diff->i > 0) {
                $timeAgo = $diff->i . ' phút trước';
            } else {
                $timeAgo = 'Vừa xong';
            }
        }
        
        // Check ai là người gửi tin nhắn cuối
        $lastMessagePrefix = '';
        if ($conv['sender_phone'] && $conv['last_message']) {
            if ($conv['sender_phone'] === $userPhone) {
                $lastMessagePrefix = 'Bạn: ';
            }
        }
        
        return [
            'conversationId' => $conv['conversation_id'],
            'otherUserName' => $conv['other_user_name'] ?? 'Người dùng',
            'otherUserPhone' => $conv['other_user_phone'],
            'avatar' => $avatar,
            'lastMessage' => $lastMessagePrefix . ($conv['last_message'] ?? ''),
            'lastActivity' => $conv['last_activity'],
            'timeAgo' => $timeAgo,
            'unreadCount' => 0, // TODO: Implement unread count later
            'type' => 'conversation'
        ];
    }, $conversations);
    
    error_log('✅ Get conversations result: ' . count($formattedConversations) . ' conversations found');
    
    sendSuccessResponse([
        'conversations' => $formattedConversations,
        'total' => count($formattedConversations)
    ], 'Lấy danh sách cuộc trò chuyện thành công');
    
} catch (Exception $e) {
    error_log('❌ Get conversations error: ' . $e->getMessage());
    error_log('❌ Stack trace: ' . $e->getTraceAsString());
    sendErrorResponse('Lỗi server: ' . $e->getMessage(), 'Internal server error', 500);
}
?> 