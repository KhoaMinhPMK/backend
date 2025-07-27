<?php
require_once 'config.php';

// DEBUG: Log raw request data
error_log("=== GET_MESSAGES.PHP CALLED ===");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Raw input: " . file_get_contents('php://input'));

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("❌ Method not allowed: " . $_SERVER['REQUEST_METHOD']);
    sendErrorResponse('Method not allowed', 'Method not allowed', 405);
    exit;
}

try {
    $conn = getDatabaseConnection();
    error_log("✅ Database connection established");
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    error_log("Parsed input data: " . print_r($data, true));
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("❌ JSON decode error: " . json_last_error_msg());
        sendErrorResponse('Invalid JSON format', 'Bad request', 400);
        exit;
    }
    
    $conversationId = isset($data['conversation_id']) ? sanitizeInput($data['conversation_id']) : null;
    $userPhone = isset($data['user_phone']) ? sanitizeInput($data['user_phone']) : null;
    
    error_log("Parameters - conversationId: $conversationId, userPhone: $userPhone");
    
    if (!$conversationId || !$userPhone) {
        error_log("❌ Missing required parameters");
        sendErrorResponse('conversation_id and user_phone are required', 'Bad request', 400);
        exit;
    }
    
    // Kiểm tra user có quyền xem conversation này không
    $checkPermissionSql = "SELECT * FROM conversations WHERE id = ? AND (participant1_phone = ? OR participant2_phone = ?)";
    $stmt = $conn->prepare($checkPermissionSql);
    $stmt->execute([$conversationId, $userPhone, $userPhone]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$conversation) {
        error_log("❌ Conversation not found or access denied for user: $userPhone, conversation: $conversationId");
        sendErrorResponse('Conversation not found or access denied', 'Not found', 404);
        exit;
    }
    
    error_log("✅ Conversation access verified for user: $userPhone");
    
    // Lấy messages
    $sql = "SELECT 
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
                m.requires_friendship,
                m.friendship_status,
                u.userName as sender_name
            FROM messages m
            JOIN user u ON m.sender_phone = u.phone
            WHERE m.conversation_id = ?
            ORDER BY m.sent_at ASC";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("❌ Database prepare error");
        sendErrorResponse('Database prepare error', 'Internal server error', 500);
        exit;
    }
    
    $stmt->execute([$conversationId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("✅ Found " . count($messages) . " messages for conversation: $conversationId");
    
    // Format response
    $formattedMessages = array_map(function($msg) use ($userPhone) {
        return [
            'id' => (int)$msg['id'],
            'conversationId' => $msg['conversation_id'],
            'senderPhone' => $msg['sender_phone'],
            'receiverPhone' => $msg['receiver_phone'],
            'messageText' => $msg['message_text'],
            'messageType' => $msg['message_type'],
            'fileUrl' => $msg['file_url'],
            'isRead' => (bool)$msg['is_read'],
            'sentAt' => $msg['sent_at'],
            'readAt' => $msg['read_at'],
            'requiresFriendship' => (bool)$msg['requires_friendship'],
            'friendshipStatus' => $msg['friendship_status'],
            'senderName' => $msg['sender_name'],
            'isOwnMessage' => $msg['sender_phone'] === $userPhone
        ];
    }, $messages);
    
    $responseData = [
        'messages' => $formattedMessages,
        'total' => count($formattedMessages),
        'conversation' => [
            'id' => $conversation['id'],
            'participant1Phone' => $conversation['participant1_phone'],
            'participant2Phone' => $conversation['participant2_phone'],
            'lastActivity' => $conversation['last_activity'],
            'createdAt' => $conversation['created_at']
        ]
    ];
    
    error_log("✅ Get messages completed successfully");
    sendSuccessResponse($responseData, 'Messages retrieved successfully');
    
} catch (Exception $e) {
    error_log('❌ Get messages error: ' . $e->getMessage());
    error_log('❌ Stack trace: ' . $e->getTraceAsString());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?> 