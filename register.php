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
    error_log('Register input: ' . $input);
    
    // Debug: log all received fields
    error_log('Received data fields: ' . json_encode(array_keys($data)));
    
    // Kiểm tra JSON có hợp lệ không
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse('Invalid JSON format', 'Bad request', 400);
        exit;
    }
    
    // Chuẩn bị các trường dữ liệu theo cấu trúc bảng user (tất cả đều optional)
    $userName = isset($data['userName']) ? sanitizeInput($data['userName']) : null;
    $email = isset($data['email']) ? sanitizeInput($data['email']) : null;
    $phone = isset($data['phone']) ? sanitizeInput($data['phone']) : null; // Thêm field phone
    $password = isset($data['password']) ? $data['password'] : null; // Thêm password
    $role = isset($data['role']) ? sanitizeInput($data['role']) : 'user'; // Thêm role
    $privateKey = isset($data['privateKey']) ? sanitizeInput($data['privateKey']) : null; // Thêm private key
    
    // Debug: log private key value before and after sanitization
    error_log('Private key before sanitization: ' . (isset($data['privateKey']) ? $data['privateKey'] : 'NULL'));
    error_log('Private key after sanitization: ' . ($privateKey ?: 'NULL'));
    
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
    
    // DEBUG: Let's try a minimal insert first to isolate the issue
    error_log('=== ATTEMPTING INSERT ===');
    
    // Full SQL insert
    $sql = "INSERT INTO user (
        userName, email, phone, password, role, private_key, 
        age, gender, blood, chronic_diseases, allergies, 
        premium_status, premium_start_date, premium_end_date, 
        notifications, relative_phone, home_address, created_at, updated_at
    ) VALUES (
        ?, ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, 
        ?, ?, ?, 
        ?, ?, ?, NOW(), NOW()
    )";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log('Prepare error: ' . json_encode($conn->errorInfo()));
        sendErrorResponse('Database prepare error', 'Internal server error', 500);
        exit;
    }
    
    // Execute with all parameters
    $result = $stmt->execute([
        $userName, $email, $phone, $hashedPassword, $role, $privateKey,
        $age, $gender, $blood, $chronic_diseases, $allergies,
        $premium_status ? 1 : 0, $premium_start_date, $premium_end_date,
        $notifications ? 1 : 0, $relative_phone, $home_address
    ]);
    
    if ($result) {
        $userId = $conn->lastInsertId();
        error_log('Insert successful, userId: ' . $userId);
        
        // Verify what was actually saved
        $verifySql = "SELECT * FROM user WHERE userId = ?";
        $verifyStmt = $conn->prepare($verifySql);
        $verifyStmt->execute([$userId]);
        $savedData = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        error_log('Insert verification: ' . json_encode($savedData));
        
        // Return the saved data
        $responseData = [
            'user' => [
                'userId' => (int)$userId,
                'userName' => $savedData['userName'],
                'email' => $savedData['email'],
                'phone' => $savedData['phone'],
                'role' => $savedData['role'],
                'privateKey' => $savedData['private_key'],
                'age' => $savedData['age'],
                'gender' => $savedData['gender'],
                'blood' => $savedData['blood'],
                'chronic_diseases' => $savedData['chronic_diseases'],
                'allergies' => $savedData['allergies'],
                'premium_status' => (bool)$savedData['premium_status'],
                'premium_start_date' => $savedData['premium_start_date'],
                'premium_end_date' => $savedData['premium_end_date'],
                'notifications' => (bool)$savedData['notifications'],
                'relative_phone' => $savedData['relative_phone'],
                'home_address' => $savedData['home_address'],
                'created_at' => $savedData['created_at'],
                'updated_at' => $savedData['updated_at']
            ]
        ];
        
        sendSuccessResponse($responseData, 'User registered successfully');
        
    } else {
        $errorInfo = $stmt->errorInfo();
        error_log('Insert failed: ' . json_encode($errorInfo));
        sendErrorResponse('Failed to register user: ' . $errorInfo[2], 'Database error', 500);
    }

} catch (Exception $e) {
    error_log('Exception in register.php: ' . $e->getMessage());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?>
