<?php
require_once 'config.php';

// Tắt error output để tránh ảnh hưởng đến JSON response
error_reporting(0);
ini_set('display_errors', 0);

setCorsHeaders();

error_log("🔍 mark_message_read.php - Request started");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("❌ mark_message_read.php - Method not allowed: " . $_SERVER['REQUEST_METHOD']);
    sendErrorResponse('Method not allowed', 'Method not allowed', 405);
    exit;
}

try {
    error_log("🔍 mark_message_read.php - Getting database connection");
    $conn = getDatabaseConnection();
    error_log("✅ mark_message_read.php - Database connected successfully");
    
    $input = file_get_contents('php://input');
    error_log("🔍 mark_message_read.php - Raw input: " . substr($input, 0, 200));
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("❌ mark_message_read.php - JSON decode error: " . json_last_error_msg());
        sendErrorResponse('Invalid JSON format', 'Bad request', 400);
        exit;
    }
    
    error_log("✅ mark_message_read.php - JSON decoded successfully");
    
    // Validate required fields
    $messageId = isset($data['message_id']) ? (int)$data['message_id'] : null;
    $userPhone = isset($data['user_phone']) ? sanitizeInput($data['user_phone']) : null;
    
    error_log("🔍 mark_message_read.php - Validated data:", [
        'message_id' => $messageId,
        'user_phone' => $userPhone
    ]);
    
    if (!$messageId || !$userPhone) {
        error_log("❌ mark_message_read.php - Missing required fields");
        sendErrorResponse('Missing required fields', 'Bad request', 400);
        exit;
    }
    
    // Validate message exists and user is receiver
    $validateSql = "SELECT id, receiver_phone, conversation_id FROM messages WHERE id = ? AND receiver_phone = ?";
    $validateStmt = $conn->prepare($validateSql);
    $validateStmt->execute([$messageId, $userPhone]);
    $message = $validateStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message) {
        error_log("❌ mark_message_read.php - Message not found or user not receiver");
        sendErrorResponse('Message not found or access denied', 'Forbidden', 403);
        exit;
    }
    
    error_log("✅ mark_message_read.php - Message validated");
    
    // Update message as read
    $updateSql = "UPDATE messages SET is_read = 1, read_at = NOW() WHERE id = ? AND receiver_phone = ?";
    $updateStmt = $conn->prepare($updateSql);
    
    if (!$updateStmt) {
        error_log("❌ mark_message_read.php - SQL prepare failed: " . json_encode($conn->errorInfo()));
        sendErrorResponse('Database prepare error', 'Internal server error', 500);
        exit;
    }
    
    $updateResult = $updateStmt->execute([$messageId, $userPhone]);
    
    if (!$updateResult) {
        error_log("❌ mark_message_read.php - SQL execute failed: " . json_encode($updateStmt->errorInfo()));
        sendErrorResponse('Database execute error', 'Internal server error', 500);
        exit;
    }
    
    $affectedRows = $updateStmt->rowCount();
    error_log("✅ mark_message_read.php - Message marked as read, affected rows: " . $affectedRows);
    
    if ($affectedRows > 0) {
        // Send socket notification
        $socketPayload = [
            'message_id' => $messageId,
            'conversation_id' => $message['conversation_id'],
            'read_by' => $userPhone,
            'read_at' => date('Y-m-d H:i:s')
        ];
        
        // Get sender phone to notify them
        $getSenderSql = "SELECT sender_phone FROM messages WHERE id = ?";
        $getSenderStmt = $conn->prepare($getSenderSql);
        $getSenderStmt->execute([$messageId]);
        $senderData = $getSenderStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($senderData) {
            $socketSuccess = send_socket_notification($senderData['sender_phone'], $socketPayload);
            error_log("🔍 mark_message_read.php - Socket notification result: " . ($socketSuccess ? "Success" : "Failed"));
        }
        
        $responseData = [
            'message_id' => $messageId,
            'conversation_id' => $message['conversation_id'],
            'read_by' => $userPhone,
            'read_at' => date('Y-m-d H:i:s'),
            'socket_sent' => isset($socketSuccess) ? $socketSuccess : false
        ];
        
        error_log("🔍 mark_message_read.php - Sending success response");
        sendSuccessResponse($responseData, 'Message marked as read successfully');
        error_log("✅ mark_message_read.php - Response sent successfully");
    } else {
        error_log("⚠️ mark_message_read.php - No rows affected, message might already be read");
        sendSuccessResponse(['message_id' => $messageId], 'Message already marked as read');
    }
    
} catch (Exception $e) {
    error_log('❌ mark_message_read.php - Exception caught: ' . $e->getMessage());
    error_log('❌ mark_message_read.php - Stack trace: ' . $e->getTraceAsString());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?> 