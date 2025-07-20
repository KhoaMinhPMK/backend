<?php
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

echo "=== Premium Purchase Test ===\n\n";

// Debug request info
echo "Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set') . "\n";

// Get raw input
$rawInput = file_get_contents('php://input');
echo "Raw input: " . $rawInput . "\n";
echo "Raw input length: " . strlen($rawInput) . "\n";

// Try to decode
$input = json_decode($rawInput, true);
echo "Decoded input: " . json_encode($input) . "\n";
echo "JSON error: " . json_last_error_msg() . "\n";

// Test with hardcoded data if input is invalid
if (!$input) {
    echo "\n=== Testing with hardcoded data ===\n";
    $testData = [
        'planId' => 1,
        'paymentMethod' => 'momo'
    ];
    
    try {
        $pdo = getDatabaseConnection();
        
        // Get plan info
        $stmt = $pdo->prepare("SELECT * FROM premium_plans WHERE id = ? AND isActive = TRUE");
        $stmt->execute([$testData['planId']]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($plan) {
            echo "✅ Plan found: " . $plan['name'] . " - " . $plan['price'] . " VND\n";
            
            // Test purchase flow
            $userId = 1; // Test user
            $amount = $plan['price'];
            $transactionCode = 'TEST_' . time();
            
            echo "Testing purchase for userId: $userId\n";
            echo "Amount: $amount\n";
            echo "Transaction code: $transactionCode\n";
            
            // This would be the actual purchase logic
            $response = [
                'success' => true,
                'transaction' => [
                    'transactionCode' => $transactionCode,
                    'amount' => $amount,
                    'status' => 'completed',
                    'paymentMethod' => $testData['paymentMethod']
                ],
                'message' => 'Test purchase successful'
            ];
            
            sendSuccessResponse($response, 'Test purchase completed');
            
        } else {
            throw new Exception('Plan not found');
        }
        
    } catch (Exception $e) {
        sendErrorResponse($e->getMessage(), 'Purchase test failed', 400);
    }
}
?>
