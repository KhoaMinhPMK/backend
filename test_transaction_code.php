<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include transaction code generator
require_once 'generate_transaction_code.php';

// Database configuration
$host = 'localhost';
$dbname = 'viegrand';
$username = 'root';
$password = '';

try {
    // Create database connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Test transaction code generation
        $testInfo = testTransactionCodeGeneration();
        
        // Generate 5 sample codes
        $sampleCodes = [];
        for ($i = 0; $i < 5; $i++) {
            $code = generateTransactionCode($conn);
            $sampleCodes[] = [
                'code' => $code,
                'breakdown' => [
                    'day' => substr($code, 0, 2),
                    'sequence' => substr($code, 2, 10),
                    'month_year' => substr($code, 12, 4),
                    'length' => strlen($code)
                ]
            ];
            
            // Add small delay to ensure different sequence numbers
            usleep(1000); // 1ms delay
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Transaction code test',
            'data' => [
                'format_info' => $testInfo,
                'current_datetime' => date('Y-m-d H:i:s'),
                'sample_codes' => $sampleCodes,
                'next_sequence' => getNextSequenceNumber($conn)
            ]
        ], JSON_PRETTY_PRINT);
        
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Generate and save a test transaction
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        $userEmail = isset($data['userEmail']) ? trim($data['userEmail']) : 'test@example.com';
        $planType = isset($data['planType']) ? trim($data['planType']) : 'monthly';
        $planDuration = isset($data['planDuration']) ? (int)$data['planDuration'] : 1;
        
        // Generate transaction code
        $transactionCode = generateTransactionCode($conn);
        
        // Get user's private key (if exists)
        $getUserSql = "SELECT private_key FROM user WHERE email = ? LIMIT 1";
        $getUserStmt = $conn->prepare($getUserSql);
        $getUserStmt->execute([$userEmail]);
        $user = $getUserStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || empty($user['private_key'])) {
            echo json_encode([
                'success' => false,
                'message' => 'User not found or no private key',
                'generated_code' => $transactionCode
            ]);
            exit;
        }
        
        $youngPersonKey = $user['private_key'];
        
        // Calculate dates
        $startDate = new DateTime();
        $endDate = clone $startDate;
        $endDate->add(new DateInterval('P' . $planDuration . 'M'));
        
        $startDateStr = $startDate->format('Y-m-d H:i:s');
        $endDateStr = $endDate->format('Y-m-d H:i:s');
        
        $elderlyKeys = json_encode([]);
        
        // Insert test subscription
        $insertSql = "INSERT INTO premium_subscriptions_json (premium_key, young_person_key, elderly_keys, start_date, end_date) VALUES (?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        
        $result = $insertStmt->execute([
            $transactionCode,
            $youngPersonKey,
            $elderlyKeys,
            $startDateStr,
            $endDateStr
        ]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Test transaction created successfully',
                'data' => [
                    'transaction_code' => $transactionCode,
                    'breakdown' => [
                        'day' => substr($transactionCode, 0, 2),
                        'sequence' => substr($transactionCode, 2, 10),
                        'month_year' => substr($transactionCode, 12, 4),
                        'length' => strlen($transactionCode)
                    ],
                    'user_email' => $userEmail,
                    'young_person_key' => $youngPersonKey,
                    'plan_type' => $planType,
                    'plan_duration' => $planDuration,
                    'start_date' => $startDateStr,
                    'end_date' => $endDateStr
                ]
            ], JSON_PRETTY_PRINT);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create test transaction'
            ]);
        }
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?> 