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
    
    // Start transaction for data consistency
    $conn->beginTransaction();
    error_log("✅ Transaction started");
    
    try {
        // Check if users exist
        $getUserSql = "SELECT userId, userName FROM user WHERE phone = ?";
        $stmt = $conn->prepare($getUserSql);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare user query: " . print_r($conn->errorInfo(), true));
        }
        
        $stmt->execute([$fromPhone]);
        $fromUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt->execute([$toPhone]);
        $toUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$fromUser || !$toUser) {
            throw new Exception("One or both users not found - fromUser: " . ($fromUser ? 'found' : 'not found') . ", toUser: " . ($toUser ? 'found' : 'not found'));
        }
        
        error_log("✅ Users found - From: " . $fromUser['userName'] . ", To: " . $toUser['userName']);
        
        // Check database schema for friend_requests table
        $checkTableSql = "DESCRIBE friend_requests";
        $stmt = $conn->prepare($checkTableSql);
        $stmt->execute();
        $tableStructure = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("✅ friend_requests table structure: " . json_encode($tableStructure));
        
        // Check if friendship already exists
        $checkFriendshipSql = "SELECT * FROM friend_status WHERE user_phone = ? AND friend_phone = ? AND status = 'accepted'";
        $stmt = $conn->prepare($checkFriendshipSql);
        $stmt->execute([$fromPhone, $toPhone]);
        $existingFriendship = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingFriendship) {
            $conn->commit();
            error_log("✅ Friendship already exists");
            sendSuccessResponse(['request_id' => null, 'message' => 'Friendship already exists'], 'Friendship already exists');
            exit;
        }
        
        // Check if friend request already exists
        $checkRequestSql = "SELECT * FROM friend_requests WHERE from_phone = ? AND to_phone = ? AND status IN ('pending', 'auto_pending')";
        $stmt = $conn->prepare($checkRequestSql);
        $stmt->execute([$fromPhone, $toPhone]);
        $existingRequest = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingRequest) {
            $conn->commit();
            error_log("✅ Friend request already exists");
            sendSuccessResponse(['request_id' => $existingRequest['id'], 'message' => 'Friend request already exists'], 'Friend request already exists');
            exit;
        }
        
        // Create auto friend request - use 'pending' status instead of 'auto_pending'
        $createRequestSql = "INSERT INTO friend_requests (from_phone, to_phone, message, status) VALUES (?, ?, ?, 'pending')";
        $stmt = $conn->prepare($createRequestSql);
        $autoMessage = "Tự động kết bạn từ hệ thống Premium";
        
        if (!$stmt->execute([$fromPhone, $toPhone, $autoMessage])) {
            throw new Exception("Failed to execute INSERT query: " . print_r($stmt->errorInfo(), true));
        }
        
        $requestId = $conn->lastInsertId();
        error_log("✅ Auto friend request created with ID: $requestId");
        
        $conn->commit();
        error_log("✅ Transaction committed successfully");
        
        $responseData = [
            'request_id' => $requestId,
            'from_phone' => $fromPhone,
            'to_phone' => $toPhone,
            'message' => $autoMessage,
            'status' => 'pending'
        ];
        
        error_log("✅ Create auto friend request completed successfully");
        sendSuccessResponse($responseData, 'Auto friend request created successfully');
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("❌ Transaction rolled back due to error: " . $e->getMessage());
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("❌ Exception in create_auto_friend_request.php: " . $e->getMessage());
    error_log("❌ Stack trace: " . $e->getTraceAsString());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?>
