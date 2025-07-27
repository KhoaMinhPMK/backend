<?php
require_once 'config.php';

setCorsHeaders();

// Chỉ cho phép POST request
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
    $friendPhone = isset($data['friend_phone']) ? sanitizeInput($data['friend_phone']) : null;
    
    if (!$userPhone || !$friendPhone) {
        sendErrorResponse('Both user_phone and friend_phone are required', 'Bad request', 400);
        exit;
    }
    
    // Validate không thể tạo conversation với chính mình
    if ($userPhone === $friendPhone) {
        sendErrorResponse('Cannot create conversation with yourself', 'Bad request', 400);
        exit;
    }
    
    // Log request để debug
    error_log('🔄 Create conversation request: ' . $userPhone . ' -> ' . $friendPhone);
    
    // Kiểm tra xem 2 user có phải bạn bè không (required để tạo conversation)
    $friendCheckSql = "
        SELECT COUNT(*) as is_friend 
        FROM user_friend 
        WHERE ((user_phone_1 = ? AND user_phone_2 = ?) OR (user_phone_1 = ? AND user_phone_2 = ?)) 
        AND status = 'accepted'
    ";
    
    $stmt = $conn->prepare($friendCheckSql);
    $stmt->execute([$userPhone, $friendPhone, $friendPhone, $userPhone]);
    $friendResult = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($friendResult['is_friend'] == 0) {
        sendErrorResponse('You can only create conversations with accepted friends', 'Forbidden', 403);
        exit;
    }
    
    // Generate conversation ID: smaller_phone|larger_phone để consistent
    $phone1 = min($userPhone, $friendPhone);
    $phone2 = max($userPhone, $friendPhone);
    $conversationId = $phone1 . '|' . $phone2;
    
    error_log('💬 Generated conversation ID: ' . $conversationId);
    
    // Check xem conversation đã tồn tại chưa
    $existCheckSql = "SELECT id FROM conversations WHERE id = ?";
    $stmt = $conn->prepare($existCheckSql);
    $stmt->execute([$conversationId]);
    $existingConv = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingConv) {
        // Conversation đã tồn tại, chỉ update last_activity
        $updateSql = "UPDATE conversations SET last_activity = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->execute([$conversationId]);
        
        error_log('✅ Conversation already exists, updated last_activity: ' . $conversationId);
        
        sendSuccessResponse([
            'conversationId' => $conversationId,
            'isNew' => false,
            'message' => 'Conversation already exists'
        ], 'Cuộc trò chuyện đã tồn tại');
        exit;
    }
    
    // Tạo conversation mới
    $createSql = "
        INSERT INTO conversations (id, participant1_phone, participant2_phone, last_activity, created_at) 
        VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ";
    
    $stmt = $conn->prepare($createSql);
    $stmt->execute([$conversationId, $phone1, $phone2]);
    
    if ($stmt->rowCount() > 0) {
        error_log('✅ New conversation created: ' . $conversationId);
        
        // Lấy thông tin người bạn để return
        $friendInfoSql = "SELECT userName FROM user WHERE phone = ?";
        $stmt = $conn->prepare($friendInfoSql);
        $stmt->execute([$friendPhone]);
        $friendInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        sendSuccessResponse([
            'conversationId' => $conversationId,
            'isNew' => true,
            'friendName' => $friendInfo['userName'] ?? 'Người dùng',
            'friendPhone' => $friendPhone
        ], 'Tạo cuộc trò chuyện thành công');
    } else {
        error_log('❌ Failed to create conversation: ' . $conversationId);
        sendErrorResponse('Failed to create conversation', 'Internal server error', 500);
    }
    
} catch (Exception $e) {
    error_log('❌ Create conversation error: ' . $e->getMessage());
    error_log('❌ Stack trace: ' . $e->getTraceAsString());
    sendErrorResponse('Lỗi server: ' . $e->getMessage(), 'Internal server error', 500);
}
?> 