<?php
require_once 'config.php';

try {
    $pdo = getDatabaseConnection();
    
    // Read and execute the SQL file
    $sqlFile = 'sql/create_face_data_table.sql';
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        throw new Exception("Could not read SQL file: $sqlFile");
    }
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
            echo "Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "✅ Face data table created successfully!\n";
    
    // Verify table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'face_data'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table 'face_data' exists in database\n";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE face_data");
        echo "\nTable structure:\n";
        while ($row = $stmt->fetch()) {
            echo "- {$row['Field']}: {$row['Type']}\n";
        }
    } else {
        echo "❌ Table 'face_data' was not created\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 