<?php
// Test script for unique code functionality
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
    } else {
        echo "❌ private_key column does not exist\n";
    }
    
    // Test 2: Check if unique constraint exists
    $sql = "SHOW INDEX FROM user WHERE Key_name = 'uk_private_key'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✅ unique constraint exists\n";
    } else {
        echo "❌ unique constraint does not exist\n";
    }
    
    // Test 3: Show sample data
    $sql = "SELECT id, userName, email, private_key FROM user LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    echo "\n📊 Sample user data:\n";
    foreach ($results as $row) {
        echo "ID: {$row['id']}, Name: {$row['userName']}, Email: {$row['email']}, Private Key: {$row['private_key']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 