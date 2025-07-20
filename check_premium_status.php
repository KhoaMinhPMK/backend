<?php
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Chỉ cho phép GET method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendErrorResponse('Method not allowed', 'Only GET method is allowed', 405);
}

try {
    // Kết nối database
    $pdo = getDatabaseConnection();
    
    // Kiểm tra và cập nhật trạng thái subscription hết hạn
    $stmt = $pdo->prepare("
        UPDATE user_subscriptions 
        SET status = 'expired', updated_at = NOW() 
        WHERE status = 'active' AND endDate < NOW()
    ");
    $stmt->execute();
    $expiredCount = $stmt->rowCount();
    
    // Lấy thống kê premium
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as totalUsers,
            COUNT(CASE WHEN us.status = 'active' AND us.endDate > NOW() THEN 1 END) as activePremiumUsers,
            COUNT(CASE WHEN us.status = 'expired' THEN 1 END) as expiredUsers,
            COUNT(CASE WHEN us.status = 'cancelled' THEN 1 END) as cancelledUsers
        FROM users u
        LEFT JOIN user_subscriptions us ON u.id = us.userId
        WHERE u.role IN ('elderly', 'relative')
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Lấy danh sách users với trạng thái premium
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.fullName,
            u.email,
            u.phone,
            u.role,
            u.active,
            us.id as subscriptionId,
            us.status as subscriptionStatus,
            us.startDate,
            us.endDate,
            us.autoRenewal,
            us.paidAmount,
            us.paymentMethod,
            pp.name as planName,
            pp.type as planType,
            DATEDIFF(us.endDate, NOW()) as daysRemaining,
            CASE 
                WHEN us.status = 'active' AND us.endDate > NOW() THEN TRUE
                ELSE FALSE
            END as isPremium
        FROM users u
        LEFT JOIN user_subscriptions us ON u.id = us.userId AND us.status IN ('active', 'expired')
        LEFT JOIN premium_plans pp ON us.planId = pp.id
        WHERE u.role IN ('elderly', 'relative')
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Success response
    sendSuccessResponse([
        'expiredCount' => $expiredCount,
        'stats' => $stats,
        'users' => $users,
        'message' => 'Premium status checked and updated successfully'
    ], 'Premium status checked successfully', 200);
    
} catch (PDOException $e) {
    // Database error
    error_log("Database Error: " . $e->getMessage());
    sendErrorResponse('Database error occurred', 'Internal server error', 500);
    
} catch (Exception $e) {
    // Validation or other error
    sendErrorResponse($e->getMessage(), 'Bad request', 400);
}
?> 