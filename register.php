<?php
// Bao gồm file config
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Chỉ cho phép POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Method not allowed', 405);
    exit;
}

try {
    // Lấy kết nối database
    $conn = getDatabaseConnection();
    
    // Lấy dữ liệu JSON từ request body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Debug: log input data
    error_log('Register input: ' . $input);
    
    // Kiểm tra JSON có hợp lệ không
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse('Invalid JSON format');
        exit;
    }
    
    // Chuẩn bị các trường dữ liệu theo cấu trúc bảng user (tất cả đều optional)
    $userName = isset($data['userName']) ? sanitizeInput($data['userName']) : null;
    $email = isset($data['email']) ? sanitizeInput($data['email']) : null;
    $phone = isset($data['phone']) ? sanitizeInput($data['phone']) : null; // Thêm field phone
    $age = isset($data['age']) ? (int)$data['age'] : null;
    $gender = isset($data['gender']) ? sanitizeInput($data['gender']) : null;
    $blood = isset($data['blood']) ? sanitizeInput($data['blood']) : null;
    $chronic_diseases = isset($data['chronic_diseases']) ? sanitizeInput($data['chronic_diseases']) : null;
    $allergies = isset($data['allergies']) ? sanitizeInput($data['allergies']) : null;
    $premium_status = isset($data['premium_status']) ? (bool)$data['premium_status'] : false;
    $premium_start_date = isset($data['premium_start_date']) && $data['premium_start_date'] ? 
        date('Y-m-d H:i:s', strtotime($data['premium_start_date'])) : null;
    $premium_end_date = isset($data['premium_end_date']) && $data['premium_end_date'] ? 
        date('Y-m-d H:i:s', strtotime($data['premium_end_date'])) : null;
    $notifications = isset($data['notifications']) ? (bool)$data['notifications'] : true;
    $relative_phone = isset($data['relative_phone']) ? sanitizeInput($data['relative_phone']) : null;
    $home_address = isset($data['home_address']) ? sanitizeInput($data['home_address']) : null;
    
    // Chuẩn bị câu SQL INSERT
    $sql = "INSERT INTO user (
        userName, 
        email, 
        phone,
        age, 
        gender, 
        blood, 
        chronic_diseases, 
        allergies, 
        premium_status,
        premium_start_date,
        premium_end_date,
        notifications,
        relative_phone,
        home_address
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        sendErrorResponse('Database prepare error');
        exit;
    }
    
    // Execute với parameters
    $result = $stmt->execute([
        $userName, 
        $email, 
        $phone,  // Thêm phone vào parameters
        $age, 
        $gender, 
        $blood, 
        $chronic_diseases, 
        $allergies, 
        $premium_status,
        $premium_start_date,
        $premium_end_date,
        $notifications,
        $relative_phone,
        $home_address
    ]);
    
    // Thực thi query
    if ($result) {
        $userId = $conn->lastInsertId();
        
        // Tạo response data
        $responseData = [
            'user' => [
                'userId' => (int)$userId,
                'userName' => $userName,
                'email' => $email,
                'phone' => $phone,  // Thêm phone vào response
                'age' => $age,
                'gender' => $gender,
                'blood' => $blood,
                'chronic_diseases' => $chronic_diseases,
                'allergies' => $allergies,
                'premium_status' => $premium_status,
                'premium_start_date' => $premium_start_date,
                'premium_end_date' => $premium_end_date,
                'notifications' => $notifications,
                'relative_phone' => $relative_phone,
                'home_address' => $home_address,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        // Debug: log response data
        error_log('Register response: ' . json_encode($responseData));
        
        sendSuccessResponse($responseData, 'User registered successfully');
        
    } else {
        sendErrorResponse('Failed to register user');
    }
    
} catch (Exception $e) {
    sendErrorResponse('Server error: ' . $e->getMessage());
}
?>
