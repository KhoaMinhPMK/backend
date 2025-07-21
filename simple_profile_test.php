<?php
// simple_profile_test.php - Test API cập nhật profile đơn giản

// Thiết lập test environment
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Test API Profile Update</h1>";

// Kiểm tra file config
if (!file_exists('config.php')) {
    die("<p style='color: red'>❌ File config.php không tồn tại!</p>");
}

require_once 'config.php';

echo "<h2>1. Test Database Connection</h2>";
try {
    $pdo = getDatabaseConnection();
    echo "<p style='color: green'>✅ Kết nối database thành công!</p>";
    
    // Kiểm tra bảng users
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Cấu trúc bảng users:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red'>❌ Lỗi database: " . $e->getMessage() . "</p>";
    die();
}

echo "<h2>2. Test Create Test User</h2>";
try {
    // Tạo user test nếu chưa tồn tại
    $testEmail = 'test@viegrand.com';
    
    // Kiểm tra user đã tồn tại chưa
    $stmt = $pdo->prepare("SELECT id, fullName, email FROM users WHERE email = ?");
    $stmt->execute([$testEmail]);
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testUser) {
        // Tạo user mới
        $hashedPassword = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (fullName, email, password, role, active, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            'Test User',
            $testEmail,
            $hashedPassword,
            'elderly',
            1,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s')
        ]);
        
        $userId = $pdo->lastInsertId();
        echo "<p style='color: green'>✅ Tạo test user thành công! ID: $userId</p>";
        
        // Lấy thông tin user vừa tạo
        $stmt = $pdo->prepare("SELECT id, fullName, email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        echo "<p style='color: blue'>ℹ️ Test user đã tồn tại: " . htmlspecialchars($testUser['fullName']) . " (" . htmlspecialchars($testUser['email']) . ")</p>";
    }
    
    // Tạo test token
    $testToken = $testUser['id'] . '_' . 'test_token_123456';
    
    echo "<p><strong>Test User Info:</strong></p>";
    echo "<ul>";
    echo "<li>ID: " . htmlspecialchars($testUser['id']) . "</li>";
    echo "<li>Name: " . htmlspecialchars($testUser['fullName']) . "</li>";
    echo "<li>Email: " . htmlspecialchars($testUser['email']) . "</li>";
    echo "<li>Test Token: " . htmlspecialchars($testToken) . "</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red'>❌ Lỗi tạo test user: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Test API Endpoints</h2>";
echo "<div style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
echo "<h3>CURL Commands để test:</h3>";

echo "<h4>📖 GET Profile:</h4>";
echo "<code style='background: white; padding: 5px; display: block; margin: 5px 0;'>";
echo "curl -X GET \"http://localhost/viegrandApp/backendphp/get_profile.php\" \\<br>";
echo "&nbsp;&nbsp;-H \"Content-Type: application/json\" \\<br>";
echo "&nbsp;&nbsp;-H \"Authorization: Bearer " . $testToken . "\"";
echo "</code>";

echo "<h4>✏️ UPDATE Profile:</h4>";
echo "<code style='background: white; padding: 5px; display: block; margin: 5px 0;'>";
echo "curl -X PUT \"http://localhost/viegrandApp/backendphp/update_profile.php\" \\<br>";
echo "&nbsp;&nbsp;-H \"Content-Type: application/json\" \\<br>";
echo "&nbsp;&nbsp;-H \"Authorization: Bearer " . $testToken . "\" \\<br>";
echo "&nbsp;&nbsp;-d '{<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;\"fullName\": \"Test User Updated\",<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;\"phone\": \"0123456789\",<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;\"age\": 30,<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;\"address\": \"123 Test Street\",<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;\"gender\": \"Nam\"<br>";
echo "&nbsp;&nbsp;}'";
echo "</code>";

echo "</div>";

echo "<h2>4. Quick File Check</h2>";
$files = [
    'get_profile.php' => 'GET Profile API',
    'update_profile.php' => 'UPDATE Profile API',
    'config.php' => 'Configuration',
    'login.php' => 'Login API'
];

foreach ($files as $file => $description) {
    if (file_exists($file)) {
        echo "<p style='color: green'>✅ $description ($file) - OK</p>";
    } else {
        echo "<p style='color: red'>❌ $description ($file) - MISSING</p>";
    }
}

echo "<h2>5. Next Steps</h2>";
echo "<ol>";
echo "<li>Chạy XAMPP/WAMP để khởi động Apache và MySQL</li>";
echo "<li>Đảm bảo database 'viegrand_app' đã được tạo</li>";
echo "<li>Chạy các CURL commands ở trên để test API</li>";
echo "<li>Hoặc sử dụng Postman/Insomnia để test</li>";
echo "<li>Kiểm tra React Native app có gọi API đúng không</li>";
echo "</ol>";

echo "<p><em>File created: " . __FILE__ . "</em></p>";
?>
