<?php
// Debug purchase API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=viegrand_db;charset=utf8mb4",
        "viegrand_user",
        "viegrand2024",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

// Get input
$rawInput = file_get_contents('php://input');
echo "Raw input: " . $rawInput . "\n";

$input = json_decode($rawInput, true);
echo "Decoded input: " . print_r($input, true) . "\n";

if (!$input) {
    $input = $_POST;
    echo "POST data: " . print_r($input, true) . "\n";
}

// Test database
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM premium_plans");
    $result = $stmt->fetch();
    echo "Premium plans count: " . $result['count'] . "\n";
    
    $stmt = $pdo->query("SELECT * FROM premium_plans WHERE isActive = TRUE LIMIT 1");
    $plan = $stmt->fetch();
    echo "Sample plan: " . print_r($plan, true) . "\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

// Test purchase logic
if (!empty($input['planId'])) {
    $planId = (int)$input['planId'];
    $paymentMethod = $input['paymentMethod'] ?? 'momo';
    $userId = 1;
    
    try {
        // Get plan info
        $stmt = $pdo->prepare("SELECT * FROM premium_plans WHERE id = ? AND isActive = TRUE");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($plan) {
            echo "Plan found: " . print_r($plan, true) . "\n";
            
            // Test transaction
            $pdo->beginTransaction();
            
            // Update user
            $endDate = date('Y-m-d H:i:s', strtotime('+' . $plan['duration'] . ' days'));
            $stmt = $pdo->prepare("
                UPDATE users 
                SET 
                    isPremium = TRUE,
                    premiumStartDate = NOW(),
                    premiumEndDate = ?,
                    premiumPlanId = ?
                WHERE id = ?
            ");
            $result = $stmt->execute([$endDate, $planId, $userId]);
            echo "User update result: " . ($result ? 'success' : 'failed') . "\n";
            
            $pdo->rollBack(); // Don't commit in debug
            echo "Test completed successfully\n";
            
        } else {
            echo "Plan not found\n";
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
}
?>
