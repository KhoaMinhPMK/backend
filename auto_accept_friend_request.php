<?php
require_once 'config.php';

// DEBUG: Log raw request data
error_log("=== AUTO_ACCEPT_FRIEND_REQUEST.PHP CALLED ===");
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
    
    // Required parameters
    $requestId = isset($data['request_id']) ? (int)$data['request_id'] : null;
    
    error_log("Parameters - requestId: $requestId");
    
    if (!$requestId) {
        error_log("❌ Missing required parameters");
        sendErrorResponse('request_id is required', 'Bad request', 400);
        exit;
    }
    
    // Start transaction
    $conn->beginTransaction();
    error_log("✅ Transaction started");
    
    // Get friend request details
    $getFriendRequestSql = "SELECT * FROM friend_requests WHERE id = ? AND status IN ('pending', 'auto_pending')";
    $stmt = $conn->prepare($getFriendRequestSql);
    $stmt->execute([$requestId]);
    $friendRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$friendRequest) {
        $conn->rollback();
        error_log("❌ Friend request not found or not pending");
        sendErrorResponse('Friend request not found or already processed', 'Not found', 404);
        exit;
    }
    
    error_log("✅ Friend request found: " . json_encode($friendRequest));
    
    $fromPhone = $friendRequest['from_phone'];
    $toPhone = $friendRequest['to_phone'];
    
    // Get user names for notifications
    $getUserSql = "SELECT userName FROM user WHERE phone = ?";
    $stmt = $conn->prepare($getUserSql);
    
    $stmt->execute([$fromPhone]);
    $fromUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt->execute([$toPhone]);
    $toUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$fromUser || !$toUser) {
        $conn->rollback();
        error_log("❌ Could not find user data");
        sendErrorResponse('User data not found', 'Internal server error', 500);
        exit;
    }
    
    error_log("✅ Users found - From: " . $fromUser['userName'] . ", To: " . $toUser['userName']);
    
    // 1. Update friend request status to 'accepted'
    $updateRequestSql = "UPDATE friend_requests SET status = 'accepted', responded_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($updateRequestSql);
    $stmt->execute([$requestId]);
    
    error_log("✅ Friend request marked as accepted");
    
    // 2. Create friendship records in friend_status (both directions)
    $createFriendshipSql = "INSERT IGNORE INTO friend_status (user_phone, friend_phone, status, requester_phone, responded_at) VALUES (?, ?, 'accepted', ?, NOW())";
    $stmt = $conn->prepare($createFriendshipSql);
    
    // Direction 1: to_phone -> from_phone
    $stmt->execute([$toPhone, $fromPhone, $fromPhone]);
    // Direction 2: from_phone -> to_phone  
    $stmt->execute([$fromPhone, $toPhone, $fromPhone]);
    
    error_log("✅ Friendship records created in both directions");
    
    // 3. Create conversation
    $createConversationSql = "INSERT IGNORE INTO conversations (user1_phone, user2_phone, created_at) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($createConversationSql);
    $stmt->execute([$fromPhone, $toPhone]);
    
    $conversationId = $conn->lastInsertId();
    error_log("✅ Conversation created with ID: $conversationId");
    
    // 4. Create notification for both users
    $notification_data = [
        'type' => 'auto_friend_request_accepted',
        'title' => 'Tự động kết bạn thành công',
        'body' => 'Bạn và ' . $fromUser['userName'] . ' đã được kết bạn tự động qua hệ thống Premium!',
        'details' => [
            'accepter_phone' => $toPhone,
            'accepter_name' => $toUser['userName'],
            'requester_phone' => $fromPhone,
            'requester_name' => $fromUser['userName'],
            'original_request_id' => $requestId,
            'conversation_id' => $conversationId
        ]
    ];
    
    $insertNotifSql = "INSERT INTO notifications (user_phone, type, title, body, data) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertNotifSql);
    
    // Notification for both users
    $stmt->execute([
        $fromPhone,
        $notification_data['type'],
        $notification_data['title'],
        $notification_data['body'],
        json_encode($notification_data['details'])
    ]);
    
    $stmt->execute([
        $toPhone,
        $notification_data['type'],
        $notification_data['title'],
        $notification_data['body'],
        json_encode($notification_data['details'])
    ]);
    
    error_log("✅ Notifications created for both users");
    
    // Commit transaction
    $conn->commit();
    error_log("✅ Transaction committed successfully");
    
    $responseData = [
        'friendship_created' => true,
        'conversation_created' => true,
        'notifications_sent' => true,
        'accepter_name' => $toUser['userName'],
        'requester_name' => $fromUser['userName'],
        'conversation_id' => $conversationId
    ];
    
    error_log("✅ Auto accept friend request completed successfully");
    sendSuccessResponse($responseData, 'Auto friend request accepted successfully');
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    error_log("❌ Exception in auto_accept_friend_request.php: " . $e->getMessage());
    sendErrorResponse('Internal server error', 'Internal server error', 500);
}
?>
