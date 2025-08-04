<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

// Chỉ cho phép POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Log request details
    error_log("Emergency API - Request method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Emergency API - Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
    
    // Lấy dữ liệu từ request
    $rawInput = file_get_contents('php://input');
    error_log("Emergency API - Raw input: " . $rawInput);
    
    $input = json_decode($rawInput, true);
    
    if (!$input) {
        error_log("Emergency API - JSON decode failed: " . json_last_error_msg());
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }
    
    error_log("Emergency API - Parsed input: " . json_encode($input));
    
    $userEmail = $input['user_email'] ?? '';
    $emergencyNumber = $input['emergency_number'] ?? '';
    $contactName = $input['contact_name'] ?? 'Số khẩn cấp';
    
    // Validate dữ liệu
    error_log("Emergency API - Validating data: email='$userEmail', number='$emergencyNumber', name='$contactName'");
    
    if (empty($userEmail)) {
        error_log("Emergency API - Email is empty");
        throw new Exception('Email is required');
    }
    
    if (empty($emergencyNumber)) {
        error_log("Emergency API - Emergency number is empty");
        throw new Exception('Emergency number is required');
    }
    
    // Validate email format
    if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        error_log("Emergency API - Invalid email format: $userEmail");
        throw new Exception('Invalid email format');
    }
    
    // Validate phone number (basic validation)
    if (!preg_match('/^[0-9+\-\s()]{10,20}$/', $emergencyNumber)) {
        error_log("Emergency API - Invalid phone number format: $emergencyNumber");
        throw new Exception('Invalid phone number format');
    }
    
    // Kết nối database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Kiểm tra xem user có tồn tại không
    error_log("Emergency API - Checking if user exists: $userEmail");
    $checkUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkUser->execute([$userEmail]);
    
    if (!$checkUser->fetch()) {
        error_log("Emergency API - User not found: $userEmail");
        throw new Exception('User not found');
    }
    
    error_log("Emergency API - User found, proceeding with save");
    
    // Lưu hoặc cập nhật số khẩn cấp
    $sql = "INSERT INTO emergency_contacts (user_email, emergency_number, contact_name) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            emergency_number = VALUES(emergency_number),
            contact_name = VALUES(contact_name),
            updated_at = CURRENT_TIMESTAMP";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$userEmail, $emergencyNumber, $contactName]);
    
    if ($result) {
        // Lấy thông tin vừa lưu
        $getContact = $pdo->prepare("SELECT * FROM emergency_contacts WHERE user_email = ?");
        $getContact->execute([$userEmail]);
        $contact = $getContact->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Emergency contact saved successfully',
            'data' => $contact
        ]);
    } else {
        throw new Exception('Failed to save emergency contact');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 