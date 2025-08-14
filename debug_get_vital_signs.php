<?php
// Debug script for get_vital_signs API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug get_vital_signs API</h1>";

try {
    // Database connection
    $host = 'localhost';
    $dbname = 'viegrand';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>✅ Database connection successful</h2>";
    
    // Test 1: Check vital_signs table structure
    echo "<h3>1. Vital Signs Table Structure:</h3>";
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
    
    // Test 2: Check user table structure
    echo "<h3>2. User Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE user");
    $userColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($userColumns as $column) {
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
    
    // Test 3: Check for users with private keys
    echo "<h3>3. Users with Private Keys:</h3>";
    $stmt = $pdo->query("SELECT userId, private_key_nguoi_nhan FROM user WHERE private_key_nguoi_nhan IS NOT NULL LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>User ID</th><th>Private Key</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['userId'] . "</td>";
            echo "<td>" . htmlspecialchars($user['private_key_nguoi_nhan']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test 4: Test the actual query with a real private key
        $testPrivateKey = $users[0]['private_key_nguoi_nhan'];
        echo "<h3>4. Testing Query with Private Key: " . htmlspecialchars($testPrivateKey) . "</h3>";
        
        // Calculate date range
        $now = new DateTime();
        $start_date = $now->modify("-7 days")->format('Y-m-d H:i:s');
        
        echo "<p>Start date: $start_date</p>";
        
        // Test the query
        $stmt = $pdo->prepare("
            SELECT id, private_key, blood_pressure_systolic, blood_pressure_diastolic, heart_rate, created_at
            FROM vital_signs 
            WHERE private_key = ? AND created_at >= ?
            ORDER BY created_at ASC
        ");
        
        $stmt->execute([$testPrivateKey, $start_date]);
        $vital_signs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Records found: " . count($vital_signs) . "</p>";
        
        if (count($vital_signs) > 0) {
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Private Key</th><th>Systolic</th><th>Diastolic</th><th>Heart Rate</th><th>Created At</th></tr>";
            foreach ($vital_signs as $record) {
                echo "<tr>";
                echo "<td>" . $record['id'] . "</td>";
                echo "<td>" . htmlspecialchars($record['private_key']) . "</td>";
                echo "<td>" . $record['blood_pressure_systolic'] . "</td>";
                echo "<td>" . $record['blood_pressure_diastolic'] . "</td>";
                echo "<td>" . $record['heart_rate'] . "</td>";
                echo "<td>" . $record['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No vital signs records found for this user in the last 7 days.</p>";
        }
        
    } else {
        echo "<p>No users found with private keys.</p>";
    }
    
    // Test 5: Check all vital signs records
    echo "<h3>5. All Vital Signs Records:</h3>";
    $stmt = $pdo->query("SELECT * FROM vital_signs ORDER BY created_at DESC LIMIT 10");
    $allRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($allRecords) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Private Key</th><th>Systolic</th><th>Diastolic</th><th>Heart Rate</th><th>Created At</th></tr>";
        foreach ($allRecords as $record) {
            echo "<tr>";
            echo "<td>" . $record['id'] . "</td>";
            echo "<td>" . htmlspecialchars($record['private_key']) . "</td>";
            echo "<td>" . $record['blood_pressure_systolic'] . "</td>";
            echo "<td>" . $record['blood_pressure_diastolic'] . "</td>";
            echo "<td>" . $record['heart_rate'] . "</td>";
            echo "<td>" . $record['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No vital signs records found in the database.</p>";
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
echo "<a href='get_vital_signs.php?private_key=0pu3ulfw&period=7d' target='_blank'>Test get_vital_signs.php</a><br>";
?> 