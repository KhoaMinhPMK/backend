<?php
require_once 'config.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Method not allowed', 'Method not allowed', 405);
    exit;
}

try {
    $conn = getDatabaseConnection();
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse('Invalid JSON format', 'Bad request', 400);
        exit;
    }
    
    $userPrivateKey = isset($data['user_private_key']) ? sanitizeInput($data['user_private_key']) : null;
    
    if (!$userPrivateKey) {
        sendErrorResponse('User private key is required', 'Bad request', 400);
        exit;
    }
    
    // Get user information
    $userSql = "SELECT userId, userName, email, phone, age, gender, role, private_key FROM user WHERE private_key = ?";
    $userStmt = $conn->prepare($userSql);
    $userStmt->execute([$userPrivateKey]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        sendErrorResponse('User not found', 'Not found', 404);
        exit;
    }
    
    $familyMembers = [];
    $subscriptionInfo = null;
    
    if ($user['role'] === 'elderly') {
        // For elderly users, find their premium subscription
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
                            WHERE JSON_CONTAINS(elderly_keys, ?)
                            ORDER BY end_date DESC 
                            LIMIT 1";
        
        $subscriptionStmt = $conn->prepare($subscriptionSql);
        $subscriptionStmt->execute([json_encode($userPrivateKey)]);
        $subscription = $subscriptionStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($subscription) {
            // Get relative (young person) information
            $relativeSql = "SELECT userId, userName, email, phone, age, gender, role, private_key FROM user WHERE private_key = ? AND role = 'relative'";
            $relativeStmt = $conn->prepare($relativeSql);
            $relativeStmt->execute([$subscription['young_person_key']]);
            $relative = $relativeStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($relative) {
                $familyMembers[] = [
                    'userId' => (int)$relative['userId'],
                    'userName' => $relative['userName'],
                    'email' => $relative['email'],
                    'phone' => $relative['phone'],
                    'age' => (int)$relative['age'],
                    'gender' => $relative['gender'],
                    'role' => $relative['role'],
                    'private_key' => $relative['private_key'],
                    'memberType' => 'relative',
                    'isManager' => true
                ];
            }
            
            // Get all other elderly users in this subscription
            $elderlyKeys = json_decode($subscription['elderly_keys'], true);
            if (is_array($elderlyKeys)) {
                foreach ($elderlyKeys as $key) {
                    if ($key !== $userPrivateKey) {
                        $otherElderlySql = "SELECT userId, userName, email, phone, age, gender, role, private_key FROM user WHERE private_key = ? AND role = 'elderly'";
                        $otherElderlyStmt = $conn->prepare($otherElderlySql);
                        $otherElderlyStmt->execute([$key]);
                        $otherElderly = $otherElderlyStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($otherElderly) {
                            $familyMembers[] = [
                                'userId' => (int)$otherElderly['userId'],
                                'userName' => $otherElderly['userName'],
                                'email' => $otherElderly['email'],
                                'phone' => $otherElderly['phone'],
                                'age' => (int)$otherElderly['age'],
                                'gender' => $otherElderly['gender'],
                                'role' => $otherElderly['role'],
                                'private_key' => $otherElderly['private_key'],
                                'memberType' => 'elderly',
                                'isManager' => false
                            ];
                        }
                    }
                }
            }
            
            $subscriptionInfo = [
                'premiumKey' => $subscription['premium_key'],
                'startDate' => $subscription['start_date'],
                'endDate' => $subscription['end_date'],
                'status' => $subscription['status'],
                'daysRemaining' => (int)$subscription['days_remaining'],
                'note' => $subscription['note']
            ];
        }
        
    } elseif ($user['role'] === 'relative') {
        // For relative users, find their premium subscription
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
                            ORDER BY end_date DESC 
                            LIMIT 1";
        
        $subscriptionStmt = $conn->prepare($subscriptionSql);
        $subscriptionStmt->execute([$userPrivateKey]);
        $subscription = $subscriptionStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($subscription) {
            // Get all elderly users in this subscription
            $elderlyKeys = json_decode($subscription['elderly_keys'], true);
            if (is_array($elderlyKeys)) {
                foreach ($elderlyKeys as $key) {
                    $elderlySql = "SELECT userId, userName, email, phone, age, gender, role, private_key FROM user WHERE private_key = ? AND role = 'elderly'";
                    $elderlyStmt = $conn->prepare($elderlySql);
                    $elderlyStmt->execute([$key]);
                    $elderly = $elderlyStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($elderly) {
                        $familyMembers[] = [
                            'userId' => (int)$elderly['userId'],
                            'userName' => $elderly['userName'],
                            'email' => $elderly['email'],
                            'phone' => $elderly['phone'],
                            'age' => (int)$elderly['age'],
                            'gender' => $elderly['gender'],
                            'role' => $elderly['role'],
                            'private_key' => $elderly['private_key'],
                            'memberType' => 'elderly',
                            'isManager' => false
                        ];
                    }
                }
            }
            
            $subscriptionInfo = [
                'premiumKey' => $subscription['premium_key'],
                'startDate' => $subscription['start_date'],
                'endDate' => $subscription['end_date'],
                'status' => $subscription['status'],
                'daysRemaining' => (int)$subscription['days_remaining'],
                'note' => $subscription['note']
            ];
        }
    }
    
    // Add avatar generation for each member
    $formattedMembers = array_map(function($member) {
        $nameParts = explode(' ', trim($member['userName']));
        $avatar = '';
        
        if (count($nameParts) >= 2) {
            $avatar = mb_substr($nameParts[0], 0, 1, 'UTF-8') . mb_substr($nameParts[1], 0, 1, 'UTF-8');
        } else {
            $avatar = mb_substr($member['userName'], 0, 2, 'UTF-8');
        }
        
        return array_merge($member, [
            'avatar' => strtoupper($avatar)
        ]);
    }, $familyMembers);
    
    $responseData = [
        'user' => [
            'userId' => (int)$user['userId'],
            'userName' => $user['userName'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'age' => (int)$user['age'],
            'gender' => $user['gender'],
            'role' => $user['role'],
            'private_key' => $user['private_key']
        ],
        'familyMembers' => $formattedMembers,
        'totalMembers' => count($formattedMembers),
        'subscription' => $subscriptionInfo,
        'hasPremiumFamily' => $subscriptionInfo !== null
    ];
    
    sendSuccessResponse($responseData, 'Premium family members retrieved successfully');
    
} catch (Exception $e) {
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?> 