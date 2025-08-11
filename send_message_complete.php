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
    // Kết nối database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    error_log("send_message_complete - Input received: " . $input);
    
    if (!$data) {
        error_log("send_message_complete - Invalid JSON input");
        sendErrorResponse(400, "Invalid JSON format");
        exit();
    }
    
    // Validate required fields
    $required_fields = ['sender_phone', 'receiver_phone'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            error_log("send_message_complete - Missing required field: " . $field);
            sendErrorResponse(400, "Missing required field: " . $field);
            exit();
        }
    }
    
    $sender_phone = $data['sender_phone'];
    $receiver_phone = $data['receiver_phone'];
    $message_text = isset($data['message_text']) ? $data['message_text'] : '';
    $message_type = isset($data['message_type']) ? $data['message_type'] : 'text';
    $file_url = isset($data['file_url']) ? $data['file_url'] : null;

    // Ensure either message_text or file_url is provided
    if (empty(trim($message_text)) && empty($file_url)) {
      error_log("send_message_complete - Missing message_text or file_url");
      sendErrorResponse(400, "Either message_text or file_url is required");
      exit();
    }
    
    error_log("send_message_complete - Validated data: sender=$sender_phone, receiver=$receiver_phone, type=$message_type, hasFile=" . (!empty($file_url) ? '1' : '0'));
    
    // Start transaction
    $pdo->beginTransaction();
    error_log("send_message_complete - Transaction started");
    
    try {
        // Step 1: Find or create conversation
        $conversation_id = findOrCreateConversation($pdo, $sender_phone, $receiver_phone);
        error_log("send_message_complete - Conversation ID: $conversation_id");
        
        // Step 2: Insert message into database
        $message_id = insertMessage($pdo, $conversation_id, $sender_phone, $receiver_phone, $message_text, $message_type, $file_url);
        error_log("send_message_complete - Message inserted with ID: $message_id");
        
        // Step 3: Update conversation with last message
        updateConversationLastMessage($pdo, $conversation_id, $message_id);
        error_log("send_message_complete - Conversation updated");
        
        // Commit transaction
        $pdo->commit();
        error_log("send_message_complete - Database transaction committed successfully");
        
        // Step 4: Send to Socket Server for real-time delivery
        $socket_result = sendToSocketServer($sender_phone, $receiver_phone, $message_text, $conversation_id, $message_id, $message_type, $file_url);
        error_log("send_message_complete - Socket server result: " . ($socket_result ? 'success' : 'failed'));
        
        // Step 5: Send push notification to receiver
        // Always send push notification when a message is sent
        error_log("send_message_complete - Starting push notification process for receiver phone: $receiver_phone");
        
        $getReceiverEmailSql = "SELECT userId, email, userName FROM user WHERE phone = ?";
        $getReceiverEmailStmt = $pdo->prepare($getReceiverEmailSql);
        $getReceiverEmailStmt->execute([$receiver_phone]);
        $receiverData = $getReceiverEmailStmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("send_message_complete - Receiver data query result: " . json_encode($receiverData));
        
        if ($receiverData && $receiverData['email']) {
            error_log("send_message_complete - Found receiver email: {$receiverData['email']}");
            
            // Get sender's name for notification
            $getSenderNameSql = "SELECT userId, userName FROM user WHERE phone = ?";
            $getSenderNameStmt = $pdo->prepare($getSenderNameSql);
            $getSenderNameStmt->execute([$sender_phone]);
            $senderData = $getSenderNameStmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("send_message_complete - Sender data query result: " . json_encode($senderData));
            
            $senderName = $senderData ? $senderData['userName'] : 'Người thân';
            $receiverName = $receiverData['userName'] ? $receiverData['userName'] : 'Người dùng';
            
            // Create notification title and body
            $notificationTitle = "Tin nhắn mới từ $senderName";
            $notificationBody = $message_text;
            
            error_log("send_message_complete - Notification title: $notificationTitle");
            error_log("send_message_complete - Notification body: $notificationBody");
            
            // Include push notification function
            require_once 'send_push_notification.php';
            
            error_log("send_message_complete - Calling sendPushNotification for email: {$receiverData['email']}");
            
            $push_result = sendPushNotification(
                $receiverData['email'],
                $notificationTitle,
                $notificationBody,
                [
                    'type' => 'message',
                    'conversation.id' => $conversation_id,
                    'sender.phone' => $sender_phone,
                    'receiver.phone' => $receiver_phone,
                    'message.id' => $message_id,
                    'message.text' => $message_text,
                    'message.type' => $message_type,
                    'sender.name' => $senderName,
                    'receiver.name' => $receiverName,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            );
            
            error_log("send_message_complete - Push notification result: " . json_encode($push_result));
            
            if ($push_result['success']) {
                error_log("send_message_complete - Push notification sent successfully to {$receiverData['email']}");
            } else {
                error_log("send_message_complete - Push notification failed: " . $push_result['message']);
            }
        } else {
            error_log("send_message_complete - No receiver email found for phone: $receiver_phone");
        }
        
        // Return success response
        $response = [
            'success' => true,
            'message' => 'Message sent successfully to database and socket server',
            'data' => [
                'message_id' => $message_id,
                'conversation_id' => $conversation_id,
                'sent_at' => date('Y-m-d H:i:s'),
                'socket_delivered' => $socket_result
            ]
        ];
        
        error_log("send_message_complete - Success response: " . json_encode($response));
        echo json_encode($response);
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log('❌ send_message_complete - DB error: ' . $e->getMessage());
        error_log('❌ send_message_complete - Trace: ' . $e->getTraceAsString());
        sendErrorResponse(500, 'Database error: ' . $e->getMessage());
        exit();
    }
    
} catch (Exception $e) {
    error_log('❌ send_message_complete - Server error: ' . $e->getMessage());
    error_log('❌ send_message_complete - Trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    exit();
}

function findOrCreateConversation($pdo, $sender_phone, $receiver_phone) {
    // Try to find existing conversation between the two phones
    $stmt = $pdo->prepare("SELECT id FROM conversations WHERE (participant1_phone = ? AND participant2_phone = ?) OR (participant1_phone = ? AND participant2_phone = ?)");
    $stmt->execute([$sender_phone, $receiver_phone, $receiver_phone, $sender_phone]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($conversation) {
        return $conversation['id'];
    }
    
    // If not found, create a new conversation with deterministic ID
    $conversation_id = 'conv_' . md5(min($sender_phone, $receiver_phone) . max($sender_phone, $receiver_phone));
    $stmt = $pdo->prepare("INSERT INTO conversations (id, participant1_phone, participant2_phone, created_at, last_activity) VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
    $stmt->execute([$conversation_id, $sender_phone, $receiver_phone]);
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

function sendToSocketServer($sender_phone, $receiver_phone, $message_text, $conversation_id, $message_id, $message_type = 'text', $file_url = null) {
    // IMPORTANT: Use the correct IP address or domain of your Node.js server.
    // If running on the same machine for development, you can use 'http://localhost:3000/send-message'.
    // For production, use your public domain e.g., 'https://chat.viegrand.site/send-message'.
    $socketServerUrl = 'https://chat.viegrand.site/send-message'; 
    $secretKey = 'viegrand_super_secret_key_for_php_2025'; // Must match the key in server.js

    $socketData = [
        'sender_phone' => $sender_phone,
        'receiver_phone' => $receiver_phone,
        'message_text' => $message_text,
        'conversation_id' => $conversation_id,
        'message_id' => $message_id,
        'message_type' => $message_type,
        'file_url' => $file_url,
        'timestamp' => date('Y-m-d H:i:s'),
        'secret' => $secretKey,
    ];

    $ch = curl_init($socketServerUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($socketData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen(json_encode($socketData))
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5-second timeout

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        error_log('sendToSocketServer - cURL error: ' . $curlError);
        return false;
    }

    error_log('sendToSocketServer - Response: HTTP ' . $httpCode . ' - ' . $response);
    return $httpCode >= 200 && $httpCode < 300;
}

// Function sendErrorResponse đã được định nghĩa trong config.php
?> 