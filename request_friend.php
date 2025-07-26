<?php
// Bao gồm file config
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Chỉ cho phép POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Method not allowed', 'Method not allowed', 405);
    exit;
}

try {
    // Lấy kết nối database
    $conn = getDatabaseConnection();
    
    // Lấy dữ liệu JSON từ request body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Debug: log input data
    error_log('Request friend input: ' . $input);
    
    // Kiểm tra JSON có hợp lệ không
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse('Invalid JSON format', 'Bad request', 400);
        exit;
    }
    
    // Lấy parameters
    $fromPhone = isset($data['from_phone']) ? sanitizeInput($data['from_phone']) : null;
    $toPhone = isset($data['to_phone']) ? sanitizeInput($data['to_phone']) : null;
    $message = isset($data['message']) ? sanitizeInput($data['message']) : null;
    
    if (!$fromPhone || !$toPhone) {
        sendErrorResponse('Both from_phone and to_phone are required', 'Bad request', 400);
        exit;
    }
    
    // Validate phone format
    $fromPhone = preg_replace('/[^0-9+]/', '', $fromPhone);
    $toPhone = preg_replace('/[^0-9+]/', '', $toPhone);
    
    if (strlen($fromPhone) < 10 || strlen($toPhone) < 10) {
        sendErrorResponse('Invalid phone number format', 'Bad request', 400);
        exit;
    }
    
    // Không thể kết bạn với chính mình
    if ($fromPhone === $toPhone) {
        sendErrorResponse('Cannot send friend request to yourself', 'Bad request', 400);
        exit;
    }
    
    // Kiểm tra xem to_phone có tồn tại trong hệ thống không
    $checkUserSql = "SELECT userId, userName FROM user WHERE phone = ? LIMIT 1";
    $checkUserStmt = $conn->prepare($checkUserSql);
    $checkUserStmt->execute([$toPhone]);
    $toUser = $checkUserStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$toUser) {
        sendErrorResponse('Target phone number does not exist in system', 'Not found', 404);
        exit;
    }
    
    // Lấy thông tin người gửi
    $checkFromUserSql = "SELECT userId, userName FROM user WHERE phone = ? LIMIT 1";
    $checkFromUserStmt = $conn->prepare($checkFromUserSql);
    $checkFromUserStmt->execute([$fromPhone]);
    $fromUser = $checkFromUserStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$fromUser) {
        sendErrorResponse('Your phone number does not exist in system', 'Not found', 404);
        exit;
    }
    
    // Kiểm tra xem đã là bạn bè chưa (trong friend_status)
    $checkFriendSql = "SELECT * FROM friend_status WHERE 
                       (user_phone = ? AND friend_phone = ? AND status = 'accepted') OR 
                       (user_phone = ? AND friend_phone = ? AND status = 'accepted') 
                       LIMIT 1";
    $checkFriendStmt = $conn->prepare($checkFriendSql);
    $checkFriendStmt->execute([$fromPhone, $toPhone, $toPhone, $fromPhone]);
    $existingFriendship = $checkFriendStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingFriendship) {
        sendErrorResponse('You are already friends', 'Conflict', 409);
        exit;
    }
    
    // Kiểm tra xem có pending request nào không (chưa expired)
    $checkRequestSql = "SELECT * FROM friend_requests WHERE 
                        ((from_phone = ? AND to_phone = ?) OR (from_phone = ? AND to_phone = ?))
                        AND status = 'pending' 
                        AND expires_at > NOW()
                        ORDER BY sent_at DESC
                        LIMIT 1";
    $checkRequestStmt = $conn->prepare($checkRequestSql);
    $checkRequestStmt->execute([$fromPhone, $toPhone, $toPhone, $fromPhone]);
    $existingRequest = $checkRequestStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingRequest) {
        if ($existingRequest['from_phone'] === $fromPhone) {
            // Đã gửi request rồi - trả về thông tin để hiển thị modal
            sendSuccessResponse([
                'alreadySent' => true,
                'canCancel' => true,
                'message' => 'Friend request already sent',
                'existingRequest' => [
                    'id' => $existingRequest['id'],
                    'from_phone' => $existingRequest['from_phone'],
                    'to_phone' => $existingRequest['to_phone'],
                    'to_name' => $toUser['userName'],
                    'message' => $existingRequest['message'],
                    'sent_at' => $existingRequest['sent_at'],
                    'expires_at' => $existingRequest['expires_at']
                ]
            ], 'Friend request already sent');
            exit;
        } else {
            // Người kia đã gửi request cho mình → có thể accept luôn
            sendSuccessResponse([
                'canAccept' => true,
                'message' => 'This person has already sent you a friend request',
                'existingRequest' => [
                    'id' => $existingRequest['id'],
                    'from_phone' => $existingRequest['from_phone'],
                    'from_name' => $fromUser['userName'], // Tên người đã gửi request
                    'message' => $existingRequest['message'],
                    'sent_at' => $existingRequest['sent_at'],
                    'expires_at' => $existingRequest['expires_at']
                ]
            ], 'Existing friend request found');
            exit;
        }
    }
    
    // Tạo friend request mới
    $insertSql = "INSERT INTO friend_requests (from_phone, to_phone, message, status, expires_at) 
                  VALUES (?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL 7 DAY))";
    $insertStmt = $conn->prepare($insertSql);
    
    if (!$insertStmt) {
        sendErrorResponse('Database prepare error', 'Internal server error', 500);
        exit;
    }
    
    $result = $insertStmt->execute([$fromPhone, $toPhone, $message]);
    
    if ($result) {
        $requestId = $conn->lastInsertId();
        
        // Lấy thông tin chi tiết của request vừa tạo
        $getRequestSql = "SELECT * FROM friend_requests WHERE id = ?";
        $getRequestStmt = $conn->prepare($getRequestSql);
        $getRequestStmt->execute([$requestId]);
        $newRequest = $getRequestStmt->fetch(PDO::FETCH_ASSOC);
        
        $responseData = [
            'requestId' => (int)$requestId,
            'from_phone' => $fromPhone,
            'from_name' => $fromUser['userName'],
            'to_phone' => $toPhone,
            'to_name' => $toUser['userName'],
            'message' => $message,
            'status' => 'pending',
            'sent_at' => $newRequest['sent_at'],
            'expires_at' => $newRequest['expires_at']
        ];
        
        // --- Bắt đầu quy trình thông báo ---
        
        // 1. Tạo payload để lưu vào DB và gửi đi
        $notification_data = [
            'type' => 'friend_request_received',
            'title' => 'Lời mời kết bạn mới',
            'body' => 'Bạn có một lời mời kết bạn mới từ ' . $fromUser['userName'] . '.',
            'details' => [ // Đổi tên 'data' thành 'details' để tránh trùng với tên cột
                'from_phone' => $fromPhone,
                'from_name' => $fromUser['userName'],
                'request_id' => $requestId,
                'message' => $message
            ]
        ];

        // 2. Lưu thông báo vào bảng `notifications`
        $insertNotifSql = "INSERT INTO notifications (user_phone, type, title, body, data) VALUES (?, ?, ?, ?, ?)";
        $insertNotifStmt = $conn->prepare($insertNotifSql);
        
        if ($insertNotifStmt) {
            $insertNotifStmt->execute([
                $toPhone,
                $notification_data['type'],
                $notification_data['title'],
                $notification_data['body'],
                json_encode($notification_data['details'])
            ]);
            $notificationId = $conn->lastInsertId();
            
            // 3. Lấy thông báo vừa tạo để gửi qua socket
            $getNotifSql = "SELECT * FROM notifications WHERE id = ?";
            $getNotifStmt = $conn->prepare($getNotifSql);
            $getNotifStmt->execute([$notificationId]);
            $fullNotificationPayload = $getNotifStmt->fetch(PDO::FETCH_ASSOC);

            // Gửi thông báo real-time với đầy đủ thông tin từ DB
            if ($fullNotificationPayload) {
                send_socket_notification($toPhone, $fullNotificationPayload);
            }
        } else {
            logError("Database prepare error for inserting notification.", ['to_phone' => $toPhone]);
        }
        
        // --- Kết thúc quy trình thông báo ---
        
        // Debug: log response data
        error_log('Request friend response: ' . json_encode($responseData));
        
        sendSuccessResponse($responseData, 'Friend request sent successfully');
        
    } else {
        sendErrorResponse('Failed to send friend request', 'Database error', 500);
    }
    
} catch (Exception $e) {
    error_log('Request friend error: ' . $e->getMessage());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?> 