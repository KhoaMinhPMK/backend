<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

echo "=== Emergency Contact API Test ===\n\n";

// Test 1: Lưu số khẩn cấp
echo "1. Testing save_emergency_contact.php\n";
$testData = [
    'user_email' => 'test@example.com',
    'emergency_number' => '0902716951',
    'contact_name' => 'Số khẩn cấp test'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/backend/save_emergency_contact.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

// Test 2: Lấy số khẩn cấp
echo "2. Testing get_emergency_contact.php\n";
$email = urlencode('test@example.com');
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/backend/get_emergency_contact.php?email=$email");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
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