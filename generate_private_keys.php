<?php
// Script để tạo private key cho các user hiện tại
require_once 'config.php';

try {
    // Get database connection
    $conn = getDatabaseConnection();

    // Lấy tất cả user chưa có private_key
    $sql = "SELECT userId, userName, email FROM user WHERE private_key IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($users) . " users without private_key\n";

    if (count($users) > 0) {
        // Tạo private key cho từng user
        $updateSql = "UPDATE user SET private_key = ? WHERE userId = ?";
        $updateStmt = $conn->prepare($updateSql);

        $updatedCount = 0;

        foreach ($users as $user) {
            // Tạo private key 8 chữ số ngẫu nhiên
            $privateKey = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
            
            $result = $updateStmt->execute([$privateKey, $user['userId']]);
            if ($result) {
                $updatedCount++;
                echo "Updated user ID: " . $user['userId'] . " (" . $user['userName'] . ") - Private Key: " . $privateKey . "\n";
            }
        }

        echo "Successfully updated " . $updatedCount . " users with private keys\n";
    } else {
        echo "All users already have private keys\n";
    }

    // Hiển thị thống kê
    $statsSql = "SELECT 
                    COUNT(*) as total_users,
                    COUNT(private_key) as users_with_key,
                    COUNT(*) - COUNT(private_key) as users_without_key
                  FROM user";
    $statsStmt = $conn->prepare($statsSql);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    echo "\n=== STATISTICS ===\n";
    echo "Total users: " . $stats['total_users'] . "\n";
    echo "Users with private key: " . $stats['users_with_key'] . "\n";
    echo "Users without private key: " . $stats['users_without_key'] . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 