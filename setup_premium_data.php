<?php
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Chỉ cho phép POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Method not allowed', 'Only POST method is allowed', 405);
}

try {
    // Kết nối database
    $pdo = getDatabaseConnection();
    
    // Lấy dữ liệu từ request
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Sanitize input
    $input = sanitizeInput($input ?? []);
    
    $userId = $input['userId'] ?? null;
    $planId = $input['planId'] ?? 2; // Default to Premium plan
    $paymentMethod = $input['paymentMethod'] ?? 'momo';
    
    // Bắt đầu transaction
    $pdo->beginTransaction();
    
    try {
        if ($userId) {
            // Cập nhật premium cho user cụ thể
            $result = setupPremiumForUser($pdo, $userId, $planId, $paymentMethod);
        } else {
            // Cập nhật premium cho tất cả users chưa có premium
            $result = setupPremiumForAllUsers($pdo, $planId, $paymentMethod);
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Success response
        sendSuccessResponse($result, 'Premium data setup completed successfully', 200);
        
    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    // Database error
    error_log("Database Error: " . $e->getMessage());
    sendErrorResponse('Database error occurred', 'Internal server error', 500);
    
} catch (Exception $e) {
    // Validation or other error
    sendErrorResponse($e->getMessage(), 'Bad request', 400);
}

function setupPremiumForUser($pdo, $userId, $planId, $paymentMethod) {
    // Kiểm tra user có tồn tại không
    $stmt = $pdo->prepare("SELECT id, fullName FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Kiểm tra user đã có premium chưa
    $stmt = $pdo->prepare("SELECT id FROM user_subscriptions WHERE userId = ? AND status = 'active'");
    $stmt->execute([$userId]);
    if ($stmt->fetch()) {
        throw new Exception('User already has active premium subscription');
    }
    
    // Lấy thông tin plan
    $stmt = $pdo->prepare("SELECT * FROM premium_plans WHERE id = ? AND isActive = 1");
    $stmt->execute([$planId]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        throw new Exception('Premium plan not found');
    }
    
    // Tạo subscription
    $startDate = date('Y-m-d H:i:s');
    $endDate = date('Y-m-d H:i:s', strtotime("+{$plan['duration']} days"));
    
    $stmt = $pdo->prepare("
        INSERT INTO user_subscriptions (userId, planId, status, startDate, endDate, autoRenewal, paidAmount, paymentMethod, created_at, updated_at) 
        VALUES (?, ?, 'active', ?, ?, TRUE, ?, ?, NOW(), NOW())
    ");
    
    $stmt->execute([$userId, $planId, $startDate, $endDate, $plan['price'], $paymentMethod]);
    $subscriptionId = $pdo->lastInsertId();
    
    // Tạo payment transaction
    $transactionCode = 'TXN_' . time() . '_' . rand(1000, 9999);
    
    $stmt = $pdo->prepare("
        INSERT INTO payment_transactions (userId, subscriptionId, planId, transactionCode, amount, currency, status, paymentMethod, type, description, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, 'VND', 'completed', ?, 'subscription', ?, NOW(), NOW())
    ");
    
    $stmt->execute([
        $userId, 
        $subscriptionId, 
        $planId, 
        $transactionCode, 
        $plan['price'], 
        $paymentMethod,
        "Thiết lập gói {$plan['name']} cho {$user['fullName']}"
    ]);
    
    return [
        'userId' => $userId,
        'userName' => $user['fullName'],
        'subscriptionId' => $subscriptionId,
        'planName' => $plan['name'],
        'endDate' => $endDate,
        'message' => "Premium setup completed for user {$user['fullName']}"
    ];
}

function setupPremiumForAllUsers($pdo, $planId, $paymentMethod) {
    // Lấy thông tin plan
    $stmt = $pdo->prepare("SELECT * FROM premium_plans WHERE id = ? AND isActive = 1");
    $stmt->execute([$planId]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        throw new Exception('Premium plan not found');
    }
    
    // Lấy danh sách users chưa có premium
    $stmt = $pdo->prepare("
        SELECT u.id, u.fullName 
        FROM users u 
        LEFT JOIN user_subscriptions us ON u.id = us.userId AND us.status = 'active'
        WHERE u.role IN ('elderly', 'relative') AND us.id IS NULL
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $results = [];
    $startDate = date('Y-m-d H:i:s');
    $endDate = date('Y-m-d H:i:s', strtotime("+{$plan['duration']} days"));
    
    foreach ($users as $user) {
        // Tạo subscription
        $stmt = $pdo->prepare("
            INSERT INTO user_subscriptions (userId, planId, status, startDate, endDate, autoRenewal, paidAmount, paymentMethod, created_at, updated_at) 
            VALUES (?, ?, 'active', ?, ?, TRUE, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([$user['id'], $planId, $startDate, $endDate, $plan['price'], $paymentMethod]);
        $subscriptionId = $pdo->lastInsertId();
        
        // Tạo payment transaction
        $transactionCode = 'TXN_' . time() . '_' . rand(1000, 9999);
        
        $stmt = $pdo->prepare("
            INSERT INTO payment_transactions (userId, subscriptionId, planId, transactionCode, amount, currency, status, paymentMethod, type, description, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, 'VND', 'completed', ?, 'subscription', ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $user['id'], 
            $subscriptionId, 
            $planId, 
            $transactionCode, 
            $plan['price'], 
            $paymentMethod,
            "Thiết lập gói {$plan['name']} cho {$user['fullName']}"
        ]);
        
        $results[] = [
            'userId' => $user['id'],
            'userName' => $user['fullName'],
            'subscriptionId' => $subscriptionId,
            'planName' => $plan['name'],
            'endDate' => $endDate
        ];
    }
    
    return [
        'totalUsers' => count($users),
        'planName' => $plan['name'],
        'users' => $results,
        'message' => "Premium setup completed for " . count($users) . " users"
    ];
}
?> 