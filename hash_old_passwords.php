<?php
// Script để hash password cho user cũ
require_once 'config.php';

try {
    // Lấy kết nối database
    $conn = getDatabaseConnection();
    
    // Lấy tất cả user có password plain text (123123)
    $sql = "SELECT userId, password FROM user WHERE password = '123123'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($users) . " users with plain text password\n";
    
    // Hash password cho từng user
    $updateSql = "UPDATE user SET password = ? WHERE userId = ?";
    $updateStmt = $conn->prepare($updateSql);
    
    $hashedPassword = password_hash('123123', PASSWORD_DEFAULT);
    $updatedCount = 0;
    
    foreach ($users as $user) {
        $result = $updateStmt->execute([$hashedPassword, $user['userId']]);
        if ($result) {
            $updatedCount++;
            echo "Updated user ID: " . $user['userId'] . "\n";
        }
    }
    
    echo "Successfully updated " . $updatedCount . " users\n";
    echo "All users now have hashed password: 123123\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 