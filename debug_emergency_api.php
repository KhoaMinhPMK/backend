<?php
// File debug để kiểm tra chi tiết lỗi API emergency contact
// Chạy: php debug_emergency_api.php

echo "=== Debug Emergency Contact API ===\n\n";

// 1. Kiểm tra file config
echo "1. Checking config.php...\n";
if (file_exists('config.php')) {
    echo "✅ config.php exists\n";
    require_once 'config.php';
    echo "✅ config.php loaded successfully\n";
} else {
    echo "❌ config.php not found\n";
    exit;
}

// 2. Kiểm tra database connection
echo "\n2. Testing database connection...\n";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful\n";
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

// 3. Kiểm tra bảng emergency_contacts
echo "\n3. Checking emergency_contacts table...\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'emergency_contacts'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✅ emergency_contacts table exists\n";
        
        // Kiểm tra cấu trúc bảng
        $stmt = $pdo->query("DESCRIBE emergency_contacts");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "📋 Table structure:\n";
        foreach ($columns as $column) {
            echo "- {$column['Field']}: {$column['Type']}\n";
        }
    } else {
        echo "❌ emergency_contacts table does not exist\n";
        echo "Creating table...\n";
        
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
        
        $pdo->exec($sql);
        echo "✅ emergency_contacts table created\n";
    }
} catch (PDOException $e) {
    echo "❌ Error checking table: " . $e->getMessage() . "\n";
}

// 4. Kiểm tra bảng users
echo "\n4. Checking users table...\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $usersTableExists = $stmt->rowCount() > 0;
    
    if ($usersTableExists) {
        echo "✅ users table exists\n";
        
        // Kiểm tra có user nào không
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "📊 Total users: $userCount\n";
        
        if ($userCount > 0) {
            $stmt = $pdo->query("SELECT email FROM users LIMIT 5");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "📧 Sample emails:\n";
            foreach ($users as $user) {
                echo "- {$user['email']}\n";
            }
        }
    } else {
        echo "❌ users table does not exist\n";
    }
} catch (PDOException $e) {
    echo "❌ Error checking users table: " . $e->getMessage() . "\n";
}

// 5. Test API với dữ liệu thật
echo "\n5. Testing API with real data...\n";

// Lấy email thật từ database
try {
    $stmt = $pdo->query("SELECT email FROM users LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $testEmail = $user['email'];
        echo "✅ Using real email: $testEmail\n";
        
        $testData = [
            'user_email' => $testEmail,
            'emergency_number' => '0902716951',
            'contact_name' => 'Số khẩn cấp test'
        ];
        
        echo "📤 Sending data: " . json_encode($testData) . "\n";
        
        // Test API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://viegrand.site/backend/save_emergency_contact.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        echo "📥 Response:\n";
        echo "HTTP Code: $httpCode\n";
        if ($error) {
            echo "CURL Error: $error\n";
        }
        echo "Response Body: $response\n";
        
    } else {
        echo "❌ No users found in database\n";
    }
} catch (PDOException $e) {
    echo "❌ Error getting test user: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";
?> 