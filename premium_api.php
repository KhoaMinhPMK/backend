<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

require_once 'config.php';

// Xử lý preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Kiểm tra method
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Lấy endpoint từ URL
$endpoint = end($pathParts);

try {
    switch ($endpoint) {
        case 'plans':
            handlePlans($method);
            break;
        case 'my-status':
            handleMyStatus($method);
            break;
        case 'purchase':
            handlePurchase($method);
            break;
        case 'transactions':
            handleTransactions($method);
            break;
        case 'payment-methods':
            handlePaymentMethods($method);
            break;
        case 'cancel-subscription':
            handleCancelSubscription($method);
            break;
        case 'retry-payment':
            handleRetryPayment($method);
            break;
        default:
            sendError('Endpoint không tồn tại', 404);
    }
} catch (Exception $e) {
    sendError('Lỗi server: ' . $e->getMessage(), 500);
}

// =====================================================
// HANDLERS
// =====================================================

function handlePlans($method) {
    if ($method !== 'GET') {
        sendError('Method không được hỗ trợ', 405);
    }
    
    $sql = "SELECT * FROM premium_plans WHERE isActive = 1 ORDER BY sortOrder ASC";
    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute();
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse features JSON
    foreach ($plans as &$plan) {
        $plan['features'] = json_decode($plan['features'], true);
    }
    
    sendSuccess($plans);
}

function handleMyStatus($method) {
    if ($method !== 'GET') {
        sendError('Method không được hỗ trợ', 405);
    }
    
    $userId = getUserIdFromToken();
    if (!$userId) {
        sendError('Token không hợp lệ', 401);
    }
    
    // Lấy thông tin subscription hiện tại
    $sql = "SELECT 
                us.*,
                pp.name as planName,
                pp.description as planDescription,
                pp.price as planPrice,
                pp.type as planType,
                pp.features as planFeatures,
                DATEDIFF(us.endDate, NOW()) as daysRemaining
            FROM user_subscriptions us
            JOIN premium_plans pp ON us.planId = pp.id
            WHERE us.userId = ? AND us.status = 'active'
            ORDER BY us.created_at DESC
            LIMIT 1";
    
    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute([$userId]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $isPremium = false;
    $daysRemaining = 0;
    $plan = null;
    
    if ($subscription) {
        $isPremium = $subscription['endDate'] > date('Y-m-d H:i:s');
        $daysRemaining = max(0, $subscription['daysRemaining']);
        $plan = [
            'id' => $subscription['planId'],
            'name' => $subscription['planName'],
            'description' => $subscription['planDescription'],
            'price' => $subscription['planPrice'],
            'type' => $subscription['planType'],
            'features' => json_decode($subscription['planFeatures'], true)
        ];
    }
    
    $result = [
        'isPremium' => $isPremium,
        'subscription' => $subscription ? [
            'id' => $subscription['id'],
            'status' => $subscription['status'],
            'startDate' => $subscription['startDate'],
            'endDate' => $subscription['endDate'],
            'autoRenewal' => (bool)$subscription['autoRenewal'],
            'paidAmount' => $subscription['paidAmount'],
            'paymentMethod' => $subscription['paymentMethod']
        ] : null,
        'plan' => $plan,
        'daysRemaining' => $daysRemaining
    ];
    
    sendSuccess($result);
}

function handlePurchase($method) {
    if ($method !== 'POST') {
        sendError('Method không được hỗ trợ', 405);
    }
    
    $userId = getUserIdFromToken();
    if (!$userId) {
        sendError('Token không hợp lệ', 401);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['planId']) || !isset($input['paymentMethod'])) {
        sendError('Thiếu thông tin cần thiết', 400);
    }
    
    $planId = $input['planId'];
    $paymentMethod = $input['paymentMethod'];
    
    // Kiểm tra plan có tồn tại không
    $sql = "SELECT * FROM premium_plans WHERE id = ? AND isActive = 1";
    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute([$planId]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        sendError('Gói premium không tồn tại', 404);
    }
    
    // Tạo transaction code
    $transactionCode = 'TXN_' . time() . '_' . rand(1000, 9999);
    
    // Bắt đầu transaction
    $GLOBALS['pdo']->beginTransaction();
    
    try {
        // Tạo subscription mới
        $startDate = date('Y-m-d H:i:s');
        $endDate = date('Y-m-d H:i:s', strtotime("+{$plan['duration']} days"));
        
        $sql = "INSERT INTO user_subscriptions (userId, planId, status, startDate, endDate, paidAmount, paymentMethod) 
                VALUES (?, ?, 'active', ?, ?, ?, ?)";
        $stmt = $GLOBALS['pdo']->prepare($sql);
        $stmt->execute([$userId, $planId, $startDate, $endDate, $plan['price'], $paymentMethod]);
        
        $subscriptionId = $GLOBALS['pdo']->lastInsertId();
        
        // Tạo payment transaction
        $sql = "INSERT INTO payment_transactions (userId, subscriptionId, planId, transactionCode, amount, currency, status, paymentMethod, type, description) 
                VALUES (?, ?, ?, ?, ?, 'VND', 'completed', ?, 'subscription', ?)";
        $stmt = $GLOBALS['pdo']->prepare($sql);
        $stmt->execute([
            $userId, 
            $subscriptionId, 
            $planId, 
            $transactionCode, 
            $plan['price'], 
            $paymentMethod,
            "Thanh toán gói {$plan['name']}"
        ]);
        
        $transactionId = $GLOBALS['pdo']->lastInsertId();
        
        // Commit transaction
        $GLOBALS['pdo']->commit();
        
        // Lấy thông tin transaction vừa tạo
        $sql = "SELECT * FROM payment_transactions WHERE id = ?";
        $stmt = $GLOBALS['pdo']->prepare($sql);
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Lấy thông tin subscription
        $sql = "SELECT * FROM user_subscriptions WHERE id = ?";
        $stmt = $GLOBALS['pdo']->prepare($sql);
        $stmt->execute([$subscriptionId]);
        $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $result = [
            'success' => true,
            'subscription' => $subscription,
            'transaction' => $transaction,
            'message' => 'Thanh toán thành công'
        ];
        
        sendSuccess($result);
        
    } catch (Exception $e) {
        $GLOBALS['pdo']->rollBack();
        sendError('Lỗi khi xử lý thanh toán: ' . $e->getMessage(), 500);
    }
}

function handleTransactions($method) {
    if ($method !== 'GET') {
        sendError('Method không được hỗ trợ', 405);
    }
    
    $userId = getUserIdFromToken();
    if (!$userId) {
        sendError('Token không hợp lệ', 401);
    }
    
    $sql = "SELECT 
                pt.*,
                pp.name as planName,
                pp.type as planType
            FROM payment_transactions pt
            JOIN premium_plans pp ON pt.planId = pp.id
            WHERE pt.userId = ?
            ORDER BY pt.created_at DESC
            LIMIT 50";
    
    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute([$userId]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendSuccess($transactions);
}

function handlePaymentMethods($method) {
    if ($method !== 'GET') {
        sendError('Method không được hỗ trợ', 405);
    }
    
    // Trả về danh sách payment methods cố định
    $paymentMethods = [
        [
            'id' => 'credit_card',
            'type' => 'credit_card',
            'name' => 'Thẻ Tín dụng / Ghi nợ',
            'description' => 'Visa, Mastercard, JCB',
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
    
    sendSuccess($paymentMethods);
}

function handleCancelSubscription($method) {
    if ($method !== 'POST') {
        sendError('Method không được hỗ trợ', 405);
    }
    
    $userId = getUserIdFromToken();
    if (!$userId) {
        sendError('Token không hợp lệ', 401);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $reason = $input['reason'] ?? 'Người dùng hủy';
    
    // Tìm subscription hiện tại
    $sql = "SELECT * FROM user_subscriptions WHERE userId = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1";
    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute([$userId]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$subscription) {
        sendError('Không có gói premium nào để hủy', 404);
    }
    
    // Cập nhật trạng thái subscription
    $sql = "UPDATE user_subscriptions SET status = 'cancelled', cancelledAt = NOW(), cancelReason = ? WHERE id = ?";
    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute([$reason, $subscription['id']]);
    
    sendSuccess(['message' => 'Đã hủy gói premium thành công']);
}

function handleRetryPayment($method) {
    if ($method !== 'POST') {
        sendError('Method không được hỗ trợ', 405);
    }
    
    $userId = getUserIdFromToken();
    if (!$userId) {
        sendError('Token không hợp lệ', 401);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $transactionId = $input['transactionId'] ?? null;
    
    if (!$transactionId) {
        sendError('Thiếu transaction ID', 400);
    }
    
    // Tìm transaction
    $sql = "SELECT * FROM payment_transactions WHERE id = ? AND userId = ?";
    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute([$transactionId, $userId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        sendError('Giao dịch không tồn tại', 404);
    }
    
    // Tạo payment URL mới (mock)
    $paymentUrl = 'https://payment-gateway.example.com/retry/' . $transactionId;
    
    sendSuccess([
        'success' => true,
        'paymentUrl' => $paymentUrl,
        'message' => 'Đã tạo link thanh toán mới'
    ]);
}

// =====================================================
// UTILITY FUNCTIONS
// =====================================================

function getUserIdFromToken() {
    $headers = getallheaders();
    $token = null;
    
    // Tìm token trong Authorization header
    if (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
        if (strpos($auth, 'Bearer ') === 0) {
            $token = substr($auth, 7);
        }
    }
    
    // Hoặc tìm trong query parameter
    if (!$token && isset($_GET['token'])) {
        $token = $_GET['token'];
    }
    
    if (!$token) {
        return null;
    }
    
    // Decode JWT token (simplified)
    try {
        // Trong thực tế, bạn sẽ sử dụng thư viện JWT
        // Đây là mock implementation
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        
        $payload = json_decode(base64_decode($parts[1]), true);
        return $payload['user_id'] ?? null;
    } catch (Exception $e) {
        return null;
    }
}

function sendSuccess($data) {
    echo json_encode([
        'success' => true,
        'data' => $data,
        'message' => 'Thành công'
    ], JSON_UNESCAPED_UNICODE);
}

function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => [
            'message' => $message,
            'code' => $code
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?> 