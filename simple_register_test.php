<?php
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Method not allowed', 405);
    exit;
}

try {
    $conn = getDatabaseConnection();
    
    // Get input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Extract only essential fields for testing
    $userName = isset($data['userName']) ? sanitizeInput($data['userName']) : 'Test User';
    $email = isset($data['email']) ? sanitizeInput($data['email']) : 'test@example.com';
    $phone = isset($data['phone']) ? sanitizeInput($data['phone']) : '1234567890';
    $password = isset($data['password']) ? $data['password'] : 'test123';
    $role = isset($data['role']) ? sanitizeInput($data['role']) : 'user';
    $privateKey = isset($data['privateKey']) ? sanitizeInput($data['privateKey']) : null;
    
    error_log('=== SIMPLE REGISTER TEST ===');
    error_log('privateKey received: ' . (isset($data['privateKey']) ? $data['privateKey'] : 'NOT_SET'));
    error_log('privateKey after sanitize: ' . ($privateKey ?: 'NULL'));
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Simple SQL with only essential fields
    $sql = "INSERT INTO user (userName, email, phone, password, role, private_key) VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log('Prepare failed: ' . json_encode($conn->errorInfo()));
        sendErrorResponse('Database prepare error');
        exit;
    }
    
    $params = [$userName, $email, $phone, $hashedPassword, $role, $privateKey];
    error_log('Parameters: ' . json_encode([
        'userName' => $userName,
        'email' => $email, 
        'phone' => $phone,
        'password' => '***',
        'role' => $role,
        'privateKey' => $privateKey
    ]));
    
    $result = $stmt->execute($params);
    
    if ($result) {
        $userId = $conn->lastInsertId();
        error_log('User inserted with ID: ' . $userId);
        
        // Verify what was saved
        $checkSql = "SELECT userId, userName, email, phone, role, private_key FROM user WHERE userId = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->execute([$userId]);
        $savedData = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        error_log('Saved data: ' . json_encode($savedData));
        
        sendSuccessResponse([
            'user' => [
                'userId' => (int)$userId,
                'userName' => $savedData['userName'],
                'email' => $savedData['email'],
                'phone' => $savedData['phone'],
                'role' => $savedData['role'],
                'privateKey' => $savedData['private_key']
            ]
        ], 'Simple register test successful');
        
    } else {
        error_log('Execute failed: ' . json_encode($stmt->errorInfo()));
        sendErrorResponse('Failed to insert user');
    }
    
} catch (Exception $e) {
    error_log('Exception: ' . $e->getMessage());
    sendErrorResponse('Server error: ' . $e->getMessage());
}
?>
