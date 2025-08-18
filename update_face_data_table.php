<?php
require_once 'config.php';

try {
    $pdo = getDatabaseConnection();
    
    echo "🔄 Updating face_data table...\n";
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'face_data'");
    if ($stmt->rowCount() == 0) {
        echo "❌ Table 'face_data' does not exist. Please run create_face_data_table.php first.\n";
        exit;
    }
    
    // Check if private_key column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM face_data LIKE 'private_key'");
    if ($stmt->rowCount() == 0) {
        echo "➕ Adding private_key column...\n";
        $pdo->exec("ALTER TABLE face_data ADD COLUMN private_key varchar(255) NOT NULL DEFAULT '' AFTER status");
        echo "✅ private_key column added\n";
    } else {
        echo "ℹ️ private_key column already exists\n";
    }
    
    // Check if is_appended column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM face_data LIKE 'is_appended'");
    if ($stmt->rowCount() == 0) {
        echo "➕ Adding is_appended column...\n";
        $pdo->exec("ALTER TABLE face_data ADD COLUMN is_appended tinyint(1) DEFAULT 0 AFTER private_key");
        echo "✅ is_appended column added\n";
    } else {
        echo "ℹ️ is_appended column already exists\n";
    }
    
    // Add indexes if they don't exist
    $indexes = [
        'idx_face_data_private_key' => 'private_key',
        'idx_face_data_private_key_upload_date' => 'private_key, upload_date'
    ];
    
    foreach ($indexes as $indexName => $columns) {
        $stmt = $pdo->query("SHOW INDEX FROM face_data WHERE Key_name = '$indexName'");
        if ($stmt->rowCount() == 0) {
            echo "➕ Adding index $indexName...\n";
            $pdo->exec("CREATE INDEX $indexName ON face_data ($columns)");
            echo "✅ Index $indexName added\n";
        } else {
            echo "ℹ️ Index $indexName already exists\n";
        }
    }
    
    // Update existing records to have private_key if they don't have it
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM face_data WHERE private_key = '' OR private_key IS NULL");
    $result = $stmt->fetch();
    if ($result['count'] > 0) {
        echo "🔄 Updating existing records with private_key...\n";
        
        // Get all records without private_key
        $stmt = $pdo->query("SELECT id, user_id FROM face_data WHERE private_key = '' OR private_key IS NULL");
        $records = $stmt->fetchAll();
        
        foreach ($records as $record) {
            // Get user's private_key
            $userStmt = $pdo->prepare("SELECT private_key FROM user WHERE userId = ?");
            $userStmt->execute([$record['user_id']]);
            $user = $userStmt->fetch();
            
            if ($user && $user['private_key']) {
                $updateStmt = $pdo->prepare("UPDATE face_data SET private_key = ? WHERE id = ?");
                $updateStmt->execute([$user['private_key'], $record['id']]);
                echo "✅ Updated record ID {$record['id']} with private_key\n";
            } else {
                echo "⚠️ Could not find private_key for user_id {$record['user_id']}\n";
            }
        }
    }
    
    echo "✅ face_data table update completed successfully!\n";
    
    // Show final table structure
    echo "\n📋 Final table structure:\n";
    $stmt = $pdo->query("DESCRIBE face_data");
    while ($row = $stmt->fetch()) {
        echo "- {$row['Field']}: {$row['Type']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 