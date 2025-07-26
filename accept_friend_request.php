<?php
require_once 'config.php';

// DEBUG: Log raw request data
error_log("=== ACCEPT_FRIEND_REQUEST.PHP CALLED ===");
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
    $userPhone = isset($data['user_phone']) ? sanitizeInput($data['user_phone']) : null;
    
    error_log("Parameters - requestId: $requestId, userPhone: $userPhone");
    
    if (!$requestId || !$userPhone) {
        error_log("❌ Missing required parameters");
        sendErrorResponse('request_id and user_phone are required', 'Bad request', 400);
        exit;
    }
    
    // Start transaction
    $conn->beginTransaction();
    error_log("✅ Transaction started");
    
    // Get friend request details
    $getFriendRequestSql = "SELECT * FROM friend_requests WHERE id = ? AND to_phone = ? AND status = 'pending'";
    $stmt = $conn->prepare($getFriendRequestSql);
    $stmt->execute([$requestId, $userPhone]);
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
    
    // 3. Create notification for the requester (fromPhone)
    $notification_data = [
        'type' => 'friend_request_accepted',
        'title' => 'Lời mời kết bạn được chấp nhận',
        'body' => $toUser['userName'] . ' đã chấp nhận lời mời kết bạn của bạn!',
        'details' => [
            'accepter_phone' => $toPhone,
            'accepter_name' => $toUser['userName'],
            'original_request_id' => $requestId
        ]
    ];
    
    $insertNotifSql = "INSERT INTO notifications (user_phone, type, title, body, data) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertNotifSql);
    $stmt->execute([
        $fromPhone,
        $notification_data['type'],
        $notification_data['title'],
        $notification_data['body'],
        json_encode($notification_data['details'])
    ]);
    
    $notificationId = $conn->lastInsertId();
    error_log("✅ Notification created with ID: $notificationId");
    
    // 4. Get full notification data to send via socket
    $getNotifSql = "SELECT * FROM notifications WHERE id = ?";
    $stmt = $conn->prepare($getNotifSql);
    $stmt->execute([$notificationId]);
    $fullNotificationPayload = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Commit transaction
    $conn->commit();
    error_log("✅ Transaction committed successfully");
    
    // 5. Send real-time notification to the requester
    if ($fullNotificationPayload) {
        $success = send_socket_notification($fromPhone, $fullNotificationPayload);
        error_log("Real-time notification sent: " . ($success ? "✅ Success" : "❌ Failed"));
    }
    
    $responseData = [
        'friendship_created' => true,
        'notification_sent' => isset($success) ? $success : false,
        'accepter_name' => $toUser['userName'],
        'requester_name' => $fromUser['userName']
    ];
    
    error_log("✅ Accept friend request completed successfully");
    sendSuccessResponse($responseData, 'Friend request accepted successfully');
    
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    error_log('❌ Accept friend request error: ' . $e->getMessage());
    error_log('❌ Stack trace: ' . $e->getTraceAsString());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?> 