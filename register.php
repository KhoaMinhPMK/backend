<?php
// Bao gồm file config
require_once 'config.php';

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
    
    // Kiểm tra JSON có hợp lệ không
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse('Invalid JSON format');
        exit;
    }
    
    // Chuẩn bị các trường dữ liệu (tất cả đều optional)
    $fullName = isset($data['fullName']) ? sanitizeInput($data['fullName']) : null;
    $email = isset($data['email']) ? sanitizeInput($data['email']) : null;
    $phone = isset($data['phone']) ? sanitizeInput($data['phone']) : null;
    $password = isset($data['password']) ? $data['password'] : null;
    $role = isset($data['role']) ? sanitizeInput($data['role']) : 'user';
    $gender = isset($data['gender']) ? sanitizeInput($data['gender']) : null;
    $dateOfBirth = isset($data['dateOfBirth']) ? sanitizeInput($data['dateOfBirth']) : null;
    $address = isset($data['address']) ? sanitizeInput($data['address']) : null;
    $emergencyContact = isset($data['emergencyContact']) ? sanitizeInput($data['emergencyContact']) : null;
    $medicalInfo = isset($data['medicalInfo']) ? sanitizeInput($data['medicalInfo']) : null;
    $avatar = isset($data['avatar']) ? sanitizeInput($data['avatar']) : null;
    
    // Hash password nếu có
    $hashedPassword = null;
    if ($password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    }
    
    // Chuẩn bị câu SQL INSERT
    $sql = "INSERT INTO users (
        fullName, 
        email, 
        phone, 
        password, 
        role, 
        gender, 
        dateOfBirth, 
        address, 
        emergencyContact, 
        medicalInfo, 
        avatar,
        active,
        createdAt
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        sendErrorResponse('Database prepare error');
        exit;
    }
    
    // Execute với parameters
    $result = $stmt->execute([
        $fullName, 
        $email, 
        $phone, 
        $hashedPassword, 
        $role, 
        $gender, 
        $dateOfBirth, 
        $address, 
        $emergencyContact, 
        $medicalInfo, 
        $avatar
    ]);
    
    // Thực thi query
    if ($result) {
        $userId = $conn->lastInsertId();
        
        // Tạo response data
        $responseData = [
            'user' => [
                'id' => (int)$userId,
                'fullName' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'role' => $role,
                'gender' => $gender,
                'dateOfBirth' => $dateOfBirth,
                'address' => $address,
                'emergencyContact' => $emergencyContact,
                'medicalInfo' => $medicalInfo,
                'avatar' => $avatar,
                'active' => true,
                'createdAt' => date('Y-m-d H:i:s')
            ]
        ];
        
        sendSuccessResponse('User registered successfully', $responseData);
        
    } else {
        sendErrorResponse('Failed to register user');
    }
    
} catch (Exception $e) {
    sendErrorResponse('Server error: ' . $e->getMessage());
}
?>
