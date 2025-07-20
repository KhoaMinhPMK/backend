<?php
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Test script for premium purchase
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

echo "<h2>Premium Purchase Test</h2>";
echo "<p>Method: " . $_SERVER['REQUEST_METHOD'] . "</p>";

try {
    $pdo = getDatabaseConnection();
    echo "<p>✅ Database connected</p>";
    
    // Test if tables exist
    $tables = ['premium_plans', 'user_subscriptions', 'payment_transactions'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ Table $table exists</p>";
        } else {
            echo "<p>❌ Table $table missing</p>";
        }
    }
    
    // Get input
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    echo "<h3>Input Data:</h3>";
    echo "<pre>Raw: " . htmlspecialchars($rawInput) . "</pre>";
    echo "<pre>Decoded: " . print_r($input, true) . "</pre>";
    
    if (!empty($input['planId'])) {
        $planId = (int)$input['planId'];
        $paymentMethod = $input['paymentMethod'] ?? 'momo';
        $userId = 1; // Test user ID
        
        // Check if plan exists
        $stmt = $pdo->prepare("SELECT * FROM premium_plans WHERE id = ? AND isActive = TRUE");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($plan) {
            echo "<h3>✅ Plan Found:</h3>";
            echo "<pre>" . print_r($plan, true) . "</pre>";
            
            // Check if user exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo "<p>✅ User found: " . $user['fullName'] . "</p>";
                
                // Test purchase logic without committing
                $pdo->beginTransaction();
                
                try {
                    $amount = $plan['price'];
                    $endDate = date('Y-m-d H:i:s', strtotime('+' . $plan['duration'] . ' days'));
                    $transactionCode = 'TXN_' . time() . '_' . $userId . '_' . $planId;
                    
                    // Update user premium status
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET 
                            isPremium = TRUE,
                            premiumStartDate = NOW(),
                            premiumEndDate = ?,
                            premiumPlanId = ?
                        WHERE id = ?
                    ");
                    $result = $stmt->execute([$endDate, $planId, $userId]);
                    echo "<p>✅ User update: " . ($result ? 'Success' : 'Failed') . "</p>";
                    
                    // Create subscription
                    $stmt = $pdo->prepare("
                        INSERT INTO user_subscriptions (
                            userId, planId, status, startDate, endDate, 
                            paidAmount, paymentMethod, autoRenewal
                        ) VALUES (?, ?, 'active', NOW(), ?, ?, ?, TRUE)
                    ");
                    $result = $stmt->execute([$userId, $planId, $endDate, $amount, $paymentMethod]);
                    $subscriptionId = $pdo->lastInsertId();
                    echo "<p>✅ Subscription created: ID " . $subscriptionId . "</p>";
                    
                    // Create transaction
                    $description = "Thanh toán {$plan['name']} - Gói {$plan['type']}";
                    $stmt = $pdo->prepare("
                        INSERT INTO payment_transactions (
                            userId, subscriptionId, planId, transactionCode, 
                            amount, currency, status, paymentMethod, type, 
                            description, paidAt
                        ) VALUES (?, ?, ?, ?, ?, 'VND', 'completed', ?, 'subscription', ?, NOW())
                    ");
                    $result = $stmt->execute([
                        $userId, $subscriptionId, $planId, $transactionCode,
                        $amount, $paymentMethod, $description
                    ]);
                    $transactionId = $pdo->lastInsertId();
                    echo "<p>✅ Transaction created: ID " . $transactionId . "</p>";
                    
                    // Test successful - rollback to not affect real data
                    $pdo->rollBack();
                    echo "<h3>🎉 Purchase Test SUCCESSFUL!</h3>";
                    echo "<p>Transaction rolled back to preserve test data.</p>";
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    echo "<p>❌ Purchase failed: " . $e->getMessage() . "</p>";
                }
                
            } else {
                echo "<p>❌ User not found with ID: $userId</p>";
            }
            
        } else {
            echo "<p>❌ Plan not found or inactive</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
