<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get elderly private key from query parameter
    $elderlyPrivateKey = $_GET['elderly_private_key'] ?? null;
    
    if (!$elderlyPrivateKey) {
        echo json_encode(['success' => false, 'message' => 'Elderly private key is required']);
        exit;
    }
    
    // First, verify that the elderly user exists
    $elderlyStmt = $pdo->prepare("SELECT userId, userName, email, phone, age, gender, role FROM user WHERE private_key = ? AND role = 'elderly'");
    $elderlyStmt->execute([$elderlyPrivateKey]);
    $elderly = $elderlyStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$elderly) {
        echo json_encode(['success' => false, 'message' => 'Elderly user not found']);
        exit;
    }
    
    // Find premium subscription that includes this elderly user
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
    
    $subscriptionStmt = $pdo->prepare($subscriptionSql);
    $subscriptionStmt->execute([json_encode($elderlyPrivateKey)]);
    $subscription = $subscriptionStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$subscription) {
        // Elderly user is not in any premium subscription
        echo json_encode([
            'success' => true,
            'data' => [
                'hasSubscription' => false,
                'isActive' => false,
                'subscription' => null,
                'elderly' => [
                    'userId' => (int)$elderly['userId'],
                    'userName' => $elderly['userName'],
                    'email' => $elderly['email'],
                    'phone' => $elderly['phone'],
                    'age' => (int)$elderly['age'],
                    'gender' => $elderly['gender'],
                    'private_key' => $elderlyPrivateKey
                ],
                'relative' => null
            ],
            'message' => 'Elderly user is not in any premium subscription'
        ]);
        exit;
    }
    
    // Get relative (young person) information
    $relativeStmt = $pdo->prepare("SELECT userId, userName, email, phone, age, gender FROM user WHERE private_key = ? AND role = 'relative'");
    $relativeStmt->execute([$subscription['young_person_key']]);
    $relative = $relativeStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$relative) {
        echo json_encode(['success' => false, 'message' => 'Relative user not found']);
        exit;
    }
    
    // Parse elderly_keys array
    $elderlyKeys = json_decode($subscription['elderly_keys'], true);
    if (!is_array($elderlyKeys)) {
        $elderlyKeys = [];
    }
    
    // Get all elderly users in this subscription
    $allElderlyUsers = [];
    foreach ($elderlyKeys as $key) {
        $otherElderlyStmt = $pdo->prepare("SELECT userId, userName, email, phone, age, gender FROM user WHERE private_key = ? AND role = 'elderly'");
        $otherElderlyStmt->execute([$key]);
        $otherElderly = $otherElderlyStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($otherElderly) {
            $allElderlyUsers[] = [
                'userId' => (int)$otherElderly['userId'],
                'userName' => $otherElderly['userName'],
                'email' => $otherElderly['email'],
                'phone' => $otherElderly['phone'],
                'age' => (int)$otherElderly['age'],
                'gender' => $otherElderly['gender'],
                'private_key' => $key
            ];
        }
    }
    
    $responseData = [
        'hasSubscription' => true,
        'isActive' => $subscription['status'] === 'active',
        'subscription' => [
            'premiumKey' => $subscription['premium_key'],
            'startDate' => $subscription['start_date'],
            'endDate' => $subscription['end_date'],
            'status' => $subscription['status'],
            'daysRemaining' => (int)$subscription['days_remaining'],
            'note' => $subscription['note'],
            'elderlyCount' => count($allElderlyUsers)
        ],
        'elderly' => [
            'userId' => (int)$elderly['userId'],
            'userName' => $elderly['userName'],
            'email' => $elderly['email'],
            'phone' => $elderly['phone'],
            'age' => (int)$elderly['age'],
            'gender' => $elderly['gender'],
            'private_key' => $elderlyPrivateKey
        ],
        'relative' => [
            'userId' => (int)$relative['userId'],
            'userName' => $relative['userName'],
            'email' => $relative['email'],
            'phone' => $relative['phone'],
            'age' => (int)$relative['age'],
            'gender' => $relative['gender'],
            'private_key' => $subscription['young_person_key']
        ],
        'allElderlyUsers' => $allElderlyUsers
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $responseData,
        'message' => 'Elderly premium information retrieved successfully'
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?> 