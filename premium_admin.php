<?php
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

try {
    // Kết nối database
    $pdo = getDatabaseConnection();
    
    // Parse URL path để xác định endpoint
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($path, 'check-expired') !== false) {
        // POST /premium/admin/check-expired
        
        try {
            // Chạy procedure kiểm tra subscription hết hạn
            $stmt = $pdo->prepare("CALL CheckExpiredSubscriptions()");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $expiredCount = $result['expiredCount'] ?? 0;
            $stmt->closeCursor();
            
            $response = [
                'success' => true,
                'expiredCount' => $expiredCount,
                'checkedAt' => date('Y-m-d H:i:s'),
                'message' => "Checked and updated {$expiredCount} expired subscriptions"
            ];
            
            sendSuccessResponse($response, 'Subscription status check completed');
            
        } catch (PDOException $e) {
            error_log("Check Expired Error: " . $e->getMessage());
            throw new Exception('Failed to check expired subscriptions');
        }
        
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($path, 'update-status') !== false) {
        // POST /premium/admin/update-status
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['userId'])) {
            throw new Exception('User ID is required');
        }
        
        $userId = (int)$input['userId'];
        
        try {
            // Cập nhật trạng thái premium cho user cụ thể
            $stmt = $pdo->prepare("CALL UpdateUserPremiumStatus(?)");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            $response = [
                'success' => true,
                'userId' => $userId,
                'isPremium' => (bool)$result['isPremium'],
                'premiumEndDate' => $result['premiumEndDate'],
                'premiumPlanId' => $result['premiumPlanId'],
                'updatedAt' => date('Y-m-d H:i:s')
            ];
            
            sendSuccessResponse($response, 'User premium status updated');
            
        } catch (PDOException $e) {
            error_log("Update Status Error: " . $e->getMessage());
            throw new Exception('Failed to update user premium status');
        }
        
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET' && strpos($path, 'stats') !== false) {
        // GET /premium/admin/stats
        
        try {
            // Thống kê tổng quan
            $stats = [];
            
            // Tổng số user premium
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE isPremium = TRUE");
            $stmt->execute();
            $stats['totalPremiumUsers'] = (int)$stmt->fetchColumn();
            
            // Tổng số subscription active
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_subscriptions WHERE status = 'active'");
            $stmt->execute();
            $stats['activeSubscriptions'] = (int)$stmt->fetchColumn();
            
            // Tổng doanh thu
            $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payment_transactions WHERE status = 'completed'");
            $stmt->execute();
            $stats['totalRevenue'] = (float)($stmt->fetchColumn() ?: 0);
            
            // Subscription sắp hết hạn (trong 7 ngày)
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total 
                FROM user_subscriptions 
                WHERE status = 'active' 
                AND endDate BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
            ");
            $stmt->execute();
            $stats['expiringSoon'] = (int)$stmt->fetchColumn();
            
            // Top plans
            $stmt = $pdo->prepare("
                SELECT 
                    pp.name,
                    pp.price,
                    COUNT(us.id) as subscriptionCount,
                    SUM(pt.amount) as totalRevenue
                FROM premium_plans pp
                LEFT JOIN user_subscriptions us ON pp.id = us.planId
                LEFT JOIN payment_transactions pt ON us.id = pt.subscriptionId AND pt.status = 'completed'
                WHERE pp.isActive = TRUE
                GROUP BY pp.id, pp.name, pp.price
                ORDER BY subscriptionCount DESC
                LIMIT 5
            ");
            $stmt->execute();
            $stats['topPlans'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendSuccessResponse($stats, 'Premium statistics retrieved successfully');
            
        } catch (PDOException $e) {
            error_log("Stats Error: " . $e->getMessage());
            throw new Exception('Failed to retrieve premium statistics');
        }
        
    } else {
        sendErrorResponse('Endpoint not found', 'Invalid admin endpoint', 404);
    }
    
} catch (Exception $e) {
    sendErrorResponse($e->getMessage(), 'Bad request', 400);
}
?>
