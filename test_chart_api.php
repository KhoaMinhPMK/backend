<?php
// Simple test for chart API
header('Content-Type: application/json');

try {
    // Test database connection
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
        
        // Test 3: Get sample data
        $stmt = $pdo->query("SELECT * FROM vital_signs ORDER BY created_at DESC LIMIT 3");
        $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result['tests']['sample_data'] = $sampleData;
        
        // Test 4: Test the get_vital_signs.php API
        $testPrivateKey = $sampleData[0]['private_key'] ?? 'test_key';
        
        // Simulate API call
        $period = '7d';
        $now = new DateTime();
        $start_date = $now->modify("-7 days")->format('Y-m-d H:i:s');
        
        $stmt = $pdo->prepare("
            SELECT id, private_key, blood_pressure_systolic, blood_pressure_diastolic, heart_rate, created_at
            FROM vital_signs 
            WHERE private_key = ? AND created_at >= ?
            ORDER BY created_at ASC
        ");
        
        $stmt->execute([$testPrivateKey, $start_date]);
        $apiResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result['tests']['api_simulation'] = [
            'private_key' => $testPrivateKey,
            'period' => $period,
            'start_date' => $start_date,
            'records_found' => count($apiResult),
            'sample_records' => array_slice($apiResult, 0, 2)
        ];
    }
    
    // Test 5: Check if get_vital_signs.php file exists
    $apiFileExists = file_exists('get_vital_signs.php');
    $result['tests']['api_file_exists'] = $apiFileExists;
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
?> 