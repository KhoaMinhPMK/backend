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
            // Cần userId từ token hoặc session - tạm thời dùng userId=1
            $userId = 1; // TODO: Lấy từ auth token
            
            try {
                // Gọi procedure cập nhật trạng thái premium
                $stmt = $pdo->prepare("CALL UpdateUserPremiumStatus(?)");
                $stmt->execute([$userId]);
                $stmt->closeCursor();
                
                // Lấy thông tin premium status
                $stmt = $pdo->prepare("
                    SELECT 
                        u.id,
                        u.fullName,
                        u.email,
                        u.isPremium,
                        u.premiumStartDate,
                        u.premiumEndDate,
                        u.premiumPlanId,
                        u.premiumTrialUsed,
                        u.premiumTrialEndDate,
                        pp.name as planName,
                        pp.price as planPrice,
                        pp.type as planType,
                        pp.features as planFeatures,
                        us.id as subscriptionId,
                        us.status as subscriptionStatus,
                        us.autoRenewal,
                        us.nextPaymentDate,
                        CASE 
                            WHEN u.isPremium = TRUE AND u.premiumEndDate > NOW() THEN 
                                DATEDIFF(u.premiumEndDate, NOW())
                            ELSE 0
                        END as daysRemaining,
                        CASE 
                            WHEN u.premiumTrialEndDate > NOW() AND u.premiumTrialUsed = TRUE THEN TRUE
                            ELSE FALSE
                        END as isTrialActive
                    FROM users u
                    LEFT JOIN premium_plans pp ON u.premiumPlanId = pp.id
                    LEFT JOIN user_subscriptions us ON u.id = us.userId AND us.status = 'active'
                    WHERE u.id = ?
                ");
                $stmt->execute([$userId]);
                $status = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$status) {
                    throw new Exception('User not found');
                }
                
                // Format features
                if (!empty($status['planFeatures'])) {
                    $status['planFeatures'] = json_decode($status['planFeatures'], true) ?: [];
                }
                
                // Format response
                $response = [
                    'userId' => $status['id'],
                    'userName' => $status['fullName'],
                    'userEmail' => $status['email'],
                    'isPremium' => (bool)$status['isPremium'],
                    'premiumStartDate' => $status['premiumStartDate'],
                    'premiumEndDate' => $status['premiumEndDate'],
                    'daysRemaining' => (int)$status['daysRemaining'],
                    'isTrialActive' => (bool)$status['isTrialActive'],
                    'trialUsed' => (bool)$status['premiumTrialUsed'],
                    'subscription' => null,
                    'plan' => null
                ];
                
                // Add subscription info if exists
                if ($status['subscriptionId']) {
                    $response['subscription'] = [
                        'id' => $status['subscriptionId'],
                        'status' => $status['subscriptionStatus'],
                        'autoRenewal' => (bool)$status['autoRenewal'],
                        'nextPaymentDate' => $status['nextPaymentDate']
                    ];
                }
                
                // Add plan info if exists
                if ($status['premiumPlanId']) {
                    $response['plan'] = [
                        'id' => $status['premiumPlanId'],
                        'name' => $status['planName'],
                        'price' => $status['planPrice'],
                        'type' => $status['planType'],
                        'features' => $status['planFeatures'] ?: []
                    ];
                }
                
                sendSuccessResponse($response, 'Premium status retrieved successfully');
                
            } catch (PDOException $e) {
                // Fallback to mock data if database issues
                $mockStatus = [
                    'userId' => $userId,
                    'isPremium' => false,
                    'subscription' => null,
                    'plan' => null,
                    'daysRemaining' => 0,
                    'isTrialActive' => false,
                    'trialUsed' => false
                ];
                
                sendSuccessResponse($mockStatus, 'Premium status retrieved successfully (fallback)');
            }
            
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
            $userId = 1; // TODO: Lấy từ auth token
            
            try {
                $stmt = $pdo->prepare("
                    SELECT 
                        pt.*,
                        pp.name as planName,
                        us.status as subscriptionStatus
                    FROM payment_transactions pt
                    LEFT JOIN premium_plans pp ON pt.planId = pp.id
                    LEFT JOIN user_subscriptions us ON pt.subscriptionId = us.id
                    WHERE pt.userId = ?
                    ORDER BY pt.created_at DESC
                    LIMIT 50
                ");
                $stmt->execute([$userId]);
                $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Format transactions
                foreach ($transactions as &$transaction) {
                    if (!empty($transaction['customerInfo'])) {
                        $transaction['customerInfo'] = json_decode($transaction['customerInfo'], true);
                    }
                    if (!empty($transaction['gatewayResponse'])) {
                        $transaction['gatewayResponse'] = json_decode($transaction['gatewayResponse'], true);
                    }
                }
                
                sendSuccessResponse($transactions, 'Transactions retrieved successfully');
                
            } catch (PDOException $e) {
                // Fallback to empty array
                sendSuccessResponse([], 'Transactions retrieved successfully (no data)');
            }
            
        } else {
            sendErrorResponse('Endpoint not found', 'Invalid premium endpoint', 404);
        }
        
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (strpos($path, 'purchase') !== false) {
            // POST /premium/purchase
            
            // Debug input
            $rawInput = file_get_contents('php://input');
            error_log("Raw input: " . $rawInput);
            
            if (!$input && !empty($rawInput)) {
                // Try to decode again
                $input = json_decode($rawInput, true);
            }
            
            if (!$input) {
                // Try alternative method to get POST data
                $input = $_POST;
                if (empty($input)) {
                    sendErrorResponse('No input data received', 'Bad Request', 400);
                    return;
                }
            }
            
            // Validate required fields
            if (empty($input['planId']) || empty($input['paymentMethod'])) {
                sendErrorResponse('Plan ID and payment method are required', 'Bad Request', 400);
                return;
            }
            
            $planId = (int)$input['planId'];
            $paymentMethod = $input['paymentMethod'];
            $userId = 1; // TODO: Lấy từ auth token
            
            // Validate payment method
            $validPaymentMethods = ['momo', 'zalopay', 'vnpay', 'credit_card'];
            if (!in_array($paymentMethod, $validPaymentMethods)) {
                sendErrorResponse('Invalid payment method', 'Bad Request', 400);
                return;
            }
            
            try {
                // Lấy thông tin plan
                $stmt = $pdo->prepare("SELECT * FROM premium_plans WHERE id = ? AND isActive = TRUE");
                $stmt->execute([$planId]);
                $plan = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$plan) {
                    throw new Exception('Plan not found or inactive');
                }
                
                $amount = $plan['price'];
                $planName = $plan['name'];
                
                // Generate unique transaction code
                $transactionCode = 'TXN_' . time() . '_' . $userId . '_' . $planId;
                
                // Simplified purchase without stored procedures
                $pdo->beginTransaction();
                
                // Update user premium status directly
                $endDate = date('Y-m-d H:i:s', strtotime('+' . $plan['duration'] . ' days'));
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET 
                        isPremium = TRUE,
                        premiumStartDate = NOW(),
                        premiumEndDate = ?,
                        premiumPlanId = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$endDate, $planId, $userId]);
                
                // Create subscription record
                $stmt = $pdo->prepare("
                    INSERT INTO user_subscriptions (
                        userId, planId, status, startDate, endDate, 
                        paidAmount, paymentMethod, autoRenewal
                    ) VALUES (?, ?, 'active', NOW(), ?, ?, ?, TRUE)
                ");
                $stmt->execute([$userId, $planId, $endDate, $amount, $paymentMethod]);
                $subscriptionId = $pdo->lastInsertId();
                
                // Create payment transaction
                $description = "Thanh toán {$planName} - Gói {$plan['type']}";
                $stmt = $pdo->prepare("
                    INSERT INTO payment_transactions (
                        userId, subscriptionId, planId, transactionCode, 
                        amount, currency, status, paymentMethod, type, 
                        description, paidAt
                    ) VALUES (?, ?, ?, ?, ?, 'VND', 'completed', ?, 'subscription', ?, NOW())
                ");
                $stmt->execute([
                    $userId, $subscriptionId, $planId, $transactionCode,
                    $amount, $paymentMethod, $description
                ]);
                
                $transactionId = $pdo->lastInsertId();
                
                $pdo->commit();
                
                $response = [
                    'success' => true,
                    'transaction' => [
                        'id' => $transactionId,
                        'transactionCode' => $transactionCode,
                        'amount' => (float)$amount,
                        'currency' => 'VND',
                        'status' => 'completed',
                        'paymentMethod' => $paymentMethod,
                        'type' => 'subscription',
                        'description' => $description,
                        'paidAt' => date('Y-m-d H:i:s')
                    ],
                    'subscription' => [
                        'id' => $subscriptionId,
                        'userId' => $userId,
                        'planId' => $planId,
                        'status' => 'active',
                        'startDate' => date('Y-m-d H:i:s'),
                        'endDate' => $endDate,
                        'autoRenewal' => true,
                        'paidAmount' => (float)$amount,
                        'paymentMethod' => $paymentMethod
                    ],
                    'plan' => [
                        'id' => $plan['id'],
                        'name' => $plan['name'],
                        'price' => (float)$plan['price'],
                        'duration' => (int)$plan['duration'],
                        'type' => $plan['type']
                    ],
                    'message' => 'Purchase completed successfully'
                ];
                
                sendSuccessResponse($response, 'Premium purchase completed successfully');
                
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Purchase PDO Error: " . $e->getMessage());
                sendErrorResponse('Database error occurred during purchase', 'Internal Server Error', 500);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Purchase Error: " . $e->getMessage());
                sendErrorResponse($e->getMessage(), 'Bad Request', 400);
            }
            
        } else if (strpos($path, 'activate-trial') !== false) {
            // POST /premium/activate-trial
            $userId = 1; // TODO: Lấy từ auth token
            $trialDays = 7; // Default 7 days trial
            
            try {
                $stmt = $pdo->prepare("CALL ActivatePremiumTrial(?, ?)");
                $stmt->execute([$userId, $trialDays]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
                
                $response = [
                    'success' => true,
                    'isPremium' => (bool)$result['isPremium'],
                    'premiumEndDate' => $result['premiumEndDate'],
                    'trialEndDate' => $result['premiumTrialEndDate'],
                    'trialDays' => $trialDays,
                    'message' => "Premium trial activated for {$trialDays} days"
                ];
                
                sendSuccessResponse($response, 'Premium trial activated successfully');
                
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Premium trial already used') !== false) {
                    throw new Exception('Premium trial has already been used for this account');
                }
                throw new Exception('Failed to activate premium trial');
            }
            
        } else {
            sendErrorResponse('Endpoint not found', 'Invalid premium endpoint', 404);
        }
        
    } else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        if (strpos($path, 'cancel') !== false) {
            // PUT /premium/subscription/cancel
            if (!$input) {
                throw new Exception('Invalid JSON input');
            }
            
            $userId = 1; // TODO: Lấy từ auth token
            $cancelReason = $input['cancelReason'] ?? 'User cancelled';
            
            try {
                // Tìm subscription active
                $stmt = $pdo->prepare("
                    SELECT id FROM user_subscriptions 
                    WHERE userId = ? AND status = 'active' 
                    ORDER BY created_at DESC LIMIT 1
                ");
                $stmt->execute([$userId]);
                $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$subscription) {
                    throw new Exception('No active subscription found');
                }
                
                // Hủy subscription
                $stmt = $pdo->prepare("
                    UPDATE user_subscriptions 
                    SET status = 'cancelled', cancelledAt = NOW(), cancelReason = ?, autoRenewal = FALSE 
                    WHERE id = ?
                ");
                $stmt->execute([$cancelReason, $subscription['id']]);
                
                // Cập nhật trạng thái premium user
                $stmt = $pdo->prepare("CALL UpdateUserPremiumStatus(?)");
                $stmt->execute([$userId]);
                $stmt->closeCursor();
                
                $response = [
                    'id' => $subscription['id'],
                    'status' => 'cancelled',
                    'cancelReason' => $cancelReason,
                    'cancelledAt' => date('Y-m-d H:i:s')
                ];
                
                sendSuccessResponse($response, 'Subscription cancelled successfully');
                
            } catch (PDOException $e) {
                throw new Exception('Failed to cancel subscription');
            }
            
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
