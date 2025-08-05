<?php
// Test script for private key functionality
require_once 'config.php';

try {
    $conn = getDatabaseConnection();
    
    // Test 1: Check if private_key column exists
    $sql = "SHOW COLUMNS FROM user LIKE 'private_key'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✅ private_key column exists\n";
        echo "Column details: " . json_encode($result) . "\n";
    } else {
        echo "❌ private_key column does not exist\n";
    }
    
    // Test 2: Check table structure
    $sql = "DESCRIBE user";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    echo "\n📋 User table structure:\n";
    foreach ($results as $row) {
        echo "Field: {$row['Field']}, Type: {$row['Type']}, Null: {$row['Null']}, Key: {$row['Key']}\n";
    }
    
    // Test 3: Show sample data
    $sql = "SELECT userId, userName, email, private_key FROM user LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    echo "\n📊 Sample user data:\n";
    foreach ($results as $row) {
        echo "ID: {$row['userId']}, Name: {$row['userName']}, Email: {$row['email']}, Private Key: {$row['private_key']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 