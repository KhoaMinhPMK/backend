<?php
require_once 'config.php';

// Set headers
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Premium Status Debug</h2>";

try {
    $pdo = getDatabaseConnection();
    
    // Check all users premium status
    $stmt = $pdo->query("
        SELECT 
            id, fullName, email, isPremium, premiumStartDate, premiumEndDate, premiumPlanId,
            CASE 
                WHEN isPremium = TRUE AND (premiumEndDate IS NULL OR premiumEndDate > NOW()) THEN 'Valid Premium'
                WHEN isPremium = TRUE AND premiumEndDate <= NOW() THEN 'Expired Premium'
                WHEN isPremium = FALSE THEN 'Not Premium'
                ELSE 'Unknown'
            END as status
        FROM users 
        ORDER BY id
    ");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>isPremium</th><th>Start</th><th>End</th><th>Status</th><th>Action</th></tr>";
    
    foreach ($users as $user) {
        $rowColor = '';
        if ($user['status'] === 'Expired Premium') {
            $rowColor = 'style="background-color: #ffcccc;"';
        } elseif ($user['status'] === 'Valid Premium') {
            $rowColor = 'style="background-color: #ccffcc;"';
        }
        
        echo "<tr $rowColor>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['fullName']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>" . ($user['isPremium'] ? 'TRUE' : 'FALSE') . "</td>";
        echo "<td>{$user['premiumStartDate']}</td>";
        echo "<td>{$user['premiumEndDate']}</td>";
        echo "<td>{$user['status']}</td>";
        
        if ($user['status'] === 'Expired Premium') {
            echo "<td><a href='?fix_user={$user['id']}'>Fix</a></td>";
        } else {
            echo "<td>-</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Fix expired users if requested
    if (isset($_GET['fix_user'])) {
        $userId = (int)$_GET['fix_user'];
        
        $stmt = $pdo->prepare("
            UPDATE users 
            SET isPremium = FALSE, premiumPlanId = NULL 
            WHERE id = ? AND premiumEndDate <= NOW()
        ");
        $result = $stmt->execute([$userId]);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Fixed user ID: $userId</p>";
            echo "<script>setTimeout(() => window.location.reload(), 1000);</script>";
        }
    }
    
    // Fix all button
    echo "<br><a href='?fix_all=1' style='background: red; color: white; padding: 10px; text-decoration: none;'>Fix All Expired Users</a>";
    
    if (isset($_GET['fix_all'])) {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET isPremium = FALSE, premiumPlanId = NULL 
            WHERE isPremium = TRUE AND premiumEndDate <= NOW()
        ");
        $result = $stmt->execute();
        
        echo "<p style='color: green;'>✅ Fixed all expired users. Affected rows: " . $stmt->rowCount() . "</p>";
        echo "<script>setTimeout(() => window.location.reload(), 1000);</script>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
