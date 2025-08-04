<?php
// File đơn giản để tạo bảng emergency_contacts
// Chạy trên server: https://viegrand.site/backend/create_emergency_table_simple.php

header('Content-Type: application/json');

try {
    require_once 'config.php';
    
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database successfully\n\n";
    
    // SQL để tạo bảng
    $sql = "
    CREATE TABLE IF NOT EXISTS emergency_contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_email VARCHAR(255) NOT NULL,
        emergency_number VARCHAR(20) NOT NULL,
        contact_name VARCHAR(100) DEFAULT 'Số khẩn cấp',
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_emergency (user_email)
    )";
    
    // Thực thi SQL
    $pdo->exec($sql);
    echo "✅ Table 'emergency_contacts' created successfully\n\n";
    
    // Tạo indexes
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_user_email ON emergency_contacts(user_email)",
        "CREATE INDEX IF NOT EXISTS idx_is_active ON emergency_contacts(is_active)"
    ];
    
    foreach ($indexes as $indexSql) {
        try {
            $pdo->exec($indexSql);
            echo "✅ Index created successfully\n";
        } catch (PDOException $e) {
            echo "⚠️ Index might already exist: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    
    // Thêm dữ liệu mẫu
    $sampleData = [
        'user_email' => 'pmkkhoaminh@gmail.com',
        'emergency_number' => '0902716951',
        'contact_name' => 'Số khẩn cấp'
    ];
    
    $insertSql = "INSERT INTO emergency_contacts (user_email, emergency_number, contact_name) 
                   VALUES (?, ?, ?) 
                   ON DUPLICATE KEY UPDATE 
                   emergency_number = VALUES(emergency_number),
                   contact_name = VALUES(contact_name),
                   updated_at = CURRENT_TIMESTAMP";
    
    $stmt = $pdo->prepare($insertSql);
    $result = $stmt->execute([
        $sampleData['user_email'],
        $sampleData['emergency_number'],
        $sampleData['contact_name']
    ]);
    
    if ($result) {
        echo "✅ Sample data inserted successfully\n";
    } else {
        echo "⚠️ Sample data might already exist\n";
    }
    
    // Kiểm tra bảng đã tạo
    $checkSql = "DESCRIBE emergency_contacts";
    $stmt = $pdo->query($checkSql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📋 Table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']}\n";
    }
    
    // Kiểm tra dữ liệu
    $dataSql = "SELECT * FROM emergency_contacts";
    $stmt = $pdo->query($dataSql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📊 Current data:\n";
    foreach ($data as $row) {
        echo "- Email: {$row['user_email']}, Number: {$row['emergency_number']}, Name: {$row['contact_name']}\n";
    }
    
    echo "\n🎉 Emergency contacts table setup completed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 