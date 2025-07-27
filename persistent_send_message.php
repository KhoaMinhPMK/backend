<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

function sendSuccessResponse($data = null, $message = 'Success') {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function sendErrorResponse($message = 'Error', $status = 400) {
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Method not allowed', 405);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendErrorResponse('Invalid JSON input');
    }
    
    $senderPhone = trim($input['sender_phone'] ?? '');
    $receiverPhone = trim($input['receiver_phone'] ?? '');
    $messageText = trim($input['message_text'] ?? '');
    
    if (empty($senderPhone) || empty($receiverPhone) || empty($messageText)) {
        sendErrorResponse('Missing required fields: sender_phone, receiver_phone, message_text');
    }
    
    $pdo = getConnection();
    
    // Tìm hoặc tạo conversation
    $conversationId = generateConversationId($senderPhone, $receiverPhone);
    
    // Kiểm tra conversation đã tồn tại chưa
    $stmt = $pdo->prepare("SELECT id FROM persistent_conversations WHERE id = ?");
    $stmt->execute([$conversationId]);
    
    if (!$stmt->fetch()) {
        // Tạo conversation mới
        $stmt = $pdo->prepare("
            INSERT INTO persistent_conversations (id, participant1_phone, participant2_phone) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$conversationId, $senderPhone, $receiverPhone]);
    }
    
    // Thêm tin nhắn
    $stmt = $pdo->prepare("
        INSERT INTO persistent_messages (conversation_id, sender_phone, receiver_phone, message_text, message_type) 
        VALUES (?, ?, ?, ?, 'text')
    ");
    $stmt->execute([$conversationId, $senderPhone, $receiverPhone, $messageText]);
    
    $messageId = $pdo->lastInsertId();
    
    // Cập nhật conversation
    $stmt = $pdo->prepare("
        UPDATE persistent_conversations 
        SET last_message_id = ?, last_activity = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->execute([$messageId, $conversationId]);
    
    // Gửi socket notification (nếu cần)
    $messageData = [
        'conversation_id' => $conversationId,
        'sender_phone' => $senderPhone,
        'receiver_phone' => $receiverPhone,
        'message_text' => $messageText,
        'message_id' => $messageId,
        'sent_at' => date('Y-m-d H:i:s')
    ];
    
    send_socket_notification($receiverPhone, [
        'type' => 'persistent_message',
        'data' => $messageData
    ]);
    
    sendSuccessResponse([
        'message_id' => $messageId,
        'conversation_id' => $conversationId,
        'sent_at' => date('Y-m-d H:i:s')
    ], 'Message sent and saved successfully');
    
} catch (Exception $e) {
    error_log("Persistent send message error: " . $e->getMessage());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}
?> 