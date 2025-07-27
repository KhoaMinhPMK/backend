<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

// Disable error output to prevent breaking JSON response
error_reporting(0);
ini_set('display_errors', 0);

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    error_log("send_message_v2 - Input received: " . $input);
    
    if (!$data) {
        error_log("send_message_v2 - Invalid JSON input");
        sendErrorResponse(400, "Invalid JSON format");
        exit();
    }
    
    // Validate required fields
    $required_fields = ['sender_phone', 'receiver_phone', 'message_text'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            error_log("send_message_v2 - Missing required field: " . $field);
            sendErrorResponse(400, "Missing required field: " . $field);
            exit();
        }
    }
    
    $sender_phone = $data['sender_phone'];
    $receiver_phone = $data['receiver_phone'];
    $message_text = $data['message_text'];
    $message_type = isset($data['message_type']) ? $data['message_type'] : 'text';
    $file_url = isset($data['file_url']) ? $data['file_url'] : null;
    
    error_log("send_message_v2 - Validated data: sender=$sender_phone, receiver=$receiver_phone, text=$message_text");
    
    // Start transaction
    $pdo->beginTransaction();
    error_log("send_message_v2 - Transaction started");
    
    try {
        // Step 1: Find or create conversation
        $conversation_id = findOrCreateConversation($pdo, $sender_phone, $receiver_phone);
        error_log("send_message_v2 - Conversation ID: $conversation_id");
        
        // Step 2: Insert message
        $message_id = insertMessage($pdo, $conversation_id, $sender_phone, $receiver_phone, $message_text, $message_type, $file_url);
        error_log("send_message_v2 - Message inserted with ID: $message_id");
        
        // Step 3: Update conversation with last message
        updateConversationLastMessage($pdo, $conversation_id, $message_id);
        error_log("send_message_v2 - Conversation updated");
        
        // Commit transaction
        $pdo->commit();
        error_log("send_message_v2 - Transaction committed successfully");
        
        // Step 4: Send socket notification
        $notification_data = [
            'type' => 'new_message',
            'conversation_id' => $conversation_id,
            'message_id' => $message_id,
            'sender_phone' => $sender_phone,
            'receiver_phone' => $receiver_phone,
            'message_text' => $message_text,
            'sent_at' => date('Y-m-d H:i:s')
        ];
        
        send_socket_notification($receiver_phone, $notification_data);
        error_log("send_message_v2 - Socket notification sent");
        
        // Return success response
        $response = [
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => [
                'message_id' => $message_id,
                'conversation_id' => $conversation_id,
                'sent_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        error_log("send_message_v2 - Success response: " . json_encode($response));
        echo json_encode($response);
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("send_message_v2 - Transaction rolled back: " . $e->getMessage());
        sendErrorResponse(500, "Database error: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    error_log("send_message_v2 - General error: " . $e->getMessage());
    sendErrorResponse(500, "Server error: " . $e->getMessage());
}

function findOrCreateConversation($pdo, $phone1, $phone2) {
    // Sort phones to ensure consistent conversation ID
    $participant1 = min($phone1, $phone2);
    $participant2 = max($phone1, $phone2);
    
    // Check if conversation exists
    $stmt = $pdo->prepare("
        SELECT id FROM conversations 
        WHERE (participant1_phone = ? AND participant2_phone = ?) 
        OR (participant1_phone = ? AND participant2_phone = ?)
    ");
    $stmt->execute([$participant1, $participant2, $participant2, $participant1]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($conversation) {
        error_log("send_message_v2 - Found existing conversation: " . $conversation['id']);
        return $conversation['id'];
    }
    
    // Create new conversation
    $conversation_id = 'conv_' . md5($participant1 . '_' . $participant2 . '_' . time());
    
    $stmt = $pdo->prepare("
        INSERT INTO conversations (id, participant1_phone, participant2_phone) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$conversation_id, $participant1, $participant2]);
    
    error_log("send_message_v2 - Created new conversation: $conversation_id");
    return $conversation_id;
}

function insertMessage($pdo, $conversation_id, $sender_phone, $receiver_phone, $message_text, $message_type, $file_url) {
    $stmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, sender_phone, receiver_phone, message_text, message_type, file_url, requires_friendship, friendship_status) 
        VALUES (?, ?, ?, ?, ?, ?, 1, '')
    ");
    $stmt->execute([$conversation_id, $sender_phone, $receiver_phone, $message_text, $message_type, $file_url]);
    
    return $pdo->lastInsertId();
}

function updateConversationLastMessage($pdo, $conversation_id, $message_id) {
    $stmt = $pdo->prepare("
        UPDATE conversations 
        SET last_message_id = ?, last_activity = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->execute([$message_id, $conversation_id]);
}

// Function sendErrorResponse đã được định nghĩa trong config.php
?> 