<?php
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Chỉ cho phép GET method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendErrorResponse('Method not allowed', 'Only GET method is allowed', 405);
}

// Authentication helper function
function authenticateUser($pdo) {
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        throw new Exception('Authorization token required');
    }
    
    $token = $matches[1];
    
    // Extract user ID from token (format: userId_tokenHash)
    $tokenParts = explode('_', $token, 2);
    if (count($tokenParts) !== 2) {
        throw new Exception('Invalid token format');
    }
    
    $userId = $tokenParts[0];
    
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
    // Kết nối database
    $pdo = getDatabaseConnection();
    
    // Authenticate user
    $currentUser = authenticateUser($pdo);
    $userId = $currentUser['id'];
    
    // Get user data with premium information
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
        throw new Exception('User not found');
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
            // Premium expired, update user status in database
            $premiumInfo['isPremium'] = false;
            $premiumInfo['daysRemaining'] = 0;
            
            // Update database to reflect expired premium
            $updateStmt = $pdo->prepare("
                UPDATE users 
                SET isPremium = FALSE, premiumPlanId = NULL 
                WHERE id = ?
            ");
            $updateStmt->execute([$userId]);
            
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
    
    // Success response
    sendSuccessResponse($user, 'User profile retrieved successfully');
    
} catch (PDOException $e) {
    // Database error
    error_log("Database Error in get_profile.php: " . $e->getMessage());
    sendErrorResponse('Database error occurred', 'Internal server error', 500);
    
} catch (Exception $e) {
    // Validation or other error
    sendErrorResponse($e->getMessage(), 'Bad request', 400);
}
?>