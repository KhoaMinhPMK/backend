<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Testing Mark Notification Read API\n\n";
    
    // Test 1: Check if notifications table exists
    echo "1. Checking notifications table structure:\n";
    $tableStmt = $pdo->prepare("DESCRIBE notifications");
    $tableStmt->execute();
    $columns = $tableStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "   - {$column['Field']}: {$column['Type']}\n";
    }
    echo "\n";
    
    // Test 2: Show sample notifications
    echo "2. Sample notifications in database:\n";
    $notificationsStmt = $pdo->prepare("SELECT id, user_phone, title, is_read, created_at FROM notifications LIMIT 5");
    $notificationsStmt->execute();
    $notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($notifications) > 0) {
        foreach ($notifications as $notification) {
            echo "   - ID: {$notification['id']}, Phone: {$notification['user_phone']}, Title: {$notification['title']}, Read: " . ($notification['is_read'] ? 'Yes' : 'No') . "\n";
        }
    } else {
        echo "   No notifications found in database\n";
    }
    echo "\n";
    
    // Test 3: Test the mark as read functionality
    echo "3. Testing mark as read functionality:\n";
    
    // Get a sample notification to test with
    $testStmt = $pdo->prepare("SELECT id, user_phone FROM notifications WHERE is_read = 0 LIMIT 1");
    $testStmt->execute();
    $testNotification = $testStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testNotification) {
        echo "   Found unread notification: ID {$testNotification['id']}, Phone: {$testNotification['user_phone']}\n";
        
        // Test marking it as read
        $updateStmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE id = ? AND user_phone = ?");
        $result = $updateStmt->execute([$testNotification['id'], $testNotification['user_phone']]);
        
        if ($result) {
            $affectedRows = $updateStmt->rowCount();
            echo "   ✅ Successfully marked notification as read. Affected rows: {$affectedRows}\n";
            
            // Verify the update
            $verifyStmt = $pdo->prepare("SELECT is_read, read_at FROM notifications WHERE id = ?");
            $verifyStmt->execute([$testNotification['id']]);
            $updatedNotification = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($updatedNotification) {
                echo "   ✅ Verification: is_read = " . ($updatedNotification['is_read'] ? 'TRUE' : 'FALSE') . ", read_at = {$updatedNotification['read_at']}\n";
            }
        } else {
            echo "   ❌ Failed to mark notification as read\n";
        }
    } else {
        echo "   No unread notifications found to test with\n";
    }
    
    echo "\n4. API Endpoint Test:\n";
    echo "   To test the actual API, use:\n";
    echo "   curl -X POST \"https://viegrand.site/backend/mark_notification_read.php\" \\\n";
    echo "        -H \"Content-Type: application/json\" \\\n";
    echo "        -d '{\"user_phone\": \"test_phone\", \"notification_ids\": [1, 2, 3]}'\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
} catch (Exception $e) {
    echo "Server error: " . $e->getMessage();
}
?> 