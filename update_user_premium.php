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
    
    // Validate input
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Sanitize input
    $input = sanitizeInput($input);
    
    // Validate required fields
    $required_fields = ['userId', 'planId', 'paymentMethod'];
    validateRequiredFields($input, $required_fields);
    
    $userId = $input['userId'];
    $planId = $input['planId'];
    $paymentMethod = $input['paymentMethod'];
    $autoRenewal = $input['autoRenewal'] ?? true;
    
    // Validate user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    if (!$stmt->fetch()) {
        throw new Exception('User not found');
    }
    
    // Validate plan exists
    $stmt = $pdo->prepare("SELECT * FROM premium_plans WHERE id = ? AND isActive = 1");
    $stmt->execute([$planId]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$plan) {
        throw new Exception('Premium plan not found');
    }
    
    // Bắt đầu transaction
    $pdo->beginTransaction();
    
    try {
        // Tạo subscription mới
        $startDate = date('Y-m-d H:i:s');
        $endDate = date('Y-m-d H:i:s', strtotime("+{$plan['duration']} days"));
        
        $stmt = $pdo->prepare("
            INSERT INTO user_subscriptions (userId, planId, status, startDate, endDate, autoRenewal, paidAmount, paymentMethod, created_at, updated_at) 
            VALUES (?, ?, 'active', ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([$userId, $planId, $startDate, $endDate, $autoRenewal, $plan['price'], $paymentMethod]);
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
            "Thanh toán gói {$plan['name']}"
        ]);
        
        $transactionId = $pdo->lastInsertId();
        
        // Commit transaction
        $pdo->commit();
        
        // Lấy thông tin subscription vừa tạo
        $stmt = $pdo->prepare("
            SELECT us.*, pp.name as planName, pp.description as planDescription, pp.type as planType
            FROM user_subscriptions us
            JOIN premium_plans pp ON us.planId = pp.id
            WHERE us.id = ?
        ");
        $stmt->execute([$subscriptionId]);
        $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Lấy thông tin transaction
        $stmt = $pdo->prepare("SELECT * FROM payment_transactions WHERE id = ?");
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Success response
        sendSuccessResponse([
            'subscription' => $subscription,
            'transaction' => $transaction,
            'message' => 'Premium subscription created successfully'
        ], 'Premium subscription updated successfully', 201);
        
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
?> 