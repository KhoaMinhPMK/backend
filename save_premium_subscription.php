<?php
// Save premium subscription after successful payment
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Include transaction code generator
require_once 'generate_transaction_code.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
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
    
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Validate JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON format']);
        exit;
    }
    
    // Extract required fields
    $userEmail = isset($data['userEmail']) ? trim($data['userEmail']) : null;
    $planType = isset($data['planType']) ? trim($data['planType']) : null;
    $planDuration = isset($data['planDuration']) ? (int)$data['planDuration'] : 1; // Default 1 month
    
    // Basic validation
    if (empty($userEmail)) {
        echo json_encode(['success' => false, 'message' => 'User email is required']);
        exit;
    }
    
    // Generate new transaction code automatically
    $premiumKey = generateTransactionCode($conn);
    
    // Get user's private key from user table
    $getUserSql = "SELECT private_key FROM user WHERE email = ? LIMIT 1";
    $getUserStmt = $conn->prepare($getUserSql);
    $getUserStmt->execute([$userEmail]);
    $user = $getUserStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    if (empty($user['private_key'])) {
        echo json_encode(['success' => false, 'message' => 'User private key not found']);
        exit;
    }
    
    // Use first 8 characters of private key for young_person_key
    $youngPersonKey = substr($user['private_key'], 0, 8);
    
    // Calculate start and end dates
    $startDate = new DateTime();
    $endDate = clone $startDate;
    $endDate->add(new DateInterval('P' . $planDuration . 'M')); // Add months
    
    // Format dates for MySQL
    $startDateStr = $startDate->format('Y-m-d H:i:s');
    $endDateStr = $endDate->format('Y-m-d H:i:s');
    
    // Set elderly_keys as empty JSON array for now
    $elderlyKeys = json_encode([]);
    
    // Check if premium_key already exists
    $checkSql = "SELECT premium_key FROM premium_subscriptions_json WHERE premium_key = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([$premiumKey]);
    
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Premium key already exists']);
        exit;
    }
    
    // Insert subscription data
    $insertSql = "INSERT INTO premium_subscriptions_json (premium_key, young_person_key, elderly_keys, start_date, end_date) VALUES (?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertSql);
    
    $result = $insertStmt->execute([
        $premiumKey,
        $youngPersonKey,
        $elderlyKeys,
        $startDateStr,
        $endDateStr
    ]);
    
    if ($result) {
        // Verify the insert
        $verifySql = "SELECT * FROM premium_subscriptions_json WHERE premium_key = ?";
        $verifyStmt = $conn->prepare($verifySql);
        $verifyStmt->execute([$premiumKey]);
        $savedData = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Premium subscription saved successfully',
            'data' => [
                'subscription' => [
                    'premiumKey' => $savedData['premium_key'],
                    'youngPersonKey' => $savedData['young_person_key'],
                    'elderlyKeys' => json_decode($savedData['elderly_keys']),
                    'startDate' => $savedData['start_date'],
                    'endDate' => $savedData['end_date'],
                    'planDuration' => $planDuration,
                    'planType' => $planType
                ]
            ]
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save premium subscription']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?> 