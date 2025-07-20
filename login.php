<?php
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Chỉ cho phép POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Method not allowed', 'Only POST method is allowed', 405);
}

try {
    // Kết nối database
    $pdo = getDatabaseConnection();
    
    // Lấy dữ liệu từ request
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Sanitize input
    $input = sanitizeInput($input);
    
    // Validate required fields
    $required_fields = ['email', 'password'];
    validateRequiredFields($input, $required_fields);
    
    $email = $input['email'];
    $password = $input['password'];
    
    // Validate email format
    validateEmail($email);
    
    // Validate password length
    validatePassword($password);
    
    // Tìm user theo email
    $stmt = $pdo->prepare("
        SELECT id, fullName, email, password, phone, age, address, gender, role, active, created_at, updated_at 
        FROM users WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Kiểm tra user có tồn tại không
    if (!$user) {
        throw new Exception('Email hoặc mật khẩu không đúng');
    }
    
    // Kiểm tra user có active không
    if (!$user['active']) {
        throw new Exception('Tài khoản đã bị khóa');
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        throw new Exception('Email hoặc mật khẩu không đúng');
    }
    
    // Generate new token
    $token = generateToken();
    
    // Remove password from user data
    unset($user['password']);
    
    // Convert timestamps to ISO format
    $user['createdAt'] = $user['created_at'];
    $user['updatedAt'] = $user['updated_at'];
    unset($user['created_at'], $user['updated_at']);
    
    // Success response
    sendSuccessResponse([
        'access_token' => $token,
        'user' => $user
    ], 'Login successful');
    
} catch (PDOException $e) {
    // Database error
    error_log("Database Error: " . $e->getMessage());
    sendErrorResponse('Database error occurred', 'Internal server error', 500);
    
} catch (Exception $e) {
    // Validation or other error
    sendErrorResponse($e->getMessage(), 'Bad request', 400);
}
?> 