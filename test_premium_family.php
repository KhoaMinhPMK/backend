<?php
require_once 'config.php';

// Test script for premium family members API
echo "🧪 Testing Premium Family Members API\n";
echo "=====================================\n\n";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get a test user with private key
    $testUserSql = "SELECT private_key, userName, role FROM user WHERE private_key IS NOT NULL LIMIT 1";
    $testUserStmt = $pdo->prepare($testUserSql);
    $testUserStmt->execute();
    $testUser = $testUserStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testUser) {
        echo "❌ No test user found with private key\n";
        exit;
    }
    
    echo "👤 Test User: " . $testUser['userName'] . " (Role: " . $testUser['role'] . ")\n";
    echo "🔑 Private Key: " . $testUser['private_key'] . "\n\n";
    
    // Check if user is in premium subscription
    if ($testUser['role'] === 'elderly') {
        $subscriptionSql = "SELECT 
                                premium_key,
                                young_person_key,
                                elderly_keys,
                                start_date,
                                end_date,
                                DATEDIFF(end_date, NOW()) as days_remaining,
                                IF(end_date > NOW(), 'active', 'expired') as status
                            FROM premium_subscriptions_json 
                            WHERE JSON_CONTAINS(elderly_keys, ?)
                            ORDER BY end_date DESC 
                            LIMIT 1";
        
        $subscriptionStmt = $pdo->prepare($subscriptionSql);
        $subscriptionStmt->execute([json_encode($testUser['private_key'])]);
        $subscription = $subscriptionStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($subscription) {
            echo "✅ Found premium subscription for elderly user\n";
            echo "   Premium Key: " . $subscription['premium_key'] . "\n";
            echo "   Status: " . $subscription['status'] . "\n";
            echo "   Days Remaining: " . $subscription['days_remaining'] . "\n";
            
            // Get relative info
            $relativeSql = "SELECT userName, phone FROM user WHERE private_key = ? AND role = 'relative'";
            $relativeStmt = $pdo->prepare($relativeSql);
            $relativeStmt->execute([$subscription['young_person_key']]);
            $relative = $relativeStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($relative) {
                echo "   Relative: " . $relative['userName'] . " (" . $relative['phone'] . ")\n";
            }
            
            // Get other elderly users
            $elderlyKeys = json_decode($subscription['elderly_keys'], true);
            if (is_array($elderlyKeys)) {
                echo "   Elderly Members (" . count($elderlyKeys) . "):\n";
                foreach ($elderlyKeys as $key) {
                    if ($key !== $testUser['private_key']) {
                        $elderlySql = "SELECT userName, phone FROM user WHERE private_key = ? AND role = 'elderly'";
                        $elderlyStmt = $pdo->prepare($elderlySql);
                        $elderlyStmt->execute([$key]);
                        $elderly = $elderlyStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($elderly) {
                            echo "     - " . $elderly['userName'] . " (" . $elderly['phone'] . ")\n";
                        }
                    }
                }
            }
        } else {
            echo "❌ No premium subscription found for elderly user\n";
        }
        
    } elseif ($testUser['role'] === 'relative') {
        $subscriptionSql = "SELECT 
                                premium_key,
                                young_person_key,
                                elderly_keys,
                                start_date,
                                end_date,
                                DATEDIFF(end_date, NOW()) as days_remaining,
                                IF(end_date > NOW(), 'active', 'expired') as status
                            FROM premium_subscriptions_json 
                            WHERE young_person_key = ?
                            ORDER BY end_date DESC 
                            LIMIT 1";
        
        $subscriptionStmt = $pdo->prepare($subscriptionSql);
        $subscriptionStmt->execute([$testUser['private_key']]);
        $subscription = $subscriptionStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($subscription) {
            echo "✅ Found premium subscription for relative user\n";
            echo "   Premium Key: " . $subscription['premium_key'] . "\n";
            echo "   Status: " . $subscription['status'] . "\n";
            echo "   Days Remaining: " . $subscription['days_remaining'] . "\n";
            
            // Get elderly users
            $elderlyKeys = json_decode($subscription['elderly_keys'], true);
            if (is_array($elderlyKeys)) {
                echo "   Elderly Members (" . count($elderlyKeys) . "):\n";
                foreach ($elderlyKeys as $key) {
                    $elderlySql = "SELECT userName, phone FROM user WHERE private_key = ? AND role = 'elderly'";
                    $elderlyStmt = $pdo->prepare($elderlySql);
                    $elderlyStmt->execute([$key]);
                    $elderly = $elderlyStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($elderly) {
                        echo "     - " . $elderly['userName'] . " (" . $elderly['phone'] . ")\n";
                    }
                }
            }
        } else {
            echo "❌ No premium subscription found for relative user\n";
        }
    }
    
    echo "\n📊 Summary:\n";
    echo "   - Test user has private key: " . ($testUser['private_key'] ? "✅" : "❌") . "\n";
    echo "   - User role: " . $testUser['role'] . "\n";
    echo "   - API should work for this user\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 