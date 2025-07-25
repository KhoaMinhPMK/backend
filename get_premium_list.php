<?php
// Bao gồm file config
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Chỉ cho phép GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendErrorResponse('Method not allowed', 'Method not allowed', 405);
    exit;
}

try {
    // Lấy kết nối database
    $conn = getDatabaseConnection();
    
    // Query để lấy tất cả premium plans đang active
    $sql = "SELECT 
                id,
                name,
                display_name,
                price,
                currency,
                duration_type,
                duration_value,
                description,
                is_recommended,
                discount_percentage,
                savings_text,
                features,
                created_at,
                updated_at
            FROM premium_plan 
            WHERE is_active = 1 
            ORDER BY is_recommended DESC, price ASC";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        sendErrorResponse('Database prepare error', 'Internal server error', 500);
        exit;
    }
    
    // Execute query
    $result = $stmt->execute();
    
    if ($result) {
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse JSON features cho mỗi plan
        foreach ($plans as &$plan) {
            if ($plan['features']) {
                $plan['features'] = json_decode($plan['features'], true);
            } else {
                $plan['features'] = [];
            }
            
            // Convert boolean values
            $plan['is_recommended'] = (bool)$plan['is_recommended'];
            
            // Convert price to integer
            $plan['price'] = (int)$plan['price'];
            $plan['discount_percentage'] = (int)$plan['discount_percentage'];
        }
        
        // Debug: log response data
        error_log('Premium plans response: ' . json_encode($plans));
        
        // Trả về danh sách plans
        sendSuccessResponse($plans, 'Premium plans retrieved successfully');
        
    } else {
        sendErrorResponse('Failed to retrieve premium plans', 'Database error', 500);
    }
    
} catch (Exception $e) {
    error_log('Get premium plans error: ' . $e->getMessage());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?>
