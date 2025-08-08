<?php
// Bao gồm file config
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Chỉ cho phép POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Lấy kết nối database
    $conn = getDatabaseConnection();
    
    // Lấy dữ liệu JSON từ request body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Debug: log input data
    error_log('Login input: ' . $input);
    
    // Kiểm tra JSON có hợp lệ không
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON format'
        ]);
        exit;
    }
    
    // Lấy email và password từ request (bắt buộc để login)
    $email = isset($data['email']) ? sanitizeInput($data['email']) : null;
    $password = isset($data['password']) ? $data['password'] : null;
    
    if (!$email || !$password) {
        echo json_encode([
            'success' => false,
            'message' => 'Email and password are required for login'
        ]);
        exit;
    }
    
    // Tìm user theo email
    $sql = "SELECT * FROM user WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Database prepare error'
        ]);
        exit;
    }
    
    // Execute với parameter
    $result = $stmt->execute([$email]);
    
    if ($result) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Kiểm tra password
            if (password_verify($password, $user['password'])) {
                // Password đúng - trả về thông tin user
                            $responseData = [
                    'user' => [
                        'userId' => (int)$user['userId'],
                        'userName' => $user['userName'],
                        'email' => $user['email'],
                        'phone' => $user['phone'], // Thêm field phone
                        'role' => $user['role'], // Thêm field role
                        'private_key' => $user['private_key'], // Thêm private key
                        'age' => $user['age'],
                        'gender' => $user['gender'],
                        'blood' => $user['blood'],
                        'chronic_diseases' => $user['chronic_diseases'],
                        'allergies' => $user['allergies'],
                        'premium_status' => (bool)$user['premium_status'],
                    'premium_start_date' => $user['premium_start_date'],
                    'premium_end_date' => $user['premium_end_date'],
                        'notifications' => (bool)$user['notifications'],
                        'relative_phone' => $user['relative_phone'],
                        'home_address' => $user['home_address'],
                        'created_at' => $user['created_at'],
                        'updated_at' => $user['updated_at']
                    ],
                    'token' => 'jwt_token_' . $user['userId'] . '_' . time()
            ];
                
                // Debug: log response data
                error_log('Login response: ' . json_encode($responseData));
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful',
                    'data' => $responseData
                ]);
            } else {
                // Password sai
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid email or password'
                ]);
            }
            
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to query database'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
