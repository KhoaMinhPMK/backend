<?php
require_once 'config.php';

// DEBUG: Log raw request data
error_log("=== GET_FRIENDS.PHP CALLED ===");
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
    
    $userPhone = isset($data['user_phone']) ? sanitizeInput($data['user_phone']) : null;
    
    error_log("Parameters - userPhone: $userPhone");
    
    if (!$userPhone) {
        error_log("❌ Missing required parameters");
        sendErrorResponse('User phone number is required', 'Bad request', 400);
        exit;
    }
    
    // Validate phone format
    $userPhone = preg_replace('/[^0-9+]/', '', $userPhone);
    
    if (strlen($userPhone) < 10) {
        sendErrorResponse('Invalid phone number format', 'Bad request', 400);
        exit;
    }
    
    // Lấy danh sách bạn bè từ friend_status
    $sql = "SELECT 
                fs.friend_phone,
                u.userId,
                u.userName,
                u.email,
                u.phone,
                u.age,
                u.gender,
                fs.status,
                fs.responded_at
            FROM friend_status fs
            JOIN user u ON fs.friend_phone = u.phone
            WHERE fs.user_phone = ? AND fs.status = 'accepted'
            ORDER BY fs.responded_at DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("❌ Database prepare error");
        sendErrorResponse('Database prepare error', 'Internal server error', 500);
        exit;
    }
    
    $stmt->execute([$userPhone]);
    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("✅ Found " . count($friends) . " friends for user: $userPhone");
    
    // Format response
    $formattedFriends = array_map(function($friend) {
        // Tạo avatar từ tên
        $nameParts = explode(' ', trim($friend['userName']));
        $avatar = '';
        
        if (count($nameParts) >= 2) {
            $avatar = mb_substr($nameParts[0], 0, 1, 'UTF-8') . mb_substr($nameParts[1], 0, 1, 'UTF-8');
        } else {
            $avatar = mb_substr($friend['userName'], 0, 2, 'UTF-8');
        }
        
        return [
            'userId' => (int)$friend['userId'],
            'userName' => $friend['userName'],
            'phone' => $friend['phone'],
            'email' => $friend['email'],
            'avatar' => strtoupper($avatar),
            'age' => $friend['age'] ? (int)$friend['age'] : null,
            'gender' => $friend['gender'],
            'status' => $friend['status'],
            'respondedAt' => $friend['responded_at']
        ];
    }, $friends);
    
    $responseData = [
        'friends' => $formattedFriends,
        'total' => count($formattedFriends)
    ];
    
    error_log("✅ Get friends completed successfully");
    sendSuccessResponse($responseData, 'Friends list retrieved successfully');
    
} catch (Exception $e) {
    error_log('❌ Get friends error: ' . $e->getMessage());
    error_log('❌ Stack trace: ' . $e->getTraceAsString());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?>
