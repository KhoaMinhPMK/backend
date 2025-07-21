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
    
    // Kiểm tra token trong header hoặc trong body
    $token = null;
    $headers = getallheaders();
    
    if (isset($headers['Authorization'])) {
        $token = str_replace('Bearer ', '', $headers['Authorization']);
    } elseif (isset($input['token'])) {
        $token = $input['token'];
    }
    
    if (!$token) {
        throw new Exception('Token không được cung cấp');
    }
    
    // Lấy user ID từ token (format: userId_token)
    $token_parts = explode('_', $token, 2);
    if (count($token_parts) !== 2) {
        throw new Exception('Token không hợp lệ');
    }
    
    $user_id = $token_parts[0];
    
    // Verify user exists and is active
    $stmt = $pdo->prepare("SELECT id, fullName, email, phone, age, address, gender, role, active FROM users WHERE id = ? AND active = 1");
    $stmt->execute([$user_id]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current_user) {
        throw new Exception('Người dùng không tồn tại hoặc tài khoản đã bị khóa');
    }
    
    // Danh sách các field có thể cập nhật
    $updatable_fields = ['fullName', 'phone', 'age', 'address', 'gender'];
    $update_data = [];
    $update_fields = [];
    
    // Kiểm tra và validate từng field
    foreach ($updatable_fields as $field) {
        if (isset($input[$field]) && $input[$field] !== '') {
            switch ($field) {
                case 'fullName':
                    if (strlen($input[$field]) < 2) {
                        throw new Exception('Họ tên phải có ít nhất 2 ký tự');
                    }
                    if (strlen($input[$field]) > 100) {
                        throw new Exception('Họ tên không được vượt quá 100 ký tự');
                    }
                    break;
                    
                case 'phone':
                    // Validate phone number format (Vietnamese phone number)
                    if (!preg_match('/^[0-9]{10,11}$/', $input[$field])) {
                        throw new Exception('Số điện thoại không hợp lệ (10-11 số)');
                    }
                    // Check if phone already exists for other users
                    $phone_check = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
                    $phone_check->execute([$input[$field], $user_id]);
                    if ($phone_check->fetch()) {
                        throw new Exception('Số điện thoại đã được sử dụng bởi tài khoản khác');
                    }
                    break;
                    
                case 'age':
                    $age = intval($input[$field]);
                    if ($age < 1 || $age > 150) {
                        throw new Exception('Tuổi phải từ 1 đến 150');
                    }
                    $input[$field] = $age;
                    break;
                    
                case 'address':
                    if (strlen($input[$field]) > 255) {
                        throw new Exception('Địa chỉ không được vượt quá 255 ký tự');
                    }
                    break;
                    
                case 'gender':
                    if (!in_array($input[$field], ['male', 'female', 'other'])) {
                        throw new Exception('Giới tính phải là: male, female hoặc other');
                    }
                    break;
            }
            
            $update_data[] = $input[$field];
            $update_fields[] = "$field = ?";
        }
    }
    
    // Kiểm tra có field nào để cập nhật không
    if (empty($update_fields)) {
        throw new Exception('Không có thông tin nào để cập nhật');
    }
    
    // Thêm updated_at
    $update_fields[] = "updated_at = NOW()";
    
    // Thực hiện update
    $sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
    $update_data[] = $user_id;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($update_data);
    
    // Kiểm tra có row nào bị affect không
    if ($stmt->rowCount() === 0) {
        throw new Exception('Không có thay đổi nào được thực hiện');
    }
    
    // Lấy thông tin user sau khi update
    $stmt = $pdo->prepare("
        SELECT 
            id, fullName, email, phone, age, address, gender, role, 
            isPremium, premiumStartDate, premiumEndDate, premiumPlanId, premiumTrialUsed,
            created_at, updated_at 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $updated_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$updated_user) {
        throw new Exception('Không thể lấy thông tin người dùng sau khi cập nhật');
    }
    
    // Format response data
    $response_data = [
        'user' => [
            'id' => intval($updated_user['id']),
            'fullName' => $updated_user['fullName'],
            'email' => $updated_user['email'],
            'phone' => $updated_user['phone'],
            'age' => intval($updated_user['age']),
            'address' => $updated_user['address'],
            'gender' => $updated_user['gender'],
            'role' => $updated_user['role'],
            'isPremium' => (bool)$updated_user['isPremium'],
            'premiumStartDate' => $updated_user['premiumStartDate'],
            'premiumEndDate' => $updated_user['premiumEndDate'],
            'premiumPlanId' => $updated_user['premiumPlanId'] ? intval($updated_user['premiumPlanId']) : null,
            'premiumTrialUsed' => (bool)$updated_user['premiumTrialUsed'],
            'created_at' => $updated_user['created_at'],
            'updated_at' => $updated_user['updated_at']
        ],
        'updated_fields' => array_keys(array_filter($input, function($key) use ($updatable_fields) {
            return in_array($key, $updatable_fields);
        }, ARRAY_FILTER_USE_KEY))
    ];
    
    sendSuccessResponse($response_data, 'Cập nhật thông tin thành công', 200);
    
} catch (PDOException $e) {
    error_log("Database Error in update_profile.php: " . $e->getMessage());
    sendErrorResponse('Lỗi cơ sở dữ liệu', 'Database error', 500);
} catch (Exception $e) {
    error_log("Error in update_profile.php: " . $e->getMessage());
    sendErrorResponse($e->getMessage(), 'Validation error', 400);
} catch (Error $e) {
    error_log("Fatal Error in update_profile.php: " . $e->getMessage());
    sendErrorResponse('Lỗi hệ thống', 'Internal server error', 500);
}
?>
