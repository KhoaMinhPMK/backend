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
            $stmt = $pdo->prepare("SELECT * FROM premium_plans WHERE isActive = TRUE ORDER BY sortOrder ASC");
            $stmt->execute();
            $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendSuccessResponse($plans, 'Premium plans retrieved successfully');
            
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
                ['id' => 'momo', 'name' => 'MoMo', 'icon' => 'momo-icon'],
                ['id' => 'zalopay', 'name' => 'ZaloPay', 'icon' => 'zalopay-icon'],
                ['id' => 'vnpay', 'name' => 'VNPay', 'icon' => 'vnpay-icon'],
                ['id' => 'credit_card', 'name' => 'Thẻ tín dụng', 'icon' => 'credit-card-icon']
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
            
            // Tạm thời return mock success
            $mockResponse = [
                'success' => true,
                'transactionId' => 'TXN_' . time(),
                'message' => 'Purchase initiated successfully'
            ];
            
            sendSuccessResponse($mockResponse, 'Purchase initiated successfully');
            
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