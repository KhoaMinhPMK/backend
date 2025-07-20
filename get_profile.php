<?php
require_once 'config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow GET method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendErrorResponse('Method not allowed', 'Only GET method is allowed', 405);
}

try {
    // Get Authorization header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
        sendErrorResponse('Authorization required', 'Missing or invalid authorization token', 401);
    }
    
    $token = substr($authHeader, 7); // Remove 'Bearer ' prefix
    
    // Extract user ID from token (format: {userId}_{randomToken})
    $tokenParts = explode('_', $token, 2);
    if (count($tokenParts) !== 2) {
        sendErrorResponse('Invalid token format', 'Token format is invalid', 401);
    }
    
    $userId = (int)$tokenParts[0];
    if ($userId <= 0) {
        sendErrorResponse('Invalid token', 'Token contains invalid user ID', 401);
    }
    
    $pdo = getDatabaseConnection();
    
    // Get user data
    $stmt = $pdo->prepare("
        SELECT id, fullName, email, phone, age, address, gender, role, active, 
               isPremium, premiumEndDate, premiumTrialUsed, created_at, updated_at,
               bloodType, allergies, medicalConditions, medications, 
               emergencyContactName, emergencyContactPhone, emergencyContactRelation
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        sendErrorResponse('User not found', 'User does not exist', 404);
    }
    
    if (!$user['active']) {
        sendErrorResponse('Account inactive', 'Your account has been deactivated', 403);
    }
    
    // Format response data
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
    sendSuccessResponse($user, 'Profile retrieved successfully');
    
} catch (PDOException $e) {
    error_log("Database Error in get_profile.php: " . $e->getMessage());
    sendErrorResponse('Database error', 'An error occurred while retrieving profile', 500);
    
} catch (Exception $e) {
    error_log("Error in get_profile.php: " . $e->getMessage());
    sendErrorResponse('Server error', $e->getMessage(), 500);
}
?>
