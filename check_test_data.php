<?php
require_once 'config.php';

setCorsHeaders();

try {
    $conn = getDatabaseConnection();
    
    echo "<h1>Test Data Check</h1>";
    
    // Check user with private key def456ghi
    echo "<h2>User with private key 'def456ghi':</h2>";
    $stmt = $conn->prepare("SELECT * FROM user WHERE private_key = ?");
    $stmt->execute(['def456ghi']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>" . json_encode($user, JSON_PRETTY_PRINT) . "</pre>";
    
    // Check user with phone 1111111112
    echo "<h2>User with phone '1111111112':</h2>";
    $stmt = $conn->prepare("SELECT * FROM user WHERE phone = ?");
    $stmt->execute(['1111111112']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>" . json_encode($user, JSON_PRETTY_PRINT) . "</pre>";
    
    // Check all elderly users
    echo "<h2>All elderly users:</h2>";
    $stmt = $conn->prepare("SELECT userId, userName, phone, private_key FROM user WHERE role = 'elderly'");
    $stmt->execute();
    $elderlyUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . json_encode($elderlyUsers, JSON_PRETTY_PRINT) . "</pre>";
    
    // Check all relative users
    echo "<h2>All relative users:</h2>";
    $stmt = $conn->prepare("SELECT userId, userName, phone, private_key FROM user WHERE role = 'relative'");
    $stmt->execute();
    $relativeUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . json_encode($relativeUsers, JSON_PRETTY_PRINT) . "</pre>";
    
} catch (Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
