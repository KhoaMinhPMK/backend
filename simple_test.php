<?php
// Simple test script to check vital signs functionality
header('Content-Type: application/json');

try {
    // Basic database connection test
    $pdo = new PDO("mysql:host=localhost;dbname=viegrand;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $result = [
        'database_connection' => 'success',
        'tests' => []
    ];
    
    // Test 1: Check if vital_signs table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'vital_signs'");
    $tableExists = $stmt->rowCount() > 0;
    $result['tests']['table_exists'] = $tableExists;
    
    if ($tableExists) {
        // Test 2: Check table structure
        $stmt = $pdo->query("DESCRIBE vital_signs");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result['tests']['table_structure'] = $columns;
        
        // Test 3: Check for sample data
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM vital_signs");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $result['tests']['data_count'] = $count;
    }
    
    // Test 4: Check if user table has private_key_nguoi_nhan field
    $stmt = $pdo->query("SHOW TABLES LIKE 'user'");
    $userTableExists = $stmt->rowCount() > 0;
    $result['tests']['user_table_exists'] = $userTableExists;
    
    if ($userTableExists) {
        $stmt = $pdo->query("DESCRIBE user");
        $userColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $hasPrivateKey = in_array('private_key_nguoi_nhan', $userColumns);
        $result['tests']['has_private_key_field'] = $hasPrivateKey;
        
        if ($hasPrivateKey) {
            // Test 5: Check for users with private keys
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM user WHERE private_key_nguoi_nhan IS NOT NULL");
            $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            $result['tests']['users_with_private_keys'] = $userCount;
        }
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
?> 