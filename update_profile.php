<?php
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Chỉ cho phép PUT method
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    sendErrorResponse('Method not allowed', 'Only PUT method is allowed', 405);
}

// Authentication helper function
function authenticateUser($pdo) {
    // Get Authorization header - compatible with different server setups
    $authHeader = '';
    
    // Method 1: getallheaders()
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    }
    
    // Method 2: $_SERVER fallback
    if (empty($authHeader)) {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
    }
    
    error_log("Auth header found: " . $authHeader);
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        throw new Exception('Authorization token required. Header: ' . $authHeader);
    }
    
    $token = $matches[1];
    error_log("Extracted token: " . substr($token, 0, 20) . "...");
    
    // Extract user ID from token (format: userId_tokenHash)
    $tokenParts = explode('_', $token, 2);
    if (count($tokenParts) !== 2) {
        throw new Exception('Invalid token format. Token: ' . substr($token, 0, 20) . '...');
    }
    
    $userId = $tokenParts[0];
    error_log("Extracted user ID: " . $userId);
    
    // Validate user exists and is active
    $stmt = $pdo->prepare("SELECT id, fullName, email, active FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !$user['active']) {
        throw new Exception('Invalid or inactive user');
    }
    
    return $user;
}

try {
    error_log("=== UPDATE PROFILE API START ===");
    
    // Kết nối database
    $pdo = getDatabaseConnection();
    error_log("Database connected successfully");
    
    // Authenticate user
    $currentUser = authenticateUser($pdo);
    $userId = $currentUser['id'];
    error_log("User authenticated: ID = " . $userId . ", Name = " . $currentUser['fullName']);
    
    // Lấy dữ liệu từ request
    $rawInput = file_get_contents('php://input');
    error_log("Raw input received: " . $rawInput);
    
    $input = json_decode($rawInput, true);
    error_log("Decoded input: " . json_encode($input));
    
    // Validate input
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Sanitize input
    $input = sanitizeInput($input);
    error_log("Sanitized input: " . json_encode($input));
    
    // Prepare update fields and values
    $updateFields = [];
    $updateValues = [];
    
    // Validate and prepare fields to update
    if (isset($input['fullName'])) {
        if (empty(trim($input['fullName']))) {
            throw new Exception('Họ và tên không được để trống');
        }
        $updateFields[] = "fullName = ?";
        $updateValues[] = $input['fullName'];
    }
    
    if (isset($input['phone'])) {
        if (!empty($input['phone'])) {
            // Basic phone validation
            if (!preg_match('/^[0-9+\-\s()]+$/', $input['phone'])) {
                throw new Exception('Số điện thoại không hợp lệ');
            }
        }
        $updateFields[] = "phone = ?";
        $updateValues[] = $input['phone'];
    }
    
    if (isset($input['age'])) {
        if (!empty($input['age'])) {
            $age = intval($input['age']);
            if ($age < 1 || $age > 120) {
                throw new Exception('Tuổi phải từ 1 đến 120');
            }
            $updateFields[] = "age = ?";
            $updateValues[] = $age;
        } else {
            $updateFields[] = "age = ?";
            $updateValues[] = null;
        }
    }
    
    if (isset($input['address'])) {
        $updateFields[] = "address = ?";
        $updateValues[] = $input['address'];
    }
    
    if (isset($input['gender'])) {
        if (!empty($input['gender']) && !in_array($input['gender'], ['Nam', 'Nữ'])) {
            throw new Exception('Giới tính phải là "Nam" hoặc "Nữ"');
        }
        $updateFields[] = "gender = ?";
        $updateValues[] = $input['gender'];
    }
    
    // Health information fields (for future use when database schema is updated)
    if (isset($input['bloodType'])) {
        $validBloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        if (!empty($input['bloodType']) && !in_array($input['bloodType'], $validBloodTypes)) {
            throw new Exception('Nhóm máu không hợp lệ');
        }
        // Uncomment when bloodType column is added to database
        // $updateFields[] = "bloodType = ?";
        // $updateValues[] = $input['bloodType'];
    }
    
    if (isset($input['allergies'])) {
        // Uncomment when allergies column is added to database
        // $updateFields[] = "allergies = ?";
        // $updateValues[] = $input['allergies'];
    }
    
    if (isset($input['chronicDiseases'])) {
        // Uncomment when chronicDiseases column is added to database
        // $updateFields[] = "chronicDiseases = ?";
        // $updateValues[] = $input['chronicDiseases'];
    }
    
    // Check if there are any fields to update
    if (empty($updateFields)) {
        throw new Exception('Không có thông tin nào để cập nhật');
    }
    
    // Add updated timestamp
    $updateFields[] = "updated_at = ?";
    $updateValues[] = date('Y-m-d H:i:s');
    
    // Add user ID for WHERE clause
    $updateValues[] = $userId;
    
    // Build and execute update query
    $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($updateValues);
    
    // Get updated user data with premium information
    $stmt = $pdo->prepare("
        SELECT 
            u.id, u.fullName, u.email, u.phone, u.age, u.address, u.gender, u.role, u.active,
            u.isPremium, u.premiumStartDate, u.premiumEndDate, u.premiumPlanId, u.premiumTrialUsed,
            pp.name as planName, pp.price as planPrice, pp.type as planType,
            u.created_at, u.updated_at 
        FROM users u
        LEFT JOIN premium_plans pp ON u.premiumPlanId = pp.id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found after update');
    }
    
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
            $premiumInfo['isPremium'] = false;
            $premiumInfo['daysRemaining'] = 0;
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
    
    // Success response
    sendSuccessResponse($user, 'Thông tin hồ sơ đã được cập nhật thành công');
    
} catch (PDOException $e) {
    // Database error
    error_log("Database Error in update_profile.php: " . $e->getMessage());
    sendErrorResponse('Database error occurred', 'Internal server error', 500);
    
} catch (Exception $e) {
    // Validation or other error
    sendErrorResponse($e->getMessage(), 'Bad request', 400);
}
?>
