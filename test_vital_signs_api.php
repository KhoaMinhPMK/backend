<?php
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Only allow GET requests for testing
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendErrorResponse('Method not allowed', 'Only GET method is allowed for testing', 405);
    exit;
}

try {
    // Get database connection
    $pdo = getDatabaseConnection();
    
    // Test 1: Check if vital_signs table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'vital_signs'");
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        // Create the table if it doesn't exist
        $createTableSQL = "CREATE TABLE `vital_signs` (
            `private_key` varchar(255) DEFAULT NULL,
            `blood_pressure_systolic` int(11) DEFAULT NULL COMMENT 'Huyết áp tâm thu (mmHg)',
            `blood_pressure_diastolic` int(11) DEFAULT NULL COMMENT 'Huyết áp tâm trương (mmHg)',
            `heart_rate` int(11) DEFAULT NULL COMMENT 'Nhịp tim (bpm)'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $pdo->exec($createTableSQL);
        $table_exists = true;
    }
    
    // Test 2: Check table structure
    $stmt = $pdo->query("DESCRIBE vital_signs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Test 3: Get sample data (if any)
    $stmt = $pdo->query("SELECT * FROM vital_signs LIMIT 5");
    $sample_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Test 4: Check if there are any users with private keys
    $stmt = $pdo->query("SELECT userId, private_key FROM user WHERE private_key IS NOT NULL LIMIT 3");
    $users_with_keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Test 5: Prepare test data
    $test_data = [
        'private_key' => $users_with_keys[0]['private_key'] ?? 'test_key_123',
        'blood_pressure_systolic' => 120,
        'blood_pressure_diastolic' => 80,
        'heart_rate' => 72
    ];
    
    sendSuccessResponse([
        'table_exists' => $table_exists,
        'table_structure' => $columns,
        'sample_data_count' => count($sample_data),
        'sample_data' => $sample_data,
        'users_with_private_keys_count' => count($users_with_keys),
        'users_with_private_keys' => $users_with_keys,
        'test_data' => $test_data,
        'message' => 'Vital signs API test completed successfully'
    ], 'Vital signs API is ready for use');
    
} catch (PDOException $e) {
    logError('Database error in test_vital_signs_api', [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
    sendErrorResponse('Database error', 'Database error: ' . $e->getMessage(), 500);
    
} catch (Exception $e) {
    logError('Unexpected error in test_vital_signs_api', [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
    sendErrorResponse('Server error', 'Server error: ' . $e->getMessage(), 500);
}
?> 