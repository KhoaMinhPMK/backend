<?php
require_once 'config.php';

setCorsHeaders();

try {
    $conn = getDatabaseConnection();
    
    echo "<h1>Test Notifications Table</h1>";
    
    // Check notifications table structure
    echo "<h2>Notifications table structure:</h2>";
    $stmt = $conn->prepare("DESCRIBE notifications");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . json_encode($columns, JSON_PRETTY_PRINT) . "</pre>";
    
    // Test INSERT query
    echo "<h2>Testing INSERT query:</h2>";
    $testSql = "INSERT INTO notifications (user_phone, title, body, type, data) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($testSql);
    
    if (!$stmt) {
        echo "<p>❌ Failed to prepare statement: " . print_r($conn->errorInfo(), true) . "</p>";
    } else {
        echo "<p>✅ Statement prepared successfully</p>";
        
        $testData = json_encode(['test' => 'data']);
        $result = $stmt->execute(['1234567890', 'Test Title', 'Test Body', 'test_type', $testData]);
        
        if ($result) {
            echo "<p>✅ INSERT successful</p>";
            
            // Clean up test data
            $conn->exec("DELETE FROM notifications WHERE user_phone = '1234567890'");
            echo "<p>✅ Test data cleaned up</p>";
        } else {
            echo "<p>❌ INSERT failed: " . print_r($stmt->errorInfo(), true) . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}
?>
