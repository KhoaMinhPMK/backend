<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

try {
    // Kiểm tra method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }

    // Lấy JSON data từ request body
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data', 400);
    }

    // Validate required fields
    $reminderId = $input['reminder_id'] ?? '';
    $status = $input['status'] ?? ''; // 'completed' hoặc 'pending'
    $email = $input['email'] ?? '';
    
    if (empty($reminderId) || empty($status) || empty($email)) {
        throw new Exception('Missing required fields: reminder_id, status, email', 400);
    }

    // Validate status
    if (!in_array($status, ['completed', 'pending'])) {
        throw new Exception('Invalid status. Must be "completed" or "pending"', 400);
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format', 400);
    }

    // Kết nối database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Kiểm tra xem nhắc nhở có tồn tại và thuộc về email này không
    $checkSql = "SELECT id FROM nhac_nho WHERE id = :id AND email_nguoi_dung = :email";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindParam(':id', $reminderId, PDO::PARAM_INT);
    $checkStmt->bindParam(':email', $email, PDO::PARAM_STR);
    $checkStmt->execute();

    if ($checkStmt->rowCount() === 0) {
        throw new Exception('Reminder not found or not authorized', 404);
    }

    // Map status từ English sang Vietnamese
    $vietnameseStatus = $status === 'completed' ? 'da_thuc_hien' : 'chua_thuc_hien';

    // Update trạng thái nhắc nhở
    $updateSql = "UPDATE nhac_nho 
                  SET trang_thai = :status, 
                      ngay_cap_nhat = NOW() 
                  WHERE id = :id AND email_nguoi_dung = :email";

    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->bindParam(':status', $vietnameseStatus, PDO::PARAM_STR);
    $updateStmt->bindParam(':id', $reminderId, PDO::PARAM_INT);
    $updateStmt->bindParam(':email', $email, PDO::PARAM_STR);
    $updateStmt->execute();

    if ($updateStmt->rowCount() === 0) {
        throw new Exception('Failed to update reminder status', 500);
    }

    // Trả về response thành công
    echo json_encode([
        'success' => true,
        'message' => $status === 'completed' ? 'Đã đánh dấu hoàn thành' : 'Đã đánh dấu chưa hoàn thành',
        'data' => [
            'reminder_id' => $reminderId,
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (PDOException $e) {
    // Lỗi database
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Lỗi khác
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 