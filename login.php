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
    
    // Tìm user theo email với thông tin premium
    $stmt = $pdo->prepare("
        SELECT 
            u.id, u.fullName, u.email, u.password, u.phone, u.age, u.address, u.gender, u.role, u.active,
            u.isPremium, u.premiumStartDate, u.premiumEndDate, u.premiumPlanId, u.premiumTrialUsed,
            pp.name as planName, pp.price as planPrice, pp.type as planType,
            u.created_at, u.updated_at 
        FROM users u
        LEFT JOIN premium_plans pp ON u.premiumPlanId = pp.id
        WHERE u.email = ?
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
    
    // Generate new token with user ID
    $token = $user['id'] . '_' . generateToken();
    
    // Remove password from user data
    unset($user['password']);
    
    // Format premium information
    $premiumInfo = [
        'isPremium' => (bool)$user['isPremium'],
        'premiumStartDate' => $user['premiumStartDate'],
        'premiumEndDate' => $user['premiumEndDate'],
        'trialUsed' => (bool)$user['premiumTrialUsed'],
        'daysRemaining' => 0,
        'plan' => null
    ];
    
    // Calculate days remaining if premium
    if ($user['isPremium'] && $user['premiumEndDate']) {
        $endDate = new DateTime($user['premiumEndDate']);
        $now = new DateTime();
        $interval = $now->diff($endDate);
        
        if ($endDate > $now) {
            $premiumInfo['daysRemaining'] = $interval->days;
        } else {
            // Premium expired, update user status in database
            $premiumInfo['isPremium'] = false;
            $premiumInfo['daysRemaining'] = 0;
            
            // Update database to reflect expired premium
            $updateStmt = $pdo->prepare("
                UPDATE users 
                SET isPremium = FALSE, premiumPlanId = NULL 
                WHERE id = ?
            ");
            $updateStmt->execute([$user['id']]);
            
            // Update local user data
            $user['isPremium'] = false;
            $user['premiumPlanId'] = null;
        }
    }
    
    // Add plan info if exists
    if ($user['premiumPlanId']) {
        $premiumInfo['plan'] = [
            'id' => $user['premiumPlanId'],
            'name' => $user['planName'],
            'price' => $user['planPrice'],
            'type' => $user['planType']
        ];
    }
    
    // Remove premium fields from main user object
    unset($user['isPremium'], $user['premiumStartDate'], $user['premiumEndDate'], 
          $user['premiumPlanId'], $user['premiumTrialUsed'], 
          $user['planName'], $user['planPrice'], $user['planType']);
    
    // Add premium info to user object
    $user['premium'] = $premiumInfo;
    
    // Convert timestamps to ISO format
    $user['createdAt'] = $user['created_at'];
    $user['updatedAt'] = $user['updated_at'];
    unset($user['created_at'], $user['updated_at']);
    
    // Convert gender to Vietnamese for frontend display
    if ($user['gender']) {
        $genderDisplayMap = [
            'male' => 'Nam',
            'female' => 'Nữ', 
            'other' => 'Khác'
        ];
        $user['gender'] = $genderDisplayMap[$user['gender']] ?? $user['gender'];
    }
    
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