<?php
require_once 'config.php';

setCorsHeaders();

// Chỉ cho phép POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Method not allowed', 'Method not allowed', 405);
    exit;
}

try {
    $conn = getDatabaseConnection();
    
    // Bắt đầu transaction
    $conn->beginTransaction();
    
    // Tạo 10 user elderly với tuổi từ 5 đến 15
    $elderlyUsers = [
        ['Nguyễn Văn An', 'an.nguyen@test.com', '1111111111', 5, 'male', 'abc123def'],
        ['Trần Thị Bình', 'binh.tran@test.com', '1111111112', 6, 'female', 'def456ghi'],
        ['Lê Văn Cường', 'cuong.le@test.com', '1111111113', 7, 'male', 'ghi789jkl'],
        ['Phạm Thị Dung', 'dung.pham@test.com', '1111111114', 8, 'female', 'jkl012mno'],
        ['Hoàng Văn Em', 'em.hoang@test.com', '1111111115', 9, 'male', 'mno345pqr'],
        ['Vũ Thị Phương', 'phuong.vu@test.com', '1111111116', 10, 'female', 'pqr678stu'],
        ['Đặng Văn Giang', 'giang.dang@test.com', '1111111117', 11, 'male', 'stu901vwx'],
        ['Ngô Thị Hoa', 'hoa.ngo@test.com', '1111111118', 12, 'female', 'vwx234yza'],
        ['Lý Văn Ích', 'ich.ly@test.com', '1111111119', 13, 'male', 'yza567bcd'],
        ['Hồ Thị Kim', 'kim.ho@test.com', '1111111120', 14, 'female', 'bcd890efg']
    ];
    
    $elderlySql = "INSERT INTO user (userName, email, phone, password, role, age, gender, private_key) VALUES (?, ?, ?, ?, 'elderly', ?, ?, ?)";
    $stmt = $conn->prepare($elderlySql);
    
    $elderlyCount = 0;
    foreach ($elderlyUsers as $user) {
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        $stmt->execute([$user[0], $user[1], $user[2], $hashedPassword, $user[3], $user[4], $user[5]]);
        $elderlyCount++;
    }
    
    // Tạo 5 user relative
    $relativeUsers = [
        ['Nguyễn Văn Relative1', 'relative1@test.com', '2222222221', 25, 'male', 'rel123abc'],
        ['Trần Thị Relative2', 'relative2@test.com', '2222222222', 30, 'female', 'rel456def'],
        ['Lê Văn Relative3', 'relative3@test.com', '2222222223', 35, 'male', 'rel789ghi'],
        ['Phạm Thị Relative4', 'relative4@test.com', '2222222224', 28, 'female', 'rel012jkl'],
        ['Hoàng Văn Relative5', 'relative5@test.com', '2222222225', 32, 'male', 'rel345mno']
    ];
    
    $relativeSql = "INSERT INTO user (userName, email, phone, password, role, age, gender, private_key) VALUES (?, ?, ?, ?, 'relative', ?, ?, ?)";
    $stmt = $conn->prepare($relativeSql);
    
    $relativeCount = 0;
    foreach ($relativeUsers as $user) {
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        $stmt->execute([$user[0], $user[1], $user[2], $hashedPassword, $user[3], $user[4], $user[5]]);
        $relativeCount++;
    }
    
    // Tạo premium subscriptions cho relatives
    $premiumSql = "INSERT INTO premium_subscriptions (user_id, premium_key, start_date, end_date, status, note) VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 'active', 'Test premium subscription')";
    $stmt = $conn->prepare($premiumSql);
    
    $premiumCount = 0;
    for ($i = 1; $i <= 5; $i++) {
        $userId = $elderlyCount + $relativeCount + $i; // user_id sẽ là 11, 12, 13, 14, 15
        $premiumKey = 'PREMIUM00' . $i;
        $stmt->execute([$userId, $premiumKey]);
        $premiumCount++;
    }
    
    // Commit transaction
    $conn->commit();
    
    $responseData = [
        'elderly_users_created' => $elderlyCount,
        'relative_users_created' => $relativeCount,
        'premium_subscriptions_created' => $premiumCount,
        'total_users_created' => $elderlyCount + $relativeCount,
        'elderly_users' => $elderlyUsers,
        'relative_users' => $relativeUsers
    ];
    
    sendSuccessResponse($responseData, 'Test users created successfully');
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    error_log('Create test users error: ' . $e->getMessage());
    sendErrorResponse('Failed to create test users: ' . $e->getMessage(), 'Internal server error', 500);
}
?>
