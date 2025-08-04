<?php
// File test đơn giản cho emergency contact API
// Chạy: php simple_test.php

echo "=== Simple Emergency Contact Test ===\n\n";

// Test 1: Lưu số khẩn cấp
echo "1. Testing SAVE API...\n";

$data = [
    'user_email' => 'test@example.com',
    'emergency_number' => '0902716951',
    'contact_name' => 'Số khẩn cấp test'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://viegrand.site/backend/save_emergency_contact.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $httpCode\n";
echo "Response: $response\n\n";

// Test 2: Lấy số khẩn cấp
echo "2. Testing GET API...\n";

$email = urlencode('test@example.com');
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://viegrand.site/backend/get_emergency_contact.php?email=$email");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $httpCode\n";
echo "Response: $response\n\n";

echo "=== Test Complete ===\n";
?> 