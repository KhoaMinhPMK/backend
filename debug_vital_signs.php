<?php
// Simple debug script for vital_signs table
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Vital Signs Table Debug</h1>";

try {
    // Database connection
    $host = 'localhost';
    $dbname = 'viegrand';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>✅ Database connection successful</h2>";
    
    // Check if vital_signs table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'vital_signs'");
    $tableExists = $stmt->rowCount() > 0;
    
    echo "<h2>Table Check:</h2>";
    echo "vital_signs table exists: " . ($tableExists ? "✅ YES" : "❌ NO") . "<br>";
    
    if ($tableExists) {
        // Show table structure
        echo "<h3>Table Structure:</h3>";
        $stmt = $pdo->query("DESCRIBE vital_signs");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "<td>" . $column['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show sample data - latest records
        echo "<h3>Latest Vital Signs Records:</h3>";
        $stmt = $pdo->query("SELECT * FROM vital_signs ORDER BY id DESC LIMIT 5");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($data) > 0) {
            echo "<table border='1'>";
            echo "<tr>";
            foreach (array_keys($data[0]) as $key) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            
            foreach ($data as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "No data found in vital_signs table<br>";
        }
    } else {
        // Create the table if it doesn't exist
        echo "<h3>Creating vital_signs table...</h3>";
        $sql = "CREATE TABLE `vital_signs` (
            `private_key` varchar(255) DEFAULT NULL,
            `blood_pressure_systolic` int(11) DEFAULT NULL COMMENT 'Huyết áp tâm thu (mmHg)',
            `blood_pressure_diastolic` int(11) DEFAULT NULL COMMENT 'Huyết áp tâm trương (mmHg)',
            `heart_rate` int(11) DEFAULT NULL COMMENT 'Nhịp tim (bpm)'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $pdo->exec($sql);
        echo "✅ vital_signs table created successfully<br>";
    }
    
    // Check for users with private keys
    echo "<h3>Users with Private Keys:</h3>";
    $stmt = $pdo->query("SELECT userId, private_key FROM user WHERE private_key IS NOT NULL LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>User ID</th><th>Private Key</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['userId'] . "</td>";
            echo "<td>" . htmlspecialchars($user['private_key']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No users found with private keys<br>";
    }
    
} catch (PDOException $e) {
    echo "<h2>❌ Database Error:</h2>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Code: " . $e->getCode() . "<br>";
} catch (Exception $e) {
    echo "<h2>❌ General Error:</h2>";
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<h2>Test API Endpoints:</h2>";
echo "<a href='test_vital_signs_api.php' target='_blank'>Test API</a><br>";
echo "<a href='test_vital_signs.html' target='_blank'>Test HTML Page</a><br>";
?> 