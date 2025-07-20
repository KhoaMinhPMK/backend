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
    $required_fields = ['fullName', 'email', 'phone', 'password'];
    validateRequiredFields($input, $required_fields);
    
    $fullName = $input['fullName'];
    $email = $input['email'];
    $phone = $input['phone'];
    $password = $input['password'];
    
    // Validate email format
    validateEmail($email);
    
    // Validate password length
    validatePassword($password);
    
    // Validate phone number
    validatePhone($phone);
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception('Email already exists');
    }
    
    // Check if phone already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->fetch()) {
        throw new Exception('Phone number already exists');
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate token
    $token = generateToken();
    
    // Insert user into database
    $stmt = $pdo->prepare("
        INSERT INTO users (fullName, email, password, phone, role, active, isPremium, premiumTrialUsed, created_at, updated_at) 
        VALUES (?, ?, ?, ?, 'elderly', TRUE, FALSE, FALSE, NOW(), NOW())
    ");
    
    $stmt->execute([$fullName, $email, $hashedPassword, $phone]);
    $userId = $pdo->lastInsertId();
    
    // Get user data for response
    $stmt = $pdo->prepare("
        SELECT id, fullName, email, phone, age, address, gender, role, active, isPremium, premiumEndDate, premiumTrialUsed, created_at, updated_at 
        FROM users WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
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
    ], 'User registered successfully', 201);
    
} catch (PDOException $e) {
    // Database error
    error_log("Database Error: " . $e->getMessage());
    sendErrorResponse('Database error occurred', 'Internal server error', 500);
    
} catch (Exception $e) {
    // Validation or other error
    sendErrorResponse($e->getMessage(), 'Bad request', 400);
}
?>
