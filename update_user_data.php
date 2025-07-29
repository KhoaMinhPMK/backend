<?php
// Bao gồm file config
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Chỉ cho phép POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Method not allowed', 'Method not allowed', 405);
    exit;
}

try {
    // Lấy kết nối database
    $conn = getDatabaseConnection();
    
    // Lấy dữ liệu JSON từ request body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Debug: log input data
    error_log('Update user input: ' . $input);
    
    // Kiểm tra JSON có hợp lệ không
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse('Invalid JSON format', 'Bad request', 400);
        exit;
    }
    
    // Email là bắt buộc để xác định user cần update
    $email = isset($data['email']) ? sanitizeInput($data['email']) : null;
    
    if (!$email) {
        sendErrorResponse('Email is required to update user data', 'Bad request', 400);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendErrorResponse('Invalid email format', 'Bad request', 400);
        exit;
    }
    
    // Kiểm tra user có tồn tại không
    $checkSql = "SELECT userId FROM user WHERE email = ? LIMIT 1";
    $checkStmt = $conn->prepare($checkSql);
    
    if (!$checkStmt) {
        sendErrorResponse('Database prepare error', 'Internal server error', 500);
        exit;
    }
    
    $checkResult = $checkStmt->execute([$email]);
    
    if (!$checkResult) {
        sendErrorResponse('Failed to check user existence', 'Database error', 500);
        exit;
    }
    
    $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingUser) {
        sendErrorResponse('User not found with this email', 'Not found', 404);
        exit;
    }
    
    // Chuẩn bị các trường có thể update (tất cả đều optional)
    $updateFields = [];
    $updateValues = [];
    
    // Các trường có thể update theo cấu trúc bảng user
    $allowedFields = [
        'userName' => 'userName',
        'phone' => 'phone',
        'age' => 'age',
        'gender' => 'gender',
        'blood' => 'blood',
        'chronic_diseases' => 'chronic_diseases',
        'allergies' => 'allergies',
        'premium_status' => 'premium_status',
        'premium_start_date' => 'premium_start_date',
        'premium_end_date' => 'premium_end_date',
        'notifications' => 'notifications',
        'relative_phone' => 'relative_phone',
        'home_address' => 'home_address',
        // Health fields
        'hypertension' => 'hypertension',
        'heart_disease' => 'heart_disease',
        'ever_married' => 'ever_married',
        'work_type' => 'work_type',
        'residence_type' => 'residence_type',
        'avg_glucose_level' => 'avg_glucose_level',
        'bmi' => 'bmi',
        'height' => 'height',
        'weight' => 'weight',
        'smoking_status' => 'smoking_status',
        'stroke' => 'stroke',
        // Blood pressure fields
        'blood_pressure_systolic' => 'blood_pressure_systolic',
        'blood_pressure_diastolic' => 'blood_pressure_diastolic',
        'heart_rate' => 'heart_rate',
        'last_health_check' => 'last_health_check'
    ];
    
    foreach ($allowedFields as $jsonKey => $dbField) {
        if (isset($data[$jsonKey])) {
            $updateFields[] = "$dbField = ?";
            
            // Xử lý kiểu dữ liệu đặc biệt
            if ($dbField === 'premium_status' || $dbField === 'notifications') {
                $updateValues[] = (bool)$data[$jsonKey];
            } elseif ($dbField === 'hypertension' || $dbField === 'heart_disease' || $dbField === 'stroke') {
                // Health boolean fields (0/1)
                $updateValues[] = (int)$data[$jsonKey];
            } elseif ($dbField === 'age') {
                $updateValues[] = (int)$data[$jsonKey];
            } elseif ($dbField === 'avg_glucose_level' || $dbField === 'bmi' || $dbField === 'height' || $dbField === 'weight') {
                // Decimal fields
                $updateValues[] = $data[$jsonKey] ? (float)$data[$jsonKey] : null;
            } elseif ($dbField === 'premium_start_date' || $dbField === 'premium_end_date') {
                // Xử lý datetime fields - expect ISO string format
                $dateValue = $data[$jsonKey];
                if ($dateValue && $dateValue !== 'null') {
                    // Convert ISO string to MySQL datetime format
                    $dateTime = date('Y-m-d H:i:s', strtotime($dateValue));
                    $updateValues[] = $dateTime;
                } else {
                    $updateValues[] = null;
                }
            } else {
                $updateValues[] = sanitizeInput($data[$jsonKey]);
            }
        }
    }
    
    // Nếu không có field nào để update
    if (empty($updateFields)) {
        sendErrorResponse('No fields to update', 'Bad request', 400);
        exit;
    }
    
    // Thêm updated_at timestamp
    $updateFields[] = "updated_at = NOW()";
    
    // Thêm email vào cuối để làm WHERE condition
    $updateValues[] = $email;
    
    // Tạo câu SQL UPDATE
    $sql = "UPDATE user SET " . implode(', ', $updateFields) . " WHERE email = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        sendErrorResponse('Database prepare error', 'Internal server error', 500);
        exit;
    }
    
    // Execute update
    $result = $stmt->execute($updateValues);
    
    if ($result) {
        // Lấy thông tin user sau khi update
        $getUserSql = "SELECT * FROM user WHERE email = ? LIMIT 1";
        $getUserStmt = $conn->prepare($getUserSql);
        $getUserResult = $getUserStmt->execute([$email]);
        
        if ($getUserResult) {
            $updatedUser = $getUserStmt->fetch(PDO::FETCH_ASSOC);
            
            $responseData = [
                'user' => [
                    'userId' => (int)$updatedUser['userId'],
                    'userName' => $updatedUser['userName'],
                    'email' => $updatedUser['email'],
                    'phone' => $updatedUser['phone'], // Thêm field phone
                    'age' => $updatedUser['age'],
                    'gender' => $updatedUser['gender'],
                    'blood' => $updatedUser['blood'],
                    'chronic_diseases' => $updatedUser['chronic_diseases'],
                    'allergies' => $updatedUser['allergies'],
                    'premium_status' => (bool)$updatedUser['premium_status'],
                    'premium_start_date' => $updatedUser['premium_start_date'],
                    'premium_end_date' => $updatedUser['premium_end_date'],
                    'notifications' => (bool)$updatedUser['notifications'],
                    'relative_phone' => $updatedUser['relative_phone'],
                    'home_address' => $updatedUser['home_address'],
                    // Health fields
                    'hypertension' => (int)$updatedUser['hypertension'],
                    'heart_disease' => (int)$updatedUser['heart_disease'],
                    'ever_married' => $updatedUser['ever_married'],
                    'work_type' => $updatedUser['work_type'],
                    'residence_type' => $updatedUser['residence_type'],
                    'avg_glucose_level' => $updatedUser['avg_glucose_level'] ? (float)$updatedUser['avg_glucose_level'] : null,
                    'bmi' => $updatedUser['bmi'] ? (float)$updatedUser['bmi'] : null,
                    'height' => $updatedUser['height'] ? (float)$updatedUser['height'] : null,
                    'weight' => $updatedUser['weight'] ? (float)$updatedUser['weight'] : null,
                    'smoking_status' => $updatedUser['smoking_status'],
                    'stroke' => (int)$updatedUser['stroke'],
                    // Blood pressure fields
                    'blood_pressure_systolic' => $updatedUser['blood_pressure_systolic'] ? (int)$updatedUser['blood_pressure_systolic'] : null,
                    'blood_pressure_diastolic' => $updatedUser['blood_pressure_diastolic'] ? (int)$updatedUser['blood_pressure_diastolic'] : null,
                    'heart_rate' => $updatedUser['heart_rate'] ? (int)$updatedUser['heart_rate'] : null,
                    'last_health_check' => $updatedUser['last_health_check'],
                    'created_at' => $updatedUser['created_at'],
                    'updated_at' => $updatedUser['updated_at']
                ]
            ];
            
            // Debug: log response data
            error_log('Update user response: ' . json_encode($responseData));
            
            sendSuccessResponse($responseData, 'User data updated successfully');
        } else {
            sendErrorResponse('Failed to get updated user data', 'Database error', 500);
        }
    } else {
        sendErrorResponse('Failed to update user data', 'Database error', 500);
    }
    
} catch (Exception $e) {
    error_log('Update user data error: ' . $e->getMessage());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?>
