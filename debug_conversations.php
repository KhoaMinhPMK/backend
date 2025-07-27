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
    
    error_log("🔍 Debug conversations for phone: $userPhone");
    
    // 1. Kiểm tra tất cả conversations
    $allConversationsSql = "SELECT * FROM conversations ORDER BY created_at DESC LIMIT 10";
    $stmt = $conn->prepare($allConversationsSql);
    $stmt->execute();
    $allConversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("📋 All conversations count: " . count($allConversations));
    foreach ($allConversations as $conv) {
        error_log("   - ID: {$conv['id']}, P1: {$conv['participant1_phone']}, P2: {$conv['participant2_phone']}, Created: {$conv['created_at']}");
    }
    
    // 2. Kiểm tra conversations của user này
    $userConversationsSql = "SELECT * FROM conversations WHERE participant1_phone = ? OR participant2_phone = ?";
    $stmt = $conn->prepare($userConversationsSql);
    $stmt->execute([$userPhone, $userPhone]);
    $userConversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("📋 User conversations count: " . count($userConversations));
    foreach ($userConversations as $conv) {
        error_log("   - ID: {$conv['id']}, P1: {$conv['participant1_phone']}, P2: {$conv['participant2_phone']}");
    }
    
    // 3. Kiểm tra friend_status
    $friendStatusSql = "SELECT * FROM friend_status WHERE user_phone = ? AND status = 'accepted'";
    $stmt = $conn->prepare($friendStatusSql);
    $stmt->execute([$userPhone]);
    $friendStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("📋 Friend status count: " . count($friendStatus));
    foreach ($friendStatus as $fs) {
        error_log("   - User: {$fs['user_phone']}, Friend: {$fs['friend_phone']}, Status: {$fs['status']}");
    }
    
    // 4. Kiểm tra messages
    $messagesSql = "SELECT * FROM messages ORDER BY sent_at DESC LIMIT 5";
    $stmt = $conn->prepare($messagesSql);
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("📋 Recent messages count: " . count($messages));
    foreach ($messages as $msg) {
        error_log("   - ID: {$msg['id']}, Conv: {$msg['conversation_id']}, Sender: {$msg['sender_phone']}, Text: " . substr($msg['message_text'], 0, 50));
    }
    
    sendSuccessResponse([
        'all_conversations' => $allConversations,
        'user_conversations' => $userConversations,
        'friend_status' => $friendStatus,
        'recent_messages' => $messages,
        'debug_info' => [
            'user_phone' => $userPhone,
            'total_conversations' => count($allConversations),
            'user_conversations_count' => count($userConversations),
            'friend_status_count' => count($friendStatus),
            'recent_messages_count' => count($messages)
        ]
    ], 'Debug information retrieved successfully');
    
} catch (Exception $e) {
    error_log('Debug conversations error: ' . $e->getMessage());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?> 