<?php
// File để chạy test emergency contact API thật
// Chạy bằng: php run_emergency_test.php

require_once 'config.php';

echo "=== Emergency Contact API Test ===\n\n";

// Test 1: Lưu số khẩn cấp
echo "1. Testing save_emergency_contact.php\n";

// Tạo dữ liệu test
$testData = [
    'user_email' => 'test@example.com',
    'emergency_number' => '0902716951',
    'contact_name' => 'Số khẩn cấp test'
];

// Gọi API save
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://viegrand.site/backend/save_emergency_contact.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "CURL Error: $error\n";
}
echo "Response: $response\n\n";

// Test 2: Lấy số khẩn cấp
echo "2. Testing get_emergency_contact.php\n";

$email = urlencode('test@example.com');
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://viegrand.site/backend/get_emergency_contact.php?email=$email");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "CURL Error: $error\n";
}
echo "Response: $response\n\n";

// Test 3: Kiểm tra database trực tiếp
echo "3. Testing database directly\n";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM emergency_contacts WHERE user_email = ?");
    $stmt->execute(['test@example.com']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "Database record found:\n";
        print_r($result);
    } else {
        echo "No database record found for test@example.com\n";
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?> 