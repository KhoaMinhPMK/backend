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
    
    if (!$userPhone) {
        sendErrorResponse('User phone number is required', 'Bad request', 400);
        exit;
    }
    
    // Log request để debug
    error_log('🔄 Get friends request for phone: ' . $userPhone);
    
    // Query lấy danh sách bạn bè đã accept (cả 2 chiều trong user_friend table)
    $sql = "
        (SELECT 
            u.userName, 
            u.phone, 
            uf.created_at as friend_since,
            'accepted' as status
        FROM user_friend uf
        JOIN user u ON uf.user_phone_2 = u.phone  
        WHERE uf.user_phone_1 = ? AND uf.status = 'accepted')
        
        UNION
        
        (SELECT 
            u.userName, 
            u.phone, 
            uf.created_at as friend_since,
            'accepted' as status
        FROM user_friend uf
        JOIN user u ON uf.user_phone_1 = u.phone
        WHERE uf.user_phone_2 = ? AND uf.status = 'accepted')
        
        ORDER BY friend_since DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userPhone, $userPhone]);
    
    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dữ liệu cho frontend với avatar generation
    $formattedFriends = array_map(function($friend) {
        // Generate avatar từ tên (2 chữ cái đầu)
        $nameWords = explode(' ', trim($friend['userName']));
        $avatar = '';
        if (count($nameWords) >= 2) {
            // Lấy chữ cái đầu của từ đầu và từ cuối
            $avatar = strtoupper(substr($nameWords[0], 0, 1) . substr($nameWords[count($nameWords)-1], 0, 1));
        } else {
            // Nếu chỉ có 1 từ, lấy 2 chữ cái đầu
            $avatar = strtoupper(substr($friend['userName'], 0, 2));
        }
        
        return [
            'userName' => $friend['userName'],
            'phone' => $friend['phone'],
            'avatar' => $avatar,
            'friendSince' => $friend['friend_since'],
            'status' => $friend['status']
        ];
    }, $friends);
    
    error_log('✅ Get friends result: ' . count($formattedFriends) . ' friends found');
    
    sendSuccessResponse([
        'friends' => $formattedFriends,
        'total' => count($formattedFriends)
    ], 'Lấy danh sách bạn bè thành công');
    
} catch (Exception $e) {
    error_log('❌ Get friends error: ' . $e->getMessage());
    error_log('❌ Stack trace: ' . $e->getTraceAsString());
    sendErrorResponse('Lỗi server: ' . $e->getMessage(), 'Internal server error', 500);
}
?> 