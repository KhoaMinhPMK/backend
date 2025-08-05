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
    $password = isset($data['password']) ? $data['password'] : null; // Thêm password
    $role = isset($data['role']) ? sanitizeInput($data['role']) : 'user'; // Thêm role
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
    
    // Validation
    if (!$userName) {
        sendErrorResponse('Tên người dùng là bắt buộc', 'Bad request', 400);
        exit;
    }
    
    if (!$email) {
        sendErrorResponse('Email là bắt buộc', 'Bad request', 400);
        exit;
    }
    
    if (!$phone) {
        sendErrorResponse('Số điện thoại là bắt buộc', 'Bad request', 400);
        exit;
    }
    
    if (!$password) {
        sendErrorResponse('Mật khẩu là bắt buộc', 'Bad request', 400);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendErrorResponse('Email không hợp lệ', 'Bad request', 400);
        exit;
    }
    
    // Validate phone format (basic validation)
    if (strlen($phone) < 10) {
        sendErrorResponse('Số điện thoại phải có ít nhất 10 chữ số', 'Bad request', 400);
        exit;
    }
    
    // Kiểm tra email đã tồn tại chưa
    $checkEmailSql = "SELECT userId FROM user WHERE email = ? LIMIT 1";
    $checkEmailStmt = $conn->prepare($checkEmailSql);
    $checkEmailStmt->execute([$email]);
    $existingEmail = $checkEmailStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingEmail) {
        sendErrorResponse('Email đã được sử dụng. Vui lòng chọn email khác.', 'Email already exists', 409);
        exit;
    }
    
    // Kiểm tra số điện thoại đã tồn tại chưa
    $checkPhoneSql = "SELECT userId FROM user WHERE phone = ? LIMIT 1";
    $checkPhoneStmt = $conn->prepare($checkPhoneSql);
    $checkPhoneStmt->execute([$phone]);
    $existingPhone = $checkPhoneStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingPhone) {
        sendErrorResponse('Số điện thoại đã được sử dụng. Vui lòng chọn số khác.', 'Phone already exists', 409);
        exit;
    }
    
    // Hash password trước khi lưu
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Chuẩn bị câu SQL INSERT
    $sql = "INSERT INTO user (
        userName, 
        email, 
        phone,
        password,
        role,
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
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
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
        $hashedPassword, // Thêm hashed password
        $role, // Thêm role
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
        
        // Tạo response data (không trả về password)
        $responseData = [
            'user' => [
                'userId' => (int)$userId,
                'userName' => $userName,
                'email' => $email,
                'phone' => $phone,  // Thêm phone vào response
                'role' => $role, // Thêm role vào response
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
