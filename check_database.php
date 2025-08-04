<?php
// File đơn giản để kiểm tra database
// Chạy trên server: https://viegrand.site/backend/check_database.php

header('Content-Type: application/json');

try {
    require_once 'config.php';
    
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $result = [
        'success' => true,
        'database' => [
            'host' => DB_HOST,
            'database' => DB_NAME,
            'connected' => true
        ],
        'tables' => []
    ];
    
    // Kiểm tra bảng emergency_contacts
    $stmt = $pdo->query("SHOW TABLES LIKE 'emergency_contacts'");
    $emergencyTableExists = $stmt->rowCount() > 0;
    
    $result['tables']['emergency_contacts'] = [
        'exists' => $emergencyTableExists
    ];
    
    if ($emergencyTableExists) {
        // Kiểm tra cấu trúc bảng
        $stmt = $pdo->query("DESCRIBE emergency_contacts");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result['tables']['emergency_contacts']['structure'] = $columns;
        
        // Kiểm tra dữ liệu
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM emergency_contacts");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $result['tables']['emergency_contacts']['record_count'] = $count;
    }
    
    // Kiểm tra bảng user
    $stmt = $pdo->query("SHOW TABLES LIKE 'user'");
    $userTableExists = $stmt->rowCount() > 0;
    
    $result['tables']['user'] = [
        'exists' => $userTableExists
    ];
    
    if ($userTableExists) {
        // Kiểm tra có user nào không
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM user");
        $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $result['tables']['user']['record_count'] = $userCount;
        
        if ($userCount > 0) {
            // Lấy một số email mẫu
            $stmt = $pdo->query("SELECT email FROM user LIMIT 5");
            $emails = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $result['tables']['user']['sample_emails'] = $emails;
        }
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 