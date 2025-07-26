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
    error_log('Search friend input: ' . $input);
    
    // Kiểm tra JSON có hợp lệ không
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse('Invalid JSON format', 'Bad request', 400);
        exit;
    }
    
    // Lấy phone và current user email (để loại trừ user hiện tại)
    $phone = isset($data['phone']) ? sanitizeInput($data['phone']) : null;
    $currentUserEmail = isset($data['currentUserEmail']) ? sanitizeInput($data['currentUserEmail']) : null;
    
    if (!$phone) {
        sendErrorResponse('Phone number is required', 'Bad request', 400);
        exit;
    }
    
    // Validate phone format (basic validation)
    $phone = preg_replace('/[^0-9+]/', '', $phone); // Loại bỏ ký tự không phải số và dấu +
    
    if (strlen($phone) < 10) {
        sendErrorResponse('Invalid phone number format', 'Bad request', 400);
        exit;
    }
    
    // Tìm user theo phone number (loại trừ user hiện tại)
    $sql = "SELECT userId, userName, email, phone FROM user WHERE phone LIKE ? AND email != ? LIMIT 10";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        sendErrorResponse('Database prepare error', 'Internal server error', 500);
        exit;
    }
    
    // Tìm kiếm với wildcard để match partial phone numbers
    $searchPhone = '%' . $phone . '%';
    $currentUserEmailParam = $currentUserEmail ?: '';
    
    $result = $stmt->execute([$searchPhone, $currentUserEmailParam]);
    
    if ($result) {
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format response data
        $friendResults = [];
        
        foreach ($users as $user) {
            // Tạo avatar từ 2 chữ đầu của tên
            $nameParts = explode(' ', trim($user['userName']));
            $avatar = '';
            
            if (count($nameParts) >= 2) {
                // Nếu có ít nhất 2 từ, lấy chữ đầu của 2 từ đầu
                $avatar = mb_substr($nameParts[0], 0, 1, 'UTF-8') . mb_substr($nameParts[1], 0, 1, 'UTF-8');
            } else {
                // Nếu chỉ có 1 từ, lấy 2 chữ đầu của từ đó
                $avatar = mb_substr($user['userName'], 0, 2, 'UTF-8');
            }
            
            $friendResults[] = [
                'userId' => (int)$user['userId'],
                'userName' => $user['userName'],
                'phone' => $user['phone'],
                'avatar' => strtoupper($avatar), // Viết hoa
                'isFound' => true
            ];
        }
        
        $responseData = [
            'results' => $friendResults,
            'total' => count($friendResults),
            'searchQuery' => $phone
        ];
        
        // Debug: log response data
        error_log('Search friend response: ' . json_encode($responseData));
        
        if (count($friendResults) > 0) {
            sendSuccessResponse($responseData, 'Friends found successfully');
        } else {
            sendSuccessResponse(['results' => [], 'total' => 0, 'searchQuery' => $phone], 'No friends found with this phone number');
        }
        
    } else {
        sendErrorResponse('Failed to search friends', 'Database error', 500);
    }
    
} catch (Exception $e) {
    error_log('Search friend error: ' . $e->getMessage());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?>

