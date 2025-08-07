<?php
require_once 'config.php';

setCorsHeaders();

try {
    $conn = getDatabaseConnection();
    
    echo "<h1>Database Schema Check</h1>";
    
    // Check friend_requests table
    echo "<h2>friend_requests table:</h2>";
    $stmt = $conn->prepare("DESCRIBE friend_requests");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . json_encode($columns, JSON_PRETTY_PRINT) . "</pre>";
    
    // Check friend_status table
    echo "<h2>friend_status table:</h2>";
    $stmt = $conn->prepare("DESCRIBE friend_status");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . json_encode($columns, JSON_PRETTY_PRINT) . "</pre>";
    
    // Check conversations table
    echo "<h2>conversations table:</h2>";
    $stmt = $conn->prepare("DESCRIBE conversations");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . json_encode($columns, JSON_PRETTY_PRINT) . "</pre>";
    
    // Check notifications table
    echo "<h2>notifications table:</h2>";
    $stmt = $conn->prepare("DESCRIBE notifications");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . json_encode($columns, JSON_PRETTY_PRINT) . "</pre>";
    
    // Check notifications table structure in detail
    echo "<h2>notifications table structure (detailed):</h2>";
    $stmt = $conn->prepare("SHOW CREATE TABLE notifications");
    $stmt->execute();
    $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>" . json_encode($createTable, JSON_PRETTY_PRINT) . "</pre>";
    
    // Check user table
    echo "<h2>user table:</h2>";
    $stmt = $conn->prepare("DESCRIBE user");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . json_encode($columns, JSON_PRETTY_PRINT) . "</pre>";
    
} catch (Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
