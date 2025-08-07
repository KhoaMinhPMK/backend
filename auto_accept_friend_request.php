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
    
    if (!$stmt) {
        throw new Exception("Failed to prepare friend request query: " . print_r($conn->errorInfo(), true));
    }
    
    $stmt->execute([$requestId]);
    $friendRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$friendRequest) {
        $conn->rollback();
        error_log("❌ Friend request not found or not pending for ID: $requestId");
        sendErrorResponse('Friend request not found or already processed', 'Not found', 404);
        exit;
    }
    
    error_log("✅ Friend request found: " . json_encode($friendRequest));
    
    $fromPhone = $friendRequest['from_phone'];
    $toPhone = $friendRequest['to_phone'];
    
    error_log("✅ Phone numbers - From: $fromPhone, To: $toPhone");
    
    // Validate that users are not trying to friend themselves
    if ($fromPhone === $toPhone) {
        $conn->rollback();
        error_log("❌ Self-friending detected: $fromPhone");
        sendErrorResponse('Cannot friend yourself', 'Bad request', 400);
        exit;
    }
    
    // Get user names for notifications
    $getUserSql = "SELECT userName FROM user WHERE phone = ?";
    $stmt = $conn->prepare($getUserSql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare user query: " . print_r($conn->errorInfo(), true));
    }
    
    $stmt->execute([$fromPhone]);
    $fromUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt->execute([$toPhone]);
    $toUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$fromUser || !$toUser) {
        $conn->rollback();
        error_log("❌ Could not find user data - fromUser: " . ($fromUser ? 'found' : 'not found') . ", toUser: " . ($toUser ? 'found' : 'not found'));
        sendErrorResponse('User data not found', 'Internal server error', 500);
        exit;
    }
    
    error_log("✅ Users found - From: " . $fromUser['userName'] . ", To: " . $toUser['userName']);
    
    // 1. Update friend request status
    $updateRequestSql = "UPDATE friend_requests SET status = 'accepted' WHERE id = ? AND status IN ('pending', 'auto_pending')";
    $stmt = $conn->prepare($updateRequestSql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare update request query: " . print_r($conn->errorInfo(), true));
    }
    
    if (!$stmt->execute([$requestId])) {
        throw new Exception("Failed to execute update request query: " . print_r($stmt->errorInfo(), true));
    }
    
    $affectedRows = $stmt->rowCount();
    error_log("✅ Friend request marked as accepted - Affected rows: $affectedRows");
    
    if ($affectedRows === 0) {
        $conn->rollback();
        error_log("❌ No friend request was updated - request may already be processed");
        sendErrorResponse('Friend request not found or already processed', 'Not found', 404);
        exit;
    }
    
    // 2. Create friendship records
    // Check if friendship already exists
    $checkFriendshipSql = "SELECT * FROM friend_status WHERE (user_phone = ? AND friend_phone = ?) OR (user_phone = ? AND friend_phone = ?)";
    $stmt = $conn->prepare($checkFriendshipSql);
    $stmt->execute([$fromPhone, $toPhone, $toPhone, $fromPhone]);
    $existingFriendship = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingFriendship) {
        error_log("✅ Friendship already exists, skipping creation");
    } else {
        // Create friendship records using INSERT IGNORE to avoid duplicates
        $createFriendshipSql = "INSERT IGNORE INTO friend_status (user_phone, friend_phone, status) VALUES (?, ?, 'accepted'), (?, ?, 'accepted')";
        $stmt = $conn->prepare($createFriendshipSql);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare friendship query: " . print_r($conn->errorInfo(), true));
        }
        
        if (!$stmt->execute([$fromPhone, $toPhone, $toPhone, $fromPhone])) {
            throw new Exception("Failed to execute friendship query: " . print_r($stmt->errorInfo(), true));
        }
        
        error_log("✅ Friendship records created");
    }
    
    // 3. Create conversation
    $conversationId = 'conv_' . md5($fromPhone . $toPhone . time());
    
    // Check if conversation already exists
    $checkConversationSql = "SELECT id FROM conversations WHERE (participant1_phone = ? AND participant2_phone = ?) OR (participant1_phone = ? AND participant2_phone = ?)";
    $stmt = $conn->prepare($checkConversationSql);
    $stmt->execute([$fromPhone, $toPhone, $toPhone, $fromPhone]);
    $existingConversation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingConversation) {
        $conversationId = $existingConversation['id'];
        error_log("✅ Existing conversation found: $conversationId");
    } else {
        // Create new conversation
        $createConversationSql = "INSERT INTO conversations (id, participant1_phone, participant2_phone) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($createConversationSql);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare conversation query: " . print_r($conn->errorInfo(), true));
        }
        
        if (!$stmt->execute([$conversationId, $fromPhone, $toPhone])) {
            throw new Exception("Failed to execute conversation query: " . print_r($stmt->errorInfo(), true));
        }
        
        error_log("✅ New conversation created: $conversationId");
    }
    
    // 4. Create notifications for both users
    if ($conversationId) {
        $notificationMessage = "Bạn và " . $fromUser['userName'] . " đã được kết bạn tự động qua hệ thống Premium!";
        
        // Notification for fromPhone (requester)
        $createNotificationSql = "INSERT INTO notifications (user_phone, title, body, type, data) VALUES (?, ?, ?, 'friend_request_accepted', ?)";
        $stmt = $conn->prepare($createNotificationSql);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare notification query: " . print_r($conn->errorInfo(), true));
        }
        
        $notificationData = json_encode([
            'request_id' => $requestId,
            'conversation_id' => $conversationId,
            'accepter_phone' => $toPhone,
            'accepter_name' => $toUser['userName'],
            'requester_phone' => $fromPhone,
            'requester_name' => $fromUser['userName']
        ]);
        
        if (!$stmt->execute([$fromPhone, 'Tự động kết bạn thành công', $notificationMessage, $notificationData])) {
            throw new Exception("Failed to execute notification query for fromPhone: " . print_r($stmt->errorInfo(), true));
        }
        
        // Notification for toPhone (accepter)
        $notificationMessage2 = "Bạn và " . $toUser['userName'] . " đã được kết bạn tự động qua hệ thống Premium!";
        
        if (!$stmt->execute([$toPhone, 'Tự động kết bạn thành công', $notificationMessage2, $notificationData])) {
            throw new Exception("Failed to execute notification query for toPhone: " . print_r($stmt->errorInfo(), true));
        }
        
        error_log("✅ Notifications created for both users");
        
        // 5. Send real-time notifications to socket server
        $fromNotificationSent = false;
        $toNotificationSent = false;
        try {
            $notificationPayload = [
                'type' => 'auto_friend_request_accepted',
                'title' => 'Tự động kết bạn thành công',
                'body' => 'Bạn và ' . $fromUser['userName'] . ' đã được kết bạn tự động qua hệ thống Premium!',
                'data' => [
                    'accepter_phone' => $toPhone,
                    'accepter_name' => $toUser['userName'],
                    'requester_phone' => $fromPhone,
                    'requester_name' => $fromUser['userName'],
                    'original_request_id' => $requestId,
                    'conversation_id' => $conversationId
                ]
            ];
            
            // Send notification to fromPhone (requester)
            $fromNotificationSent = send_socket_notification($fromPhone, $notificationPayload);
            if ($fromNotificationSent) {
                error_log("✅ Real-time notification sent to $fromPhone");
            } else {
                error_log("⚠️ Failed to send real-time notification to $fromPhone");
            }
            
            // Send notification to toPhone (accepter)
            $toNotificationSent = send_socket_notification($toPhone, $notificationPayload);
            if ($toNotificationSent) {
                error_log("✅ Real-time notification sent to $toPhone");
            } else {
                error_log("⚠️ Failed to send real-time notification to $toPhone");
            }
            
        } catch (Exception $e) {
            error_log("⚠️ Error sending real-time notifications: " . $e->getMessage());
            // Don't throw exception here, just log the error
        }
    } else {
        error_log("⚠️ No conversation ID available, skipping notifications");
    }
    
    $conn->commit();
    error_log("✅ Transaction committed successfully");
    
    $responseData = [
        'friendship_created' => !$existingFriendship, // true if created, false if already existed
        'conversation_created' => !empty($conversationId),
        'notifications_sent' => !empty($conversationId),
        'real_time_notifications_sent' => $fromNotificationSent && $toNotificationSent,
        'accepter_name' => $toUser['userName'],
        'requester_name' => $fromUser['userName'],
        'conversation_id' => $conversationId,
        'friendship_already_existed' => $existingFriendship ? true : false
    ];
    
    error_log("✅ Auto accept friend request completed successfully");
    sendSuccessResponse($responseData, 'Auto friend request accepted successfully');
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    error_log("❌ Exception in auto_accept_friend_request.php: " . $e->getMessage());
    error_log("❌ Stack trace: " . $e->getTraceAsString());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?>
