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
    
    // Bắt đầu transaction
    $pdo->beginTransaction();
    
    try {
        // 1. Xóa dữ liệu cũ (nếu có)
        $pdo->exec("DELETE FROM payment_transactions");
        $pdo->exec("DELETE FROM user_subscriptions");
        $pdo->exec("DELETE FROM premium_plans");
        
        // 2. Thêm premium plans mẫu
        $plans = [
            [
                'name' => 'Gói Cơ Bản',
                'description' => 'Gói premium cơ bản với các tính năng cần thiết',
                'price' => 99000,
                'duration' => 30,
                'type' => 'monthly',
                'features' => json_encode([
                    'Truy cập không giới hạn',
                    'Hỗ trợ 24/7',
                    'Không quảng cáo',
                    'Thông báo cơ bản'
                ]),
                'isActive' => true,
                'sortOrder' => 1,
                'isRecommended' => false,
                'discountPercent' => 0
            ],
            [
                'name' => 'Gói Nâng Cao',
                'description' => 'Gói premium nâng cao với nhiều tính năng hơn',
                'price' => 199000,
                'duration' => 30,
                'type' => 'monthly',
                'features' => json_encode([
                    'Tất cả tính năng cơ bản',
                    'Tùy chỉnh giao diện',
                    'Sao lưu dữ liệu',
                    'Tích hợp AI',
                    'Báo cáo chi tiết',
                    'Liên hệ khẩn cấp'
                ]),
                'isActive' => true,
                'sortOrder' => 2,
                'isRecommended' => true,
                'discountPercent' => 10
            ],
            [
                'name' => 'Gói Gia Đình',
                'description' => 'Gói premium cho cả gia đình',
                'price' => 299000,
                'duration' => 30,
                'type' => 'monthly',
                'features' => json_encode([
                    'Tất cả tính năng nâng cao',
                    'Quản lý nhiều tài khoản',
                    'Báo cáo chi tiết',
                    'Tích hợp IoT',
                    'Hỗ trợ ưu tiên',
                    'Tính năng độc quyền'
                ]),
                'isActive' => true,
                'sortOrder' => 3,
                'isRecommended' => false,
                'discountPercent' => 15
            ],
            [
                'name' => 'Gói Năm',
                'description' => 'Gói premium trả trước 1 năm',
                'price' => 990000,
                'duration' => 365,
                'type' => 'yearly',
                'features' => json_encode([
                    'Tất cả tính năng gia đình',
                    'Ưu đãi đặc biệt',
                    'Hỗ trợ ưu tiên',
                    'Tiết kiệm 20%',
                    'Cập nhật miễn phí'
                ]),
                'isActive' => true,
                'sortOrder' => 4,
                'isRecommended' => false,
                'discountPercent' => 20
            ],
            [
                'name' => 'Gói Trọn Đời',
                'description' => 'Gói premium trọn đời',
                'price' => 2990000,
                'duration' => 0,
                'type' => 'lifetime',
                'features' => json_encode([
                    'Tất cả tính năng',
                    'Cập nhật miễn phí trọn đời',
                    'Hỗ trợ VIP',
                    'Tính năng độc quyền cao cấp',
                    'Không giới hạn thời gian'
                ]),
                'isActive' => true,
                'sortOrder' => 5,
                'isRecommended' => false,
                'discountPercent' => 0
            ]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO premium_plans (name, description, price, duration, type, features, isActive, sortOrder, isRecommended, discountPercent, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        foreach ($plans as $plan) {
            $stmt->execute([
                $plan['name'],
                $plan['description'],
                $plan['price'],
                $plan['duration'],
                $plan['type'],
                $plan['features'],
                $plan['isActive'],
                $plan['sortOrder'],
                $plan['isRecommended'],
                $plan['discountPercent']
            ]);
        }
        
        // 3. Thêm subscription mẫu cho user đầu tiên (nếu có)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'elderly' LIMIT 1");
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $userId = $user['id'];
            
            // Tạo subscription mẫu
            $startDate = date('Y-m-d H:i:s');
            $endDate = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            $stmt = $pdo->prepare("
                INSERT INTO user_subscriptions (userId, planId, status, startDate, endDate, autoRenewal, paidAmount, paymentMethod, created_at, updated_at) 
                VALUES (?, 2, 'active', ?, ?, TRUE, 199000, 'momo', NOW(), NOW())
            ");
            $stmt->execute([$userId, $startDate, $endDate]);
            
            $subscriptionId = $pdo->lastInsertId();
            
            // Tạo payment transaction mẫu
            $transactionCode = 'TXN_' . time() . '_' . rand(1000, 9999);
            
            $stmt = $pdo->prepare("
                INSERT INTO payment_transactions (userId, subscriptionId, planId, transactionCode, amount, currency, status, paymentMethod, type, description, created_at, updated_at) 
                VALUES (?, ?, 2, ?, 199000, 'VND', 'completed', 'momo', 'subscription', 'Thanh toán gói Nâng Cao', NOW(), NOW())
            ");
            $stmt->execute([$userId, $subscriptionId, $transactionCode]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Lấy danh sách plans vừa tạo
        $stmt = $pdo->prepare("SELECT * FROM premium_plans ORDER BY sortOrder ASC");
        $stmt->execute();
        $createdPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse features JSON
        foreach ($createdPlans as &$plan) {
            $plan['features'] = json_decode($plan['features'], true);
        }
        
        // Success response
        sendSuccessResponse([
            'plans' => $createdPlans,
            'message' => 'Premium data initialized successfully',
            'totalPlans' => count($createdPlans)
        ], 'Premium data initialized successfully', 201);
        
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