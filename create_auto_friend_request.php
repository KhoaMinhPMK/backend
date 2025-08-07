<?php
require_once 'config.php';

// DEBUG: Log raw request data
error_log("=== CREATE_AUTO_FRIEND_REQUEST.PHP CALLED ===");
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
    $fromPhone = isset($data['from_phone']) ? sanitizeInput($data['from_phone']) : null;
    $toPhone = isset($data['to_phone']) ? sanitizeInput($data['to_phone']) : null;
    
    error_log("Parameters - fromPhone: $fromPhone, toPhone: $toPhone");
    
    if (!$fromPhone || !$toPhone) {
        error_log("❌ Missing required parameters");
        sendErrorResponse('from_phone and to_phone are required', 'Bad request', 400);
        exit;
    }
    
    // Check if users exist
    $getUserSql = "SELECT userId, userName FROM user WHERE phone = ?";
    $stmt = $conn->prepare($getUserSql);
    
    $stmt->execute([$fromPhone]);
    $fromUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt->execute([$toPhone]);
    $toUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$fromUser || !$toUser) {
        error_log("❌ One or both users not found");
        sendErrorResponse('One or both users not found', 'Not found', 404);
        exit;
    }
    
    error_log("✅ Users found - From: " . $fromUser['userName'] . ", To: " . $toUser['userName']);
    
    // Check if friendship already exists
    $checkFriendshipSql = "SELECT * FROM friend_status WHERE user_phone = ? AND friend_phone = ? AND status = 'accepted'";
    $stmt = $conn->prepare($checkFriendshipSql);
    $stmt->execute([$fromPhone, $toPhone]);
    $existingFriendship = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingFriendship) {
        error_log("✅ Friendship already exists");
        sendSuccessResponse(['request_id' => null, 'message' => 'Friendship already exists'], 'Friendship already exists');
        exit;
    }
    
    // Check if friend request already exists
    $checkRequestSql = "SELECT * FROM friend_requests WHERE from_phone = ? AND to_phone = ? AND status = 'pending'";
    $stmt = $conn->prepare($checkRequestSql);
    $stmt->execute([$fromPhone, $toPhone]);
    $existingRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingRequest) {
        error_log("✅ Friend request already exists");
        sendSuccessResponse(['request_id' => $existingRequest['id'], 'message' => 'Friend request already exists'], 'Friend request already exists');
        exit;
    }
    
    // Create auto friend request
    $createRequestSql = "INSERT INTO friend_requests (from_phone, to_phone, message, status, created_at) VALUES (?, ?, ?, 'auto_pending', NOW())";
    $stmt = $conn->prepare($createRequestSql);
    $autoMessage = "Tự động kết bạn từ hệ thống Premium";
    $stmt->execute([$fromPhone, $toPhone, $autoMessage]);
    
    $requestId = $conn->lastInsertId();
    error_log("✅ Auto friend request created with ID: $requestId");
    
    $responseData = [
        'request_id' => $requestId,
        'from_phone' => $fromPhone,
        'to_phone' => $toPhone,
        'message' => $autoMessage,
        'status' => 'auto_pending'
    ];
    
    error_log("✅ Create auto friend request completed successfully");
    sendSuccessResponse($responseData, 'Auto friend request created successfully');
    
} catch (Exception $e) {
    error_log("❌ Exception in create_auto_friend_request.php: " . $e->getMessage());
    sendErrorResponse('Internal server error', 'Internal server error', 500);
}
?>
