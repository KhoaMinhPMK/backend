<?php
require_once 'config.php';

echo "<h1>User Table Structure Test</h1>";

try {
    $pdo = getDatabaseConnection();
    echo "✅ Database connection successful<br>";
    
    // Check if user table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user'");
    if ($stmt->rowCount() > 0) {
        echo "✅ User table exists<br>";
        
        // Show table structure
        echo "<h2>User Table Structure:</h2>";
        $stmt = $pdo->query("DESCRIBE user");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show sample data
        echo "<h2>Sample User Data:</h2>";
        $stmt = $pdo->query("SELECT * FROM user LIMIT 3");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($users) > 0) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr>";
            foreach (array_keys($users[0]) as $column) {
                echo "<th>$column</th>";
            }
            echo "</tr>";
            
            foreach ($users as $user) {
                echo "<tr>";
                foreach ($user as $value) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "No users found in the table<br>";
        }
        
    } else {
        echo "❌ User table does not exist<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?> 