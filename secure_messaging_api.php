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
    
    $action = isset($data['action']) ? $data['action'] : null;
    
    switch ($action) {
        case 'send_message':
            sendMessage($conn, $data);
            break;
            
        case 'get_messages':
            getMessages($conn, $data);
            break;
            
        case 'get_stranger_messages':
            getStrangerMessages($conn, $data);
            break;
            
        case 'respond_stranger_message':
            respondStrangerMessage($conn, $data);
            break;
            
        case 'get_conversations':
            getConversations($conn, $data);
            break;
            
        default:
            sendErrorResponse('Invalid action', 'Bad request', 400);
            break;
    }
    
} catch (Exception $e) {
    error_log('Secure messaging API error: ' . $e->getMessage());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}

// Gửi tin nhắn với kiểm tra friendship
function sendMessage($conn, $data) {
    $fromPhone = isset($data['from_phone']) ? sanitizeInput($data['from_phone']) : null;
    $toPhone = isset($data['to_phone']) ? sanitizeInput($data['to_phone']) : null;
    $messageText = isset($data['message_text']) ? sanitizeInput($data['message_text']) : null;
    $messageType = isset($data['message_type']) ? sanitizeInput($data['message_type']) : 'text';
    
    if (!$fromPhone || !$toPhone || !$messageText) {
        sendErrorResponse('from_phone, to_phone and message_text are required', 'Bad request', 400);
        return;
    }
    
    // Kiểm tra xem người nhận có bị block không
    $blockSql = "SELECT id FROM blocked_numbers WHERE user_phone = ? AND blocked_phone = ?";
    $blockStmt = $conn->prepare($blockSql);
    $blockStmt->execute([$toPhone, $fromPhone]);
    
    if ($blockStmt->fetch()) {
        sendErrorResponse('You are blocked by this user', 'Forbidden', 403);
        return;
    }
    
    // Kiểm tra friendship status
    $friendshipSql = "SELECT COUNT(*) as is_friend FROM friend_status 
                     WHERE ((user_phone = ? AND friend_phone = ?) OR (user_phone = ? AND friend_phone = ?))
                     AND status = 'accepted'";
    $friendshipStmt = $conn->prepare($friendshipSql);
    $friendshipStmt->execute([$fromPhone, $toPhone, $toPhone, $fromPhone]);
    $friendship = $friendshipStmt->fetch(PDO::FETCH_ASSOC);
    $isFriend = $friendship['is_friend'] > 0;
    
    // Lấy security settings của người nhận
    $settingsSql = "SELECT * FROM message_security_settings WHERE user_phone = ?";
    $settingsStmt = $conn->prepare($settingsSql);
    $settingsStmt->execute([$toPhone]);
    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Mặc định settings nếu chưa có
    if (!$settings) {
        $settings = [
            'allow_stranger_messages' => false,
            'require_friend_request' => true,
            'auto_block_spam' => true
        ];
    }
    
    if (!$isFriend) {
        // Nếu chưa là bạn bè
        if (!$settings['allow_stranger_messages']) {
            // Lưu vào stranger_messages để người nhận xem xét
            $strangerSql = "INSERT INTO stranger_messages (from_phone, to_phone, message_text, message_type) 
                           VALUES (?, ?, ?, ?)";
            $strangerStmt = $conn->prepare($strangerSql);
            
            if ($strangerStmt->execute([$fromPhone, $toPhone, $messageText, $messageType])) {
                sendSuccessResponse([
                    'message_id' => $conn->lastInsertId(),
                    'status' => 'pending_approval',
                    'requires_friendship' => true
                ], 'Message sent for approval');
            } else {
                sendErrorResponse('Failed to send message', 'Database error', 500);
            }
            return;
        }
    }
    
    // Gửi tin nhắn bình thường (đã là bạn bè hoặc cho phép tin nhắn từ người lạ)
    $conversationId = generateConversationId($fromPhone, $toPhone);
    
    $sql = "INSERT INTO messages (conversation_id, sender_phone, receiver_phone, message_text, message_type, friendship_status, requires_friendship) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    $friendshipStatus = $isFriend ? 'friends' : 'stranger';
    $requiresFriendship = !$isFriend;
    
    if ($stmt->execute([$conversationId, $fromPhone, $toPhone, $messageText, $messageType, $friendshipStatus, $requiresFriendship])) {
        // Cập nhật hoặc tạo conversation
        updateConversation($conn, $conversationId, $fromPhone, $toPhone, $conn->lastInsertId());
        
        sendSuccessResponse([
            'message_id' => $conn->lastInsertId(),
            'conversation_id' => $conversationId,
            'friendship_status' => $friendshipStatus
        ], 'Message sent successfully');
    } else {
        sendErrorResponse('Failed to send message', 'Database error', 500);
    }
}

// Lấy tin nhắn trong conversation
function getMessages($conn, $data) {
    $userPhone = isset($data['user_phone']) ? sanitizeInput($data['user_phone']) : null;
    $otherPhone = isset($data['other_phone']) ? sanitizeInput($data['other_phone']) : null;
    $limit = isset($data['limit']) ? (int)$data['limit'] : 50;
    $offset = isset($data['offset']) ? (int)$data['offset'] : 0;
    
    if (!$userPhone || !$otherPhone) {
        sendErrorResponse('user_phone and other_phone are required', 'Bad request', 400);
        return;
    }
    
    $conversationId = generateConversationId($userPhone, $otherPhone);
    
    $sql = "SELECT 
                m.*,
                c.name as sender_name,
                c.avatar_url as sender_avatar
            FROM messages m
            LEFT JOIN contacts c ON m.sender_phone = c.phone
            WHERE m.conversation_id = ?
            ORDER BY m.sent_at DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$conversationId, $limit, $offset]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Đánh dấu tin nhắn đã đọc
    $markReadSql = "UPDATE messages SET is_read = TRUE, read_at = NOW() 
                   WHERE conversation_id = ? AND receiver_phone = ? AND is_read = FALSE";
    $markReadStmt = $conn->prepare($markReadSql);
    $markReadStmt->execute([$conversationId, $userPhone]);
    
    sendSuccessResponse(['messages' => array_reverse($messages)], 'Messages retrieved successfully');
}

// Lấy tin nhắn từ stranger
function getStrangerMessages($conn, $data) {
    $userPhone = isset($data['user_phone']) ? sanitizeInput($data['user_phone']) : null;
    
    if (!$userPhone) {
        sendErrorResponse('user_phone is required', 'Bad request', 400);
        return;
    }
    
    $sql = "SELECT 
                sm.*,
                c.name as sender_name,
                c.avatar_url as sender_avatar,
                c.relationship
            FROM stranger_messages sm
            LEFT JOIN contacts c ON sm.from_phone = c.phone
            WHERE sm.to_phone = ? AND sm.status = 'pending'
            ORDER BY sm.sent_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userPhone]);
    $strangerMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendSuccessResponse(['stranger_messages' => $strangerMessages], 'Stranger messages retrieved successfully');
}

// Phản hồi tin nhắn từ stranger
function respondStrangerMessage($conn, $data) {
    $messageId = isset($data['message_id']) ? (int)$data['message_id'] : null;
    $response = isset($data['response']) ? $data['response'] : null; // 'allowed' or 'blocked'
    $userPhone = isset($data['user_phone']) ? sanitizeInput($data['user_phone']) : null;
    
    if (!$messageId || !$response || !$userPhone) {
        sendErrorResponse('message_id, response and user_phone are required', 'Bad request', 400);
        return;
    }
    
    if (!in_array($response, ['allowed', 'blocked'])) {
        sendErrorResponse('response must be "allowed" or "blocked"', 'Bad request', 400);
        return;
    }
    
    // Lấy thông tin stranger message
    $getSql = "SELECT * FROM stranger_messages WHERE id = ? AND to_phone = ? AND status = 'pending'";
    $getStmt = $conn->prepare($getSql);
    $getStmt->execute([$messageId, $userPhone]);
    $strangerMsg = $getStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$strangerMsg) {
        sendErrorResponse('Stranger message not found', 'Not found', 404);
        return;
    }
    
    // Cập nhật trạng thái
    $updateSql = "UPDATE stranger_messages SET status = ?, reviewed_at = NOW() WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->execute([$response, $messageId]);
    
    if ($response === 'allowed') {
        // Chuyển thành tin nhắn bình thường
        $conversationId = generateConversationId($strangerMsg['from_phone'], $userPhone);
        
        $insertSql = "INSERT INTO messages (conversation_id, sender_phone, receiver_phone, message_text, message_type, friendship_status, requires_friendship) 
                     VALUES (?, ?, ?, ?, ?, 'stranger', TRUE)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->execute([
            $conversationId,
            $strangerMsg['from_phone'],
            $userPhone,
            $strangerMsg['message_text'],
            $strangerMsg['message_type']
        ]);
        
        updateConversation($conn, $conversationId, $strangerMsg['from_phone'], $userPhone, $conn->lastInsertId());
        
    } else if ($response === 'blocked') {
        // Block số điện thoại này
        $blockSql = "INSERT IGNORE INTO blocked_numbers (user_phone, blocked_phone, reason, blocked_by) 
                    VALUES (?, ?, 'Blocked from stranger message', 'user')";
        $blockStmt = $conn->prepare($blockSql);
        $blockStmt->execute([$userPhone, $strangerMsg['from_phone']]);
    }
    
    sendSuccessResponse(['response' => $response], "Stranger message {$response} successfully");
}

// Lấy danh sách conversations
function getConversations($conn, $data) {
    $userPhone = isset($data['user_phone']) ? sanitizeInput($data['user_phone']) : null;
    
    if (!$userPhone) {
        sendErrorResponse('user_phone is required', 'Bad request', 400);
        return;
    }
    
    $sql = "SELECT DISTINCT
                CASE 
                    WHEN m.sender_phone = ? THEN m.receiver_phone 
                    ELSE m.sender_phone 
                END as other_phone,
                c.name,
                c.avatar_url,
                c.relationship,
                m.friendship_status,
                last_msg.message_text as last_message,
                last_msg.sent_at as last_message_time,
                COALESCE(unread.unread_count, 0) as unread_count
            FROM messages m
            LEFT JOIN contacts c ON (
                CASE 
                    WHEN m.sender_phone = ? THEN m.receiver_phone 
                    ELSE m.sender_phone 
                END = c.phone
            )
            LEFT JOIN (
                SELECT 
                    conversation_id,
                    message_text,
                    sent_at,
                    ROW_NUMBER() OVER (PARTITION BY conversation_id ORDER BY sent_at DESC) as rn
                FROM messages
            ) last_msg ON m.conversation_id = last_msg.conversation_id AND last_msg.rn = 1
            LEFT JOIN (
                SELECT 
                    conversation_id,
                    COUNT(*) as unread_count
                FROM messages 
                WHERE receiver_phone = ? AND is_read = FALSE
                GROUP BY conversation_id
            ) unread ON m.conversation_id = unread.conversation_id
            WHERE (m.sender_phone = ? OR m.receiver_phone = ?)
            GROUP BY other_phone
            ORDER BY last_msg.sent_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userPhone, $userPhone, $userPhone, $userPhone, $userPhone]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendSuccessResponse(['conversations' => $conversations], 'Conversations retrieved successfully');
}

// Helper functions
function generateConversationId($phone1, $phone2) {
    $phones = [$phone1, $phone2];
    sort($phones);
    return $phones[0] . '_' . $phones[1];
}

function updateConversation($conn, $conversationId, $participant1, $participant2, $lastMessageId) {
    $sql = "INSERT INTO conversations (id, participant1_phone, participant2_phone, last_message_id, last_activity) 
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            last_message_id = VALUES(last_message_id),
            last_activity = VALUES(last_activity)";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$conversationId, $participant1, $participant2, $lastMessageId]);
}
?> 