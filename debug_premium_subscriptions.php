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

// Database configuration
$host = 'localhost';
$dbname = 'viegrand';
$username = 'root';
$password = '';

try {
    // Create database connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all premium subscriptions
    $sql = "SELECT 
                p.*,
                u.userName,
                u.email,
                u.role,
                DATEDIFF(p.end_date, NOW()) as days_remaining,
                IF(p.end_date > NOW(), 'Active', 'Expired') as status
            FROM premium_subscriptions_json p
            LEFT JOIN user u ON u.private_key = p.young_person_key
            ORDER BY p.start_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data for better readability
    $formattedSubscriptions = array_map(function($sub) {
        return [
            'premiumKey' => $sub['premium_key'],
            'youngPersonKey' => $sub['young_person_key'],
            'elderlyKeys' => json_decode($sub['elderly_keys'] ?? '[]'),
            'startDate' => $sub['start_date'],
            'endDate' => $sub['end_date'],
            'status' => $sub['status'],
            'daysRemaining' => (int)$sub['days_remaining'],
            'user' => [
                'name' => $sub['userName'],
                'email' => $sub['email'],
                'role' => $sub['role']
            ]
        ];
    }, $subscriptions);
    
    // Get table info
    $tableInfoSql = "SHOW TABLE STATUS LIKE 'premium_subscriptions_json'";
    $tableInfoStmt = $conn->prepare($tableInfoSql);
    $tableInfoStmt->execute();
    $tableInfo = $tableInfoStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Premium subscriptions data retrieved',
        'data' => [
            'totalSubscriptions' => count($subscriptions),
            'activeSubscriptions' => count(array_filter($subscriptions, function($sub) {
                return $sub['status'] === 'Active';
            })),
            'expiredSubscriptions' => count(array_filter($subscriptions, function($sub) {
                return $sub['status'] === 'Expired';
            })),
            'subscriptions' => $formattedSubscriptions,
            'tableInfo' => [
                'rows' => $tableInfo['Rows'] ?? 0,
                'dataLength' => $tableInfo['Data_length'] ?? 0,
                'created' => $tableInfo['Create_time'] ?? null
            ]
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?> 