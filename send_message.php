<?php
require_once 'config.php';

// Tắt error output để tránh ảnh hưởng đến JSON response
error_reporting(0);
ini_set('display_errors', 0);

setCorsHeaders();

error_log("🔍 send_message.php - Request started");
error_log("🔍 send_message.php - Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("🔍 send_message.php - Request URI: " . $_SERVER['REQUEST_URI']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("❌ send_message.php - Method not allowed: " . $_SERVER['REQUEST_METHOD']);
    sendErrorResponse('Method not allowed', 'Method not allowed', 405);
    exit;
}

try {
    error_log("🔍 send_message.php - Getting database connection");
    try {
        $conn = getDatabaseConnection();
        error_log("✅ send_message.php - Database connected successfully");
    } catch (Exception $e) {
        error_log("❌ send_message.php - Database connection failed: " . $e->getMessage());
        sendErrorResponse('Database connection failed: ' . $e->getMessage(), 'Internal server error', 500);
        exit;
    }
    
    $input = file_get_contents('php://input');
    error_log("🔍 send_message.php - Raw input: " . substr($input, 0, 200));
    error_log("🔍 send_message.php - Input length: " . strlen($input));
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("❌ send_message.php - JSON decode error: " . json_last_error_msg());
        error_log("❌ send_message.php - JSON last error: " . json_last_error());
        sendErrorResponse('Invalid JSON format', 'Bad request', 400);
        exit;
    }
    
    error_log("✅ send_message.php - JSON decoded successfully");
    error_log("🔍 send_message.php - Decoded data: " . json_encode($data));
    
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
        error_log("❌ send_message.php - conversation_id: " . ($conversationId ?: 'NULL'));
        error_log("❌ send_message.php - sender_phone: " . ($senderPhone ?: 'NULL'));
        error_log("❌ send_message.php - receiver_phone: " . ($receiverPhone ?: 'NULL'));
        error_log("❌ send_message.php - message_text: " . ($messageText ?: 'NULL'));
        sendErrorResponse('Missing required fields', 'Bad request', 400);
        exit;
    }
    
    // Validate conversation exists and user is participant
    error_log("🔍 send_message.php - Validating conversation: " . $conversationId);
    error_log("🔍 send_message.php - Sender phone: " . $senderPhone);
    
    $validateSql = "SELECT id FROM conversations WHERE id = ? AND (participant1_phone = ? OR participant2_phone = ?)";
    $validateStmt = $conn->prepare($validateSql);
    
    if (!$validateStmt) {
        error_log("❌ send_message.php - Prepare validation statement failed: " . json_encode($conn->errorInfo()));
        throw new Exception('Prepare validation statement failed');
    }
    
    $validateResult = $validateStmt->execute([$conversationId, $senderPhone, $senderPhone]);
    
    if (!$validateResult) {
        error_log("❌ send_message.php - Execute validation failed: " . json_encode($validateStmt->errorInfo()));
        throw new Exception('Execute validation failed');
    }
    
    $conversation = $validateStmt->fetch(PDO::FETCH_ASSOC);
    error_log("🔍 send_message.php - Conversation validation result: " . ($conversation ? 'FOUND' : 'NOT FOUND'));
    
    if (!$conversation) {
        error_log("❌ send_message.php - Conversation not found or user not participant");
        error_log("❌ send_message.php - conversation_id: " . $conversationId);
        error_log("❌ send_message.php - sender_phone: " . $senderPhone);
        sendErrorResponse('Conversation not found or access denied', 'Forbidden', 403);
        exit;
    }
    
    error_log("✅ send_message.php - Conversation validated");
    
    // Start transaction
    error_log("🔍 send_message.php - Starting transaction...");
    $conn->beginTransaction();
    error_log("✅ send_message.php - Transaction started successfully");
    
    try {
        // Insert message into database
        error_log("🔍 send_message.php - Preparing insert statement...");
        $insertSql = "INSERT INTO messages (conversation_id, sender_phone, receiver_phone, message_text, message_type, requires_friendship, friendship_status, sent_at) VALUES (?, ?, ?, ?, 'text', 1, 'stranger', NOW())";
        error_log("🔍 send_message.php - Insert SQL: " . $insertSql);
        
        $insertStmt = $conn->prepare($insertSql);
        
        if (!$insertStmt) {
            error_log("❌ send_message.php - SQL prepare failed: " . json_encode($conn->errorInfo()));
            throw new Exception('Database prepare error');
        }
        
        error_log("🔍 send_message.php - Executing insert with params: " . json_encode([$conversationId, $senderPhone, $receiverPhone, substr($messageText, 0, 50)]));
        $insertResult = $insertStmt->execute([$conversationId, $senderPhone, $receiverPhone, $messageText]);
        
        if (!$insertResult) {
            error_log("❌ send_message.php - SQL execute failed: " . json_encode($insertStmt->errorInfo()));
            throw new Exception('Database execute error');
        }
        
        $messageId = $conn->lastInsertId();
        error_log("✅ send_message.php - Message inserted with ID: " . $messageId);
        
        // Update conversation last_message_id and last_activity
        error_log("🔍 send_message.php - Preparing update conversation statement...");
        $updateConversationSql = "UPDATE conversations SET last_message_id = ?, last_activity = NOW() WHERE id = ?";
        error_log("🔍 send_message.php - Update SQL: " . $updateConversationSql);
        
        $updateStmt = $conn->prepare($updateConversationSql);
        
        if (!$updateStmt) {
            error_log("❌ send_message.php - Prepare update statement failed: " . json_encode($conn->errorInfo()));
            throw new Exception('Prepare update statement failed');
        }
        
        error_log("🔍 send_message.php - Executing update with params: " . json_encode([$messageId, $conversationId]));
        $updateResult = $updateStmt->execute([$messageId, $conversationId]);
        
        if (!$updateResult) {
            error_log("❌ send_message.php - Update conversation failed: " . json_encode($updateStmt->errorInfo()));
            throw new Exception('Update conversation error');
        }
        
        error_log("✅ send_message.php - Conversation updated successfully");
        
        // Commit transaction
        error_log("🔍 send_message.php - Committing transaction...");
        $conn->commit();
        error_log("✅ send_message.php - Transaction committed successfully");
        
    } catch (Exception $e) {
        // Rollback transaction on error
        error_log("❌ send_message.php - Exception caught: " . $e->getMessage());
        error_log("❌ send_message.php - Rolling back transaction...");
        $conn->rollback();
        error_log("✅ send_message.php - Transaction rolled back successfully");
        sendErrorResponse('Database error: ' . $e->getMessage(), 'Internal server error', 500);
        exit;
    }
    
    // Get full message data for response
    $getMessageSql = "SELECT * FROM messages WHERE id = ?";
    $getMessageStmt = $conn->prepare($getMessageSql);
    $getMessageStmt->execute([$messageId]);
    $messageData = $getMessageStmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("✅ send_message.php - Message data retrieved");
    
    // Send socket notification
    error_log("🔍 send_message.php - Preparing socket notification...");
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
    
    error_log("🔍 send_message.php - Socket payload: " . json_encode($socketPayload));
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
    
    error_log("🔍 send_message.php - Preparing success response...");
    error_log("🔍 send_message.php - Response data: " . json_encode($responseData));
    sendSuccessResponse($responseData, 'Message sent successfully');
    error_log("✅ send_message.php - Response sent successfully");
    error_log("✅ send_message.php - Request completed successfully");
    
} catch (Exception $e) {
    error_log('❌ send_message.php - Exception caught: ' . $e->getMessage());
    error_log('❌ send_message.php - Stack trace: ' . $e->getTraceAsString());
    
    // Check if we're in a transaction and rollback if needed
    if ($conn->inTransaction()) {
        $conn->rollback();
        error_log('❌ send_message.php - Transaction rolled back due to exception');
    }
    
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?> 