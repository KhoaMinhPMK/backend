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
    $email = $input['email'] ?? '';
    $tenNguoiDung = $input['ten_nguoi_dung'] ?? '';
    $noiDung = $input['noi_dung'] ?? '';
    $ngayGio = $input['ngay_gio'] ?? '';
    $thoiGian = $input['thoi_gian'] ?? '';
    $privateKeyNguoiNhan = $input['private_key_nguoi_nhan'] ?? '';
    
    if (empty($email) || empty($tenNguoiDung) || empty($noiDung) || empty($ngayGio) || empty($thoiGian) || empty($privateKeyNguoiNhan)) {
        throw new Exception('Missing required fields: email, ten_nguoi_dung, noi_dung, ngay_gio, thoi_gian, private_key_nguoi_nhan', 400);
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format', 400);
    }

    // Validate datetime
    $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $ngayGio);
    if (!$dateTime) {
        throw new Exception('Invalid ngay_gio format. Expect Y-m-d H:i:s', 400);
    }
    $time = DateTime::createFromFormat('H:i:s', $thoiGian);
    if (!$time) {
        throw new Exception('Invalid thoi_gian format. Expect H:i:s', 400);
    }

    // Kết nối database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Thêm nhắc nhở mới
    $sql = "INSERT INTO nhac_nho (email_nguoi_dung, ten_nguoi_dung, thoi_gian, ngay_gio, noi_dung, trang_thai, private_key_nguoi_nhan) VALUES (:email, :ten_nguoi_dung, :thoi_gian, :ngay_gio, :noi_dung, 'chua_thuc_hien', :private_key_nguoi_nhan)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':ten_nguoi_dung', $tenNguoiDung, PDO::PARAM_STR);
    $stmt->bindParam(':thoi_gian', $thoiGian, PDO::PARAM_STR);
    $stmt->bindParam(':ngay_gio', $ngayGio, PDO::PARAM_STR);
    $stmt->bindParam(':noi_dung', $noiDung, PDO::PARAM_STR);
    $stmt->bindParam(':private_key_nguoi_nhan', $privateKeyNguoiNhan, PDO::PARAM_STR);
    $stmt->execute();

    $reminderId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Thêm nhắc nhở thành công',
        'data' => [
            'reminder_id' => $reminderId,
            'email' => $email,
            'ten_nguoi_dung' => $tenNguoiDung,
            'noi_dung' => $noiDung,
            'ngay_gio' => $ngayGio,
            'thoi_gian' => $thoiGian,
            'private_key_nguoi_nhan' => $privateKeyNguoiNhan
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
