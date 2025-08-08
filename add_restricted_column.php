<?php
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if column already exists
    $checkStmt = $pdo->prepare("SHOW COLUMNS FROM user LIKE 'restricted_contents'");
    $checkStmt->execute();
    $columnExists = $checkStmt->fetch();
    
    if ($columnExists) {
        echo "Column 'restricted_contents' already exists.\n";
    } else {
        // Add the column
        $stmt = $pdo->prepare("ALTER TABLE user ADD COLUMN restricted_contents json DEFAULT NULL COMMENT 'Array of keywords that this elderly user should not watch' AFTER last_health_check");
        $stmt->execute();
        echo "Column 'restricted_contents' added successfully.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 