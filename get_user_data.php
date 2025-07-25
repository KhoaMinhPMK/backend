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
    error_log('Get user data input: ' . $input);
    
    // Kiểm tra JSON có hợp lệ không
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse('Invalid JSON format', 'Bad request', 400);
        exit;
    }
    
    // Email là bắt buộc để lấy thông tin user
    $email = isset($data['email']) ? sanitizeInput($data['email']) : null;
    
    if (!$email) {
        sendErrorResponse('Email is required to get user data', 'Bad request', 400);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendErrorResponse('Invalid email format', 'Bad request', 400);
        exit;
    }
    
    // Tìm user theo email
    $sql = "SELECT * FROM user WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        sendErrorResponse('Database prepare error', 'Internal server error', 500);
        exit;
    }
    
    // Execute với parameter
    $result = $stmt->execute([$email]);
    
    if ($result) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // User tồn tại - trả về thông tin user
            $responseData = [
                'user' => [
                    'userId' => (int)$user['userId'],
                    'userName' => $user['userName'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'age' => $user['age'],
                    'gender' => $user['gender'],
                    'blood' => $user['blood'],
                    'chronic_diseases' => $user['chronic_diseases'],
                    'allergies' => $user['allergies'],
                    'premium_status' => (bool)$user['premium_status'],
                    'premium_start_date' => $user['premium_start_date'],
                    'premium_end_date' => $user['premium_end_date'],
                    'notifications' => (bool)$user['notifications'],
                    'relative_phone' => $user['relative_phone'],
                    'home_address' => $user['home_address'],
                    // Health fields
                    'hypertension' => (int)$user['hypertension'],
                    'heart_disease' => (int)$user['heart_disease'],
                    'ever_married' => $user['ever_married'],
                    'work_type' => $user['work_type'],
                    'residence_type' => $user['residence_type'],
                    'avg_glucose_level' => $user['avg_glucose_level'] ? (float)$user['avg_glucose_level'] : null,
                    'bmi' => $user['bmi'] ? (float)$user['bmi'] : null,
                    'smoking_status' => $user['smoking_status'],
                    'stroke' => (int)$user['stroke'],
                    'created_at' => $user['created_at'],
                    'updated_at' => $user['updated_at']
                ]
            ];
            
            // Debug: log response data
            error_log('Get user data response: ' . json_encode($responseData));
            
            sendSuccessResponse($responseData, 'User data retrieved successfully');
            
        } else {
            sendErrorResponse('User not found with this email', 'Not found', 404);
        }
    } else {
        sendErrorResponse('Failed to query database', 'Database error', 500);
    }
    
} catch (Exception $e) {
    error_log('Get user data error: ' . $e->getMessage());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?>
