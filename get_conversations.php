<?php
require_once 'config.php';

// Tắt error output để tránh ảnh hưởng đến JSON response
error_reporting(0);
ini_set('display_errors', 0);

setCorsHeaders();

error_log("🔍 get_conversations.php - Request started");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("❌ get_conversations.php - Method not allowed: " . $_SERVER['REQUEST_METHOD']);
    sendErrorResponse('Method not allowed', 'Method not allowed', 405);
    exit;
}

try {
    error_log("🔍 get_conversations.php - Getting database connection");
    $conn = getDatabaseConnection();
    error_log("✅ get_conversations.php - Database connected successfully");
    
    $input = file_get_contents('php://input');
    error_log("🔍 get_conversations.php - Raw input: " . substr($input, 0, 200));
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("❌ get_conversations.php - JSON decode error: " . json_last_error_msg());
        sendErrorResponse('Invalid JSON format', 'Bad request', 400);
        exit;
    }
    
    error_log("✅ get_conversations.php - JSON decoded successfully");
    
    $userPhone = isset($data['user_phone']) ? sanitizeInput($data['user_phone']) : null;
    error_log("🔍 get_conversations.php - User phone: " . ($userPhone ?? 'null'));
    
    if (!$userPhone) {
        error_log("❌ get_conversations.php - User phone is required");
        sendErrorResponse('User phone is required', 'Bad request', 400);
        exit;
    }
    
    error_log("🔍 get_conversations.php - Building simple SQL query");
    
    // Đơn giản: Lấy tất cả conversations mà user tham gia
    $sql = "SELECT * FROM conversations WHERE participant1_phone = ? OR participant2_phone = ? ORDER BY last_activity DESC";
    
    error_log("🔍 get_conversations.php - SQL query: " . $sql);
    error_log("🔍 get_conversations.php - Parameters: " . json_encode([$userPhone, $userPhone]));
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("❌ get_conversations.php - SQL prepare failed: " . json_encode($conn->errorInfo()));
        sendErrorResponse('Database prepare error', 'Internal server error', 500);
        exit;
    }
    
    $executeResult = $stmt->execute([$userPhone, $userPhone]);
    if (!$executeResult) {
        error_log("❌ get_conversations.php - SQL execute failed: " . json_encode($stmt->errorInfo()));
        sendErrorResponse('Database execute error', 'Internal server error', 500);
        exit;
    }
    
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("✅ get_conversations.php - Query executed successfully, found " . count($conversations) . " conversations");
    
    error_log("🔍 get_conversations.php - Formatting conversations data");
    
    // Format dữ liệu trả về - lấy tên thật từ database
    $formattedConversations = [];
    foreach ($conversations as $conv) {
        // Xác định người tham gia khác
        $otherParticipantPhone = ($conv['participant1_phone'] == $userPhone) ? $conv['participant2_phone'] : $conv['participant1_phone'];
        
        // Lấy thông tin người tham gia khác từ database
        $getUserNameSql = "SELECT userName FROM user WHERE phone = ? LIMIT 1";
        $userStmt = $conn->prepare($getUserNameSql);
        $userStmt->execute([$otherParticipantPhone]);
        $otherUser = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        // Tạo avatar từ tên (logic từ searchfriend.php)
        $userName = $otherUser ? $otherUser['userName'] : 'Người dùng';
        $nameParts = explode(' ', trim($userName));
        $avatar = '';
        
        if (count($nameParts) >= 2) {
            // Nếu có ít nhất 2 từ, lấy chữ đầu của 2 từ đầu
            $avatar = mb_substr($nameParts[0], 0, 1, 'UTF-8') . mb_substr($nameParts[1], 0, 1, 'UTF-8');
        } else {
            // Nếu chỉ có 1 từ, lấy 2 chữ đầu của từ đó
            $avatar = mb_substr($userName, 0, 2, 'UTF-8');
        }
        
        $formattedConversations[] = [
            'id' => $conv['id'],
            'otherParticipantPhone' => $otherParticipantPhone,
            'otherParticipantName' => $userName,
            'lastMessage' => 'Chưa có tin nhắn',
            'lastMessageTime' => $conv['last_activity'],
            'avatar' => strtoupper($avatar) // Viết hoa như searchfriend.php
        ];
    }
    
    error_log("✅ get_conversations.php - Formatted " . count($formattedConversations) . " conversations");
    error_log("🔍 get_conversations.php - Formatted data: " . json_encode($formattedConversations));
    
    $responseData = [
        'conversations' => $formattedConversations,
        'count' => count($formattedConversations)
    ];
    
    error_log("🔍 get_conversations.php - Sending success response");
    sendSuccessResponse($responseData, 'Conversations retrieved successfully');
    error_log("✅ get_conversations.php - Response sent successfully");
    
} catch (Exception $e) {
    error_log('❌ get_conversations.php - Exception caught: ' . $e->getMessage());
    error_log('❌ get_conversations.php - Stack trace: ' . $e->getTraceAsString());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?> 