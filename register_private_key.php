<?php
// New registration file focused on private key handling
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Include config
require_once 'config.php';

try {
    // Create database connection
    $conn = getDatabaseConnection();
    
    // Get JSON input
    $input = file_get_contents('php://input');
    error_log("Register Private Key Input: " . $input);
    $data = json_decode($input, true);
    
    // Validate JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON Decode Error: " . json_last_error_msg());
        echo json_encode(['success' => false, 'message' => 'Invalid JSON format']);
        exit;
    }
    
    // Extract required fields from frontend
    $fullName = isset($data['fullName']) ? trim($data['fullName']) : null;
    $phone = isset($data['phone']) ? trim($data['phone']) : null;
    $email = isset($data['email']) ? trim($data['email']) : null;
    $password = isset($data['password']) ? $data['password'] : null;
    $privateKey = isset($data['privateKey']) ? trim($data['privateKey']) : null;
    $role = isset($data['role']) ? trim($data['role']) : 'user'; // Default to 'user' if not provided
    
    error_log("Parsed Data: Name=$fullName, Phone=$phone, Email=$email, Role=$role");

    // Basic validation
    if (empty($fullName) || empty($phone) || empty($email) || empty($password) || empty($privateKey)) {
        error_log("Validation Failed: Missing fields");
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Prepare SQL for inserting into viegrand.user table
    $sql = "INSERT INTO user (userName, phone, email, password, private_key, role) VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Prepare Error: " . print_r($conn->errorInfo(), true));
        echo json_encode(['success' => false, 'message' => 'Database prepare error']);
        exit;
    }
    
    // Execute the insert
    try {
        $result = $stmt->execute([$fullName, $phone, $email, $hashedPassword, $privateKey, $role]);
        
        if ($result) {
            $userId = $conn->lastInsertId();
            error_log("Insert Success. New User ID: " . $userId);
            
            // Verify the insert by querying back
            $verifySql = "SELECT userId, userName, phone, email, private_key, role FROM user WHERE userId = ?";
            $verifyStmt = $conn->prepare($verifySql);
            $verifyStmt->execute([$userId]);
            $savedData = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            // Return success response with saved data
            echo json_encode([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => [
                        'userId' => (int)$savedData['userId'],
                        'userName' => $savedData['userName'],
                        'phone' => $savedData['phone'],
                        'email' => $savedData['email'],
                        'privateKey' => $savedData['private_key'],
                        'role' => $savedData['role']
                    ]
                ]
            ]);
            
        } else {
            error_log("Execute Failed: " . print_r($stmt->errorInfo(), true));
            echo json_encode(['success' => false, 'message' => 'Failed to register user']);
        }
    } catch (PDOException $e) {
        error_log("PDO Exception during execute: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
