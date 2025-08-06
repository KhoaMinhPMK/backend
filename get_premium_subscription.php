<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Method not allowed', 405);
    exit;
}

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['email'])) {
        sendErrorResponse('Email is required');
        exit;
    }
    
    $email = trim($input['email']);
    
    // Create database connection
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // First, get the user's private_key
    $userSql = "SELECT private_key, userName, email FROM user WHERE email = ?";
    $userStmt = $conn->prepare($userSql);
    $userStmt->execute([$email]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        sendErrorResponse('User not found');
        exit;
    }
    
    if (!$user['private_key']) {
        sendErrorResponse('User does not have a private key');
        exit;
    }
    
    // Use the whole private_key as young_person_key
    $youngPersonKey = $user['private_key'];
    
    // Get the current active premium subscription
    $subscriptionSql = "SELECT 
                            premium_key,
                            young_person_key,
                            elderly_keys,
                            start_date,
                            end_date,
                            note,
                            DATEDIFF(end_date, NOW()) as days_remaining,
                            IF(end_date > NOW(), 'active', 'expired') as status
                        FROM premium_subscriptions_json 
                        WHERE young_person_key = ? 
                        AND end_date > NOW()
                        ORDER BY end_date DESC 
                        LIMIT 1";
    
    $subscriptionStmt = $conn->prepare($subscriptionSql);
    $subscriptionStmt->execute([$youngPersonKey]);
    $subscription = $subscriptionStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$subscription) {
        // Check if user has any expired subscriptions
        $expiredSql = "SELECT 
                            premium_key,
                            young_person_key,
                            elderly_keys,
                            start_date,
                            end_date,
                            note,
                            DATEDIFF(end_date, NOW()) as days_remaining,
                            'expired' as status
                        FROM premium_subscriptions_json 
                        WHERE young_person_key = ? 
                        ORDER BY end_date DESC 
                        LIMIT 1";
        
        $expiredStmt = $conn->prepare($expiredSql);
        $expiredStmt->execute([$youngPersonKey]);
        $expiredSubscription = $expiredStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($expiredSubscription) {
            $responseData = [
                'hasSubscription' => true,
                'isActive' => false,
                'subscription' => [
                    'premiumKey' => $expiredSubscription['premium_key'],
                    'startDate' => $expiredSubscription['start_date'],
                    'endDate' => $expiredSubscription['end_date'],
                    'status' => 'expired',
                    'daysRemaining' => (int)$expiredSubscription['days_remaining'],
                    'elderlyKeys' => json_decode($expiredSubscription['elderly_keys'] ?? '[]'),
                    'note' => $expiredSubscription['note']
                ],
                'user' => [
                    'name' => $user['userName'],
                    'email' => $user['email'],
                    'youngPersonKey' => $youngPersonKey
                ]
            ];
        } else {
            $responseData = [
                'hasSubscription' => false,
                'isActive' => false,
                'subscription' => null,
                'user' => [
                    'name' => $user['userName'],
                    'email' => $user['email'],
                    'youngPersonKey' => $youngPersonKey
                ]
            ];
        }
    } else {
        $responseData = [
            'hasSubscription' => true,
            'isActive' => true,
            'subscription' => [
                'premiumKey' => $subscription['premium_key'],
                'startDate' => $subscription['start_date'],
                'endDate' => $subscription['end_date'],
                'status' => $subscription['status'],
                'daysRemaining' => (int)$subscription['days_remaining'],
                'elderlyKeys' => json_decode($subscription['elderly_keys'] ?? '[]'),
                'note' => $subscription['note']
            ],
            'user' => [
                'name' => $user['userName'],
                'email' => $user['email'],
                'youngPersonKey' => $youngPersonKey
            ]
        ];
    }
    
    sendSuccessResponse($responseData, 'Premium subscription details retrieved successfully');
    
} catch (PDOException $e) {
    error_log("Database error in get_premium_subscription.php: " . $e->getMessage());
    sendErrorResponse('Database error: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("General error in get_premium_subscription.php: " . $e->getMessage());
    sendErrorResponse('An error occurred: ' . $e->getMessage());
}
?>
