<?php
// Test script to debug private key issue

// Simple database connection test without config.php
$host = 'localhost';
$dbname = 'viegrand';
$username = 'root';
$password = '';

// Test data
$testData = [
    'userName' => 'Test User',
    'email' => 'test@example.com',
    'phone' => '1234567890',
    'password' => 'test123',
    'role' => 'user',
    'privateKey' => 'test-private-key-12345'
];

echo "Testing private key insertion...\n";
echo "Test data: " . json_encode($testData) . "\n";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Prepare the same SQL as in register.php
    $sql = "INSERT INTO user (
        userName, 
        email, 
        phone,
        password,
        role,
        private_key
    ) VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo "Error preparing statement: " . $conn->errorInfo()[2] . "\n";
        exit;
    }
    
    $hashedPassword = password_hash($testData['password'], PASSWORD_DEFAULT);
    
    // Execute with parameters
    $result = $stmt->execute([
        $testData['userName'], 
        $testData['email'], 
        $testData['phone'],
        $hashedPassword,
        $testData['role'],
        $testData['privateKey']
    ]);
    
    if ($result) {
        $userId = $conn->lastInsertId();
        echo "Success! User inserted with ID: $userId\n";
        
        // Query back to verify private_key was saved
        $checkSql = "SELECT userId, userName, email, phone, role, private_key FROM user WHERE userId = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->execute([$userId]);
        $userData = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Verification data: " . json_encode($userData) . "\n";
        
        // Clean up - delete the test user
        $deleteSql = "DELETE FROM user WHERE userId = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->execute([$userId]);
        echo "Test user deleted.\n";
        
    } else {
        echo "Failed to insert user\n";
        echo "Error info: " . json_encode($stmt->errorInfo()) . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
