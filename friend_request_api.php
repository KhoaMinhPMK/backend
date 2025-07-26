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
    
    $action = isset($data['action']) ? $data['action'] : null;
    
    switch ($action) {
        case 'send_request':
            sendFriendRequest($conn, $data);
            break;
            
        case 'respond_request':
            respondFriendRequest($conn, $data);
            break;
            
        case 'get_pending_requests':
            getPendingRequests($conn, $data);
            break;
            
        case 'get_friends':
            getFriends($conn, $data);
            break;
            
        case 'block_user':
            blockUser($conn, $data);
            break;
            
        default:
            sendErrorResponse('Invalid action', 'Bad request', 400);
            break;
    }
    
} catch (Exception $e) {
    error_log('Friend request API error: ' . $e->getMessage());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}

// Gửi lời mời kết bạn
function sendFriendRequest($conn, $data) {
    $fromPhone = isset($data['from_phone']) ? sanitizeInput($data['from_phone']) : null;
    $toPhone = isset($data['to_phone']) ? sanitizeInput($data['to_phone']) : null;
    $message = isset($data['message']) ? sanitizeInput($data['message']) : '';
    
    if (!$fromPhone || !$toPhone) {
        sendErrorResponse('from_phone and to_phone are required', 'Bad request', 400);
        return;
    }
    
    // Kiểm tra xem đã có friend request chưa
    $checkSql = "SELECT id FROM friend_requests 
                 WHERE from_phone = ? AND to_phone = ? AND status = 'pending' 
                 AND expires_at > NOW()";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([$fromPhone, $toPhone]);
    
    if ($checkStmt->fetch()) {
        sendErrorResponse('Friend request already sent', 'Conflict', 409);
        return;
    }
    
    // Kiểm tra xem đã là bạn bè chưa
    $friendSql = "SELECT id FROM friend_status 
                  WHERE ((user_phone = ? AND friend_phone = ?) OR (user_phone = ? AND friend_phone = ?))
                  AND status = 'accepted'";
    $friendStmt = $conn->prepare($friendSql);
    $friendStmt->execute([$fromPhone, $toPhone, $toPhone, $fromPhone]);
    
    if ($friendStmt->fetch()) {
        sendErrorResponse('Already friends', 'Conflict', 409);
        return;
    }
    
    // Gửi friend request
    $sql = "INSERT INTO friend_requests (from_phone, to_phone, message) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute([$fromPhone, $toPhone, $message])) {
        sendSuccessResponse(['request_id' => $conn->lastInsertId()], 'Friend request sent successfully');
    } else {
        sendErrorResponse('Failed to send friend request', 'Database error', 500);
    }
}

// Phản hồi lời mời kết bạn
function respondFriendRequest($conn, $data) {
    $requestId = isset($data['request_id']) ? (int)$data['request_id'] : null;
    $response = isset($data['response']) ? $data['response'] : null; // 'accepted' or 'rejected'
    $userPhone = isset($data['user_phone']) ? sanitizeInput($data['user_phone']) : null;
    
    if (!$requestId || !$response || !$userPhone) {
        sendErrorResponse('request_id, response and user_phone are required', 'Bad request', 400);
        return;
    }
    
    if (!in_array($response, ['accepted', 'rejected'])) {
        sendErrorResponse('response must be "accepted" or "rejected"', 'Bad request', 400);
        return;
    }
    
    // Kiểm tra request có tồn tại và thuộc về user này không
    $checkSql = "SELECT * FROM friend_requests WHERE id = ? AND to_phone = ? AND status = 'pending'";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([$requestId, $userPhone]);
    $request = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        sendErrorResponse('Friend request not found or already responded', 'Not found', 404);
        return;
    }
    
    // Cập nhật trạng thái request
    $updateSql = "UPDATE friend_requests SET status = ?, responded_at = NOW() WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    
    if ($updateStmt->execute([$response, $requestId])) {
        sendSuccessResponse(['status' => $response], "Friend request {$response} successfully");
    } else {
        sendErrorResponse('Failed to update friend request', 'Database error', 500);
    }
}

// Lấy danh sách lời mời kết bạn đang chờ
function getPendingRequests($conn, $data) {
    $userPhone = isset($data['user_phone']) ? sanitizeInput($data['user_phone']) : null;
    
    if (!$userPhone) {
        sendErrorResponse('user_phone is required', 'Bad request', 400);
        return;
    }
    
    $sql = "SELECT 
                fr.id,
                fr.from_phone,
                fr.message,
                fr.sent_at,
                fr.expires_at,
                c.name as sender_name,
                c.avatar_url as sender_avatar,
                c.relationship
            FROM friend_requests fr
            LEFT JOIN contacts c ON fr.from_phone = c.phone
            WHERE fr.to_phone = ? AND fr.status = 'pending' AND fr.expires_at > NOW()
            ORDER BY fr.sent_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userPhone]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendSuccessResponse(['requests' => $requests], 'Pending requests retrieved successfully');
}

// Lấy danh sách bạn bè
function getFriends($conn, $data) {
    $userPhone = isset($data['user_phone']) ? sanitizeInput($data['user_phone']) : null;
    
    if (!$userPhone) {
        sendErrorResponse('user_phone is required', 'Bad request', 400);
        return;
    }
    
    $sql = "SELECT * FROM user_friends WHERE user_phone = ? ORDER BY is_favorite DESC, name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userPhone]);
    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendSuccessResponse(['friends' => $friends], 'Friends list retrieved successfully');
}

// Block người dùng
function blockUser($conn, $data) {
    $userPhone = isset($data['user_phone']) ? sanitizeInput($data['user_phone']) : null;
    $blockedPhone = isset($data['blocked_phone']) ? sanitizeInput($data['blocked_phone']) : null;
    $reason = isset($data['reason']) ? sanitizeInput($data['reason']) : 'User blocked';
    
    if (!$userPhone || !$blockedPhone) {
        sendErrorResponse('user_phone and blocked_phone are required', 'Bad request', 400);
        return;
    }
    
    $sql = "INSERT IGNORE INTO blocked_numbers (user_phone, blocked_phone, reason, blocked_by) 
            VALUES (?, ?, ?, 'user')";
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute([$userPhone, $blockedPhone, $reason])) {
        // Cũng block trong friend_status nếu đã là bạn bè
        $blockFriendSql = "UPDATE friend_status SET status = 'blocked' 
                          WHERE ((user_phone = ? AND friend_phone = ?) OR (user_phone = ? AND friend_phone = ?))";
        $blockFriendStmt = $conn->prepare($blockFriendSql);
        $blockFriendStmt->execute([$userPhone, $blockedPhone, $blockedPhone, $userPhone]);
        
        sendSuccessResponse(['blocked' => true], 'User blocked successfully');
    } else {
        sendErrorResponse('Failed to block user', 'Database error', 500);
    }
}
?> 