<?php
// Debug script to test registration and see what's stored in database
require_once 'config.php';

try {
    $conn = getDatabaseConnection();
    
    // Test 1: Check if we can connect to database
    echo "✅ Database connection successful\n";
    
    // Test 2: Check the latest user record
    $sql = "SELECT userId, userName, email, phone, role, private_key FROM user ORDER BY userId DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "\n📊 Latest user record:\n";
        echo "User ID: {$result['userId']}\n";
        echo "User Name: {$result['userName']}\n";
        echo "Email: {$result['email']}\n";
        echo "Phone: {$result['phone']}\n";
        echo "Role: {$result['role']}\n";
        echo "Private Key: " . ($result['private_key'] ?: 'NULL') . "\n";
    } else {
        echo "❌ No users found in database\n";
    }
    
    // Test 3: Check all users with private_key
    $sql = "SELECT userId, userName, email, private_key FROM user WHERE private_key IS NOT NULL LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    echo "\n📋 Users with private_key:\n";
    if (count($results) > 0) {
        foreach ($results as $row) {
            echo "ID: {$row['userId']}, Name: {$row['userName']}, Email: {$row['email']}, Private Key: {$row['private_key']}\n";
        }
    } else {
        echo "No users with private_key found\n";
    }
    
    // Test 4: Check users without private_key
    $sql = "SELECT userId, userName, email FROM user WHERE private_key IS NULL LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    echo "\n📋 Users without private_key:\n";
    if (count($results) > 0) {
        foreach ($results as $row) {
            echo "ID: {$row['userId']}, Name: {$row['userName']}, Email: {$row['email']}\n";
        }
    } else {
        echo "All users have private_key\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 