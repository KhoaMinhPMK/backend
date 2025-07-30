<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

try {
    // Kiểm tra method
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Method not allowed', 405);
    }

    // Lấy email từ query parameter
    $email = $_GET['email'] ?? '';
    
    if (empty($email)) {
        throw new Exception('Email is required', 400);
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format', 400);
    }

    // Kết nối database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query để lấy nhắc nhở theo email
    $sql = "SELECT 
                id,
                email_nguoi_dung,
                ten_nguoi_dung,
                thoi_gian,
                ngay_gio,
                noi_dung,
                trang_thai,
                ngay_tao,
                ngay_cap_nhat
            FROM nhac_nho 
            WHERE email_nguoi_dung = :email 
            ORDER BY ngay_gio ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format data để trả về
    $formattedReminders = [];
    foreach ($reminders as $reminder) {
        // Xác định loại nhắc nhở dựa trên nội dung
        $type = 'other';
        $content = strtolower($reminder['noi_dung']);
        
        if (strpos($content, 'thuốc') !== false || strpos($content, 'uống') !== false) {
            $type = 'medicine';
        } elseif (strpos($content, 'tập') !== false || strpos($content, 'thể dục') !== false) {
            $type = 'exercise';
        } elseif (strpos($content, 'khám') !== false || strpos($content, 'bệnh') !== false) {
            $type = 'appointment';
        } elseif (strpos($content, 'gọi') !== false || strpos($content, 'điện') !== false) {
            $type = 'call';
        }

        // Format ngày giờ
        $dateTime = new DateTime($reminder['ngay_gio']);
        $now = new DateTime();
        $today = new DateTime('today');
        $tomorrow = new DateTime('tomorrow');
        
        $dateLabel = '';
        if ($dateTime->format('Y-m-d') === $today->format('Y-m-d')) {
            $dateLabel = 'Hôm nay';
        } elseif ($dateTime->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
            $dateLabel = 'Ngày mai';
        } else {
            $dateLabel = $dateTime->format('d/m/Y');
        }

        $formattedReminders[] = [
            'id' => $reminder['id'],
            'type' => $type,
            'title' => $reminder['noi_dung'],
            'time' => $dateTime->format('H:i'),
            'date' => $dateLabel,
            'content' => $reminder['noi_dung'],
            'isCompleted' => $reminder['trang_thai'] === 'da_thuc_hien',
            'createdAt' => $reminder['ngay_tao'],
            'updatedAt' => $reminder['ngay_cap_nhat']
        ];
    }

    // Trả về response thành công
    echo json_encode([
        'success' => true,
        'data' => $formattedReminders,
        'message' => 'Lấy nhắc nhở thành công'
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