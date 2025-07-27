<?php
require_once 'config.php';

// Tắt error output để tránh ảnh hưởng đến JSON response
error_reporting(0);
ini_set('display_errors', 0);

setCorsHeaders();

error_log("🔍 send_message.php - Request started");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("❌ send_message.php - Method not allowed: " . $_SERVER['REQUEST_METHOD']);
    sendErrorResponse('Method not allowed', 'Method not allowed', 405);
    exit;
}

try {
    error_log("🔍 send_message.php - Getting database connection");
    $conn = getDatabaseConnection();
    error_log("✅ send_message.php - Database connected successfully");
    
    $input = file_get_contents('php://input');
    error_log("🔍 send_message.php - Raw input: " . substr($input, 0, 200));
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("❌ send_message.php - JSON decode error: " . json_last_error_msg());
        sendErrorResponse('Invalid JSON format', 'Bad request', 400);
        exit;
    }
    
    error_log("✅ send_message.php - JSON decoded successfully");
    
    // Validate required fields
    $conversationId = isset($data['conversation_id']) ? sanitizeInput($data['conversation_id']) : null;
    $senderPhone = isset($data['sender_phone']) ? sanitizeInput($data['sender_phone']) : null;
    $receiverPhone = isset($data['receiver_phone']) ? sanitizeInput($data['receiver_phone']) : null;
    $messageText = isset($data['message_text']) ? sanitizeInput($data['message_text']) : null;
    
    error_log("🔍 send_message.php - Validated data:", [
        'conversation_id' => $conversationId,
        'sender_phone' => $senderPhone,
        'receiver_phone' => $receiverPhone,
        'message_text' => substr($messageText, 0, 50) . '...'
    ]);
    
    if (!$conversationId || !$senderPhone || !$receiverPhone || !$messageText) {
        error_log("❌ send_message.php - Missing required fields");
        sendErrorResponse('Missing required fields', 'Bad request', 400);
        exit;
    }
    
    // Validate conversation exists and user is participant
    $validateSql = "SELECT id FROM conversations WHERE id = ? AND (participant1_phone = ? OR participant2_phone = ?)";
    $validateStmt = $conn->prepare($validateSql);
    $validateStmt->execute([$conversationId, $senderPhone, $senderPhone]);
    $conversation = $validateStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$conversation) {
        error_log("❌ send_message.php - Conversation not found or user not participant");
        sendErrorResponse('Conversation not found or access denied', 'Forbidden', 403);
        exit;
    }
    
    error_log("✅ send_message.php - Conversation validated");
    
    // Insert message into database
    $insertSql = "INSERT INTO messages (conversation_id, sender_phone, receiver_phone, message_text, message_type, requires_friendship, friendship_status, sent_at) VALUES (?, ?, ?, ?, 'text', 1, 'friends', NOW())";
    $insertStmt = $conn->prepare($insertSql);
    
    if (!$insertStmt) {
        error_log("❌ send_message.php - SQL prepare failed: " . json_encode($conn->errorInfo()));
        sendErrorResponse('Database prepare error', 'Internal server error', 500);
        exit;
    }
    
    $insertResult = $insertStmt->execute([$conversationId, $senderPhone, $receiverPhone, $messageText]);
    
    if (!$insertResult) {
        error_log("❌ send_message.php - SQL execute failed: " . json_encode($insertStmt->errorInfo()));
        sendErrorResponse('Database execute error', 'Internal server error', 500);
        exit;
    }
    
    $messageId = $conn->lastInsertId();
    error_log("✅ send_message.php - Message inserted with ID: " . $messageId);
    
    // Update conversation last_message_id and last_activity
    $updateConversationSql = "UPDATE conversations SET last_message_id = ?, last_activity = NOW() WHERE id = ?";
    $updateStmt = $conn->prepare($updateConversationSql);
    $updateStmt->execute([$messageId, $conversationId]);
    
    error_log("✅ send_message.php - Conversation updated");
    
    // Get full message data for response
    $getMessageSql = "SELECT * FROM messages WHERE id = ?";
    $getMessageStmt = $conn->prepare($getMessageSql);
    $getMessageStmt->execute([$messageId]);
    $messageData = $getMessageStmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("✅ send_message.php - Message data retrieved");
    
    // Send socket notification
    $socketPayload = [
        'id' => $messageId,
        'conversation_id' => $conversationId,
        'sender_phone' => $senderPhone,
        'receiver_phone' => $receiverPhone,
        'message_text' => $messageText,
        'message_type' => 'text',
        'is_read' => 0,
        'sent_at' => $messageData['sent_at'],
        'read_at' => null
    ];
    
    $socketSuccess = send_socket_notification($receiverPhone, $socketPayload);
    error_log("🔍 send_message.php - Socket notification result: " . ($socketSuccess ? "Success" : "Failed"));
    
    $responseData = [
        'message_id' => $messageId,
        'conversation_id' => $conversationId,
        'sender_phone' => $senderPhone,
        'receiver_phone' => $receiverPhone,
        'message_text' => $messageText,
        'sent_at' => $messageData['sent_at'],
        'socket_sent' => $socketSuccess
    ];
    
    error_log("🔍 send_message.php - Sending success response");
    sendSuccessResponse($responseData, 'Message sent successfully');
    error_log("✅ send_message.php - Response sent successfully");
    
} catch (Exception $e) {
    error_log('❌ send_message.php - Exception caught: ' . $e->getMessage());
    error_log('❌ send_message.php - Stack trace: ' . $e->getTraceAsString());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?> 