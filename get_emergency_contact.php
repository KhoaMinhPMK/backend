<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

// Chỉ cho phép GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Lấy email từ query parameter
    $userEmail = $_GET['email'] ?? '';
    
    // Validate email
    if (empty($userEmail)) {
        throw new Exception('Email parameter is required');
    }
    
    if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Kết nối database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Lấy thông tin số khẩn cấp
    $sql = "SELECT emergency_number, contact_name, updated_at 
            FROM emergency_contacts 
            WHERE user_email = ? AND is_active = TRUE";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userEmail]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($contact) {
        echo json_encode([
            'success' => true,
            'message' => 'Emergency contact found',
            'data' => $contact
        ]);
    } else {
        // Trả về số mặc định nếu không tìm thấy
        echo json_encode([
            'success' => true,
            'message' => 'No emergency contact found, using default',
            'data' => [
                'emergency_number' => '0902716951',
                'contact_name' => 'Số khẩn cấp',
                'updated_at' => null
            ]
        ]);
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