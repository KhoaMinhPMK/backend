<?php
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Chỉ cho phép GET và POST method
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST', 'PUT'])) {
    sendErrorResponse('Method not allowed', 'Only GET, POST and PUT methods are allowed', 405);
}

try {
    // Kết nối database
    $pdo = getDatabaseConnection();
    
    // Lấy dữ liệu từ request
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Parse URL path để xác định endpoint
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (strpos($path, 'plans') !== false) {
            // GET /premium/plans
            try {
                $stmt = $pdo->prepare("SELECT * FROM premium_plans WHERE isActive = TRUE ORDER BY sortOrder ASC");
                $stmt->execute();
                $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Convert features from JSON string to array
                foreach ($plans as &$plan) {
                    if (isset($plan['features']) && is_string($plan['features'])) {
                        $plan['features'] = json_decode($plan['features'], true) ?: [];
                    }
                }
                
                sendSuccessResponse($plans, 'Premium plans retrieved successfully');
            } catch (PDOException $e) {
                // Fallback to mock data if table doesn't exist
                $mockPlans = [
                    [
                        'id' => 1,
                        'name' => 'Gói Cơ Bản',
                        'description' => 'Gói premium cơ bản với các tính năng cần thiết',
                        'price' => 99000,
                        'duration' => 30,
                        'type' => 'monthly',
                        'features' => ['Truy cập không giới hạn', 'Hỗ trợ 24/7', 'Không quảng cáo'],
                        'isActive' => true,
                        'sortOrder' => 1,
                        'isRecommended' => false,
                        'discountPercent' => 0
                    ],
                    [
                        'id' => 2,
                        'name' => 'Gói Nâng Cao',
                        'description' => 'Gói premium nâng cao với nhiều tính năng hơn',
                        'price' => 199000,
                        'duration' => 30,
                        'type' => 'monthly',
                        'features' => ['Tất cả tính năng cơ bản', 'Tùy chỉnh giao diện', 'Sao lưu dữ liệu', 'Tích hợp AI'],
                        'isActive' => true,
                        'sortOrder' => 2,
                        'isRecommended' => true,
                        'discountPercent' => 10
                    ]
                ];
                sendSuccessResponse($mockPlans, 'Premium plans retrieved successfully (mock data)');
            }
            
        } else if (strpos($path, 'my-status') !== false) {
            // GET /premium/my-status
            // Tạm thời return mock data
            $mockStatus = [
                'isPremium' => false,
                'subscription' => null,
                'plan' => null,
                'daysRemaining' => 0
            ];
            
            sendSuccessResponse($mockStatus, 'Premium status retrieved successfully');
            
        } else if (strpos($path, 'payment-methods') !== false) {
            // GET /premium/payment-methods
            $paymentMethods = [
                [
                    'id' => 'credit_card',
                    'type' => 'credit_card',
                    'name' => 'Thẻ Tín dụng / Ghi nợ',
                    'description' => 'Visa, Mastercard',
                    'icon' => '💳',
                    'enabled' => true,
                    'isAvailable' => true,
                    'processingFee' => 0
                ],
                [
                    'id' => 'momo',
                    'type' => 'e_wallet',
                    'name' => 'Ví MoMo',
                    'description' => 'Thanh toán qua MoMo',
                    'icon' => '🐷',
                    'enabled' => true,
                    'isAvailable' => true,
                    'processingFee' => 0
                ],
                [
                    'id' => 'zalopay',
                    'type' => 'e_wallet',
                    'name' => 'ZaloPay',
                    'description' => 'Thanh toán qua ZaloPay',
                    'icon' => '🔵',
                    'enabled' => true,
                    'isAvailable' => true,
                    'processingFee' => 0
                ],
                [
                    'id' => 'vnpay',
                    'type' => 'e_wallet',
                    'name' => 'VNPay',
                    'description' => 'Thanh toán qua VNPay',
                    'icon' => '🏦',
                    'enabled' => true,
                    'isAvailable' => true,
                    'processingFee' => 0
                ]
            ];
            
            sendSuccessResponse($paymentMethods, 'Payment methods retrieved successfully');
            
        } else if (strpos($path, 'my-transactions') !== false) {
            // GET /premium/payment/my-transactions
            // Tạm thời return empty array
            sendSuccessResponse([], 'Transactions retrieved successfully');
            
        } else {
            sendErrorResponse('Endpoint not found', 'Invalid premium endpoint', 404);
        }
        
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (strpos($path, 'purchase') !== false) {
            // POST /premium/purchase
            if (!$input) {
                throw new Exception('Invalid JSON input');
            }
            
            // Validate required fields
            if (empty($input['planId']) || empty($input['paymentMethod'])) {
                throw new Exception('Plan ID and payment method are required');
            }
            
            $planId = $input['planId'];
            $paymentMethod = $input['paymentMethod'];
            
            // Lấy thông tin plan
            $plan = null;
            try {
                $stmt = $pdo->prepare("SELECT * FROM premium_plans WHERE id = ? AND isActive = TRUE");
                $stmt->execute([$planId]);
                $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // Fallback to mock plan
                $plan = [
                    'id' => $planId,
                    'name' => 'Gói Premium',
                    'price' => 99000,
                    'duration' => 30
                ];
            }
            
            $amount = $plan ? $plan['price'] : 99000;
            $planName = $plan ? $plan['name'] : 'Gói Premium';
            
            // Tạm thời return mock success với format đúng
            $mockResponse = [
                'success' => true,
                'transaction' => [
                    'id' => 1,
                    'transactionCode' => 'TXN_' . time(),
                    'amount' => $amount,
                    'currency' => 'VND',
                    'status' => 'completed',
                    'paymentMethod' => $paymentMethod,
                    'type' => 'subscription',
                    'description' => "Thanh toán {$planName}",
                    'paidAt' => date('Y-m-d H:i:s'),
                    'createdAt' => date('Y-m-d H:i:s'),
                    'updatedAt' => date('Y-m-d H:i:s')
                ],
                'subscription' => [
                    'id' => 1,
                    'userId' => 1,
                    'planId' => $planId,
                    'status' => 'active',
                    'startDate' => date('Y-m-d H:i:s'),
                    'endDate' => date('Y-m-d H:i:s', strtotime('+1 month')),
                    'autoRenewal' => true,
                    'paidAmount' => $amount,
                    'paymentMethod' => $paymentMethod,
                    'createdAt' => date('Y-m-d H:i:s'),
                    'updatedAt' => date('Y-m-d H:i:s')
                ],
                'message' => 'Purchase completed successfully'
            ];
            
            sendSuccessResponse($mockResponse, 'Purchase completed successfully');
            
        } else {
            sendErrorResponse('Endpoint not found', 'Invalid premium endpoint', 404);
        }
        
    } else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        if (strpos($path, 'cancel') !== false) {
            // PUT /premium/subscription/{id}/cancel
            if (!$input) {
                throw new Exception('Invalid JSON input');
            }
            
            // Tạm thời return mock success
            $mockResponse = [
                'id' => 1,
                'status' => 'cancelled',
                'cancelReason' => $input['cancelReason'] ?? 'User cancelled'
            ];
            
            sendSuccessResponse($mockResponse, 'Subscription cancelled successfully');
            
        } else {
            sendErrorResponse('Endpoint not found', 'Invalid premium endpoint', 404);
        }
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