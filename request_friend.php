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
    
    // Lấy phone numbers
    $userPhone = isset($data['user_phone']) ? sanitizeInput($data['user_phone']) : null;
    $friendPhone = isset($data['friend_phone']) ? sanitizeInput($data['friend_phone']) : null;
    $notes = isset($data['notes']) ? sanitizeInput($data['notes']) : null;
    
    if (!$userPhone || !$friendPhone) {
        sendErrorResponse('Both user_phone and friend_phone are required', 'Bad request', 400);
        exit;
    }
    
    // Validate phone format
    $userPhone = preg_replace('/[^0-9+]/', '', $userPhone);
    $friendPhone = preg_replace('/[^0-9+]/', '', $friendPhone);
    
    if (strlen($userPhone) < 10 || strlen($friendPhone) < 10) {
        sendErrorResponse('Invalid phone number format', 'Bad request', 400);
        exit;
    }
    
    // Không thể kết bạn với chính mình
    if ($userPhone === $friendPhone) {
        sendErrorResponse('Cannot send friend request to yourself', 'Bad request', 400);
        exit;
    }
    
    // Kiểm tra xem friend_phone có tồn tại trong hệ thống không
    $checkUserSql = "SELECT userId, userName FROM user WHERE phone = ? LIMIT 1";
    $checkUserStmt = $conn->prepare($checkUserSql);
    $checkUserStmt->execute([$friendPhone]);
    $friendUser = $checkUserStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$friendUser) {
        sendErrorResponse('Friend phone number does not exist in system', 'Not found', 404);
        exit;
    }
    
    // Kiểm tra trạng thái hiện tại của mối quan hệ
    $checkSql = "SELECT * FROM friend_status WHERE 
                 (user_phone = ? AND friend_phone = ?) OR 
                 (user_phone = ? AND friend_phone = ?) 
                 LIMIT 1";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([$userPhone, $friendPhone, $friendPhone, $userPhone]);
    $existingRelation = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingRelation) {
        // Đã có mối quan hệ
        switch ($existingRelation['status']) {
            case 'pending':
                if ($existingRelation['requester_phone'] === $userPhone) {
                    sendErrorResponse('Friend request already sent and pending', 'Conflict', 409);
                } else {
                    // Người kia đã gửi request cho mình → có thể accept luôn
                    sendSuccessResponse([
                        'canAccept' => true,
                        'message' => 'This person has already sent you a friend request',
                        'existingRequest' => [
                            'id' => $existingRelation['id'],
                            'requester_phone' => $existingRelation['requester_phone'],
                            'requested_at' => $existingRelation['requested_at']
                        ]
                    ], 'Existing friend request found');
                }
                exit;
                
            case 'accepted':
                sendErrorResponse('You are already friends', 'Conflict', 409);
                exit;
                
            case 'blocked':
                sendErrorResponse('Cannot send friend request to this user', 'Forbidden', 403);
                exit;
                
            case 'rejected':
                // Có thể gửi lại request sau khi bị từ chối
                // Cập nhật request cũ thành pending
                $updateSql = "UPDATE friend_status SET 
                              status = 'pending', 
                              requester_phone = ?, 
                              requested_at = CURRENT_TIMESTAMP,
                              responded_at = NULL,
                              notes = ?
                              WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateResult = $updateStmt->execute([$userPhone, $notes, $existingRelation['id']]);
                
                if ($updateResult) {
                    sendSuccessResponse([
                        'friendRequestId' => $existingRelation['id'],
                        'status' => 'pending',
                        'friendName' => $friendUser['userName'],
                        'message' => 'Friend request sent successfully (re-sent after rejection)'
                    ], 'Friend request re-sent successfully');
                } else {
                    sendErrorResponse('Failed to update friend request', 'Database error', 500);
                }
                exit;
        }
    }
    
    // Chưa có mối quan hệ → tạo mới
    $insertSql = "INSERT INTO friend_status (user_phone, friend_phone, status, requester_phone, notes) 
                  VALUES (?, ?, 'pending', ?, ?)";
    $insertStmt = $conn->prepare($insertSql);
    
    if (!$insertStmt) {
        sendErrorResponse('Database prepare error', 'Internal server error', 500);
        exit;
    }
    
    $result = $insertStmt->execute([$userPhone, $friendPhone, $userPhone, $notes]);
    
    if ($result) {
        $friendRequestId = $conn->lastInsertId();
        
        $responseData = [
            'friendRequestId' => (int)$friendRequestId,
            'user_phone' => $userPhone,
            'friend_phone' => $friendPhone,
            'status' => 'pending',
            'requester_phone' => $userPhone,
            'friendName' => $friendUser['userName'],
            'requested_at' => date('Y-m-d H:i:s'),
            'notes' => $notes
        ];
        
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