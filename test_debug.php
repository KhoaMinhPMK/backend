<?php
// File test đơn giản để debug login
require_once 'config.php';

header('Content-Type: application/json');

try {
    echo "Testing database connection...\n";
    $conn = getDatabaseConnection();
    echo "Database connected successfully!\n";
    
    echo "Testing user query...\n";
    $sql = "SELECT * FROM user LIMIT 5";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute();
    
    if ($result) {
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Found " . count($users) . " users:\n";
        foreach ($users as $user) {
            echo "- ID: " . $user['userId'] . ", Email: " . $user['email'] . "\n";
        }
    }
    
    echo "Test completed successfully!";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
