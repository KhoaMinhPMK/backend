<?php
require_once 'config.php';

echo "🔧 Adding device_token column to user table...\n\n";

try {
    $conn = getDatabaseConnection();
    
    // Check if column already exists
    $checkColumnSql = "SHOW COLUMNS FROM user LIKE 'device_token'";
    $checkColumnStmt = $conn->prepare($checkColumnSql);
    $checkColumnStmt->execute();
    $columnExists = $checkColumnStmt->rowCount() > 0;
    
    if ($columnExists) {
        echo "✅ Device token column already exists!\n";
        exit;
    }
    
    // Add device_token column
    $addColumnSql = "ALTER TABLE user ADD COLUMN device_token VARCHAR(255) DEFAULT NULL COMMENT 'Firebase Cloud Messaging device token for push notifications'";
    $addColumnStmt = $conn->prepare($addColumnSql);
    $addColumnStmt->execute();
    
    echo "✅ Device token column added successfully!\n";
    
    // Add index for better performance
    $addIndexSql = "CREATE INDEX idx_device_token ON user (device_token)";
    $addIndexStmt = $conn->prepare($addIndexSql);
    $addIndexStmt->execute();
    
    echo "✅ Index created for device_token column!\n";
    
    // Show table structure
    echo "\n📋 Current user table structure:\n";
    $showColumnsSql = "SHOW COLUMNS FROM user";
    $showColumnsStmt = $conn->prepare($showColumnsSql);
    $showColumnsStmt->execute();
    $columns = $showColumnsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "   - {$column['Field']}: {$column['Type']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "🔧 Stack trace: " . $e->getTraceAsString() . "\n";
}
?> 