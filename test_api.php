<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = [
    'message' => 'API Debug Script',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'Not set'
];

try {
    // Test database connection
    $pdo = new PDO('mysql:host=localhost;dbname=viegrand_app;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $response['database'] = [
        'status' => 'connected',
        'host' => 'localhost',
        'database' => 'viegrand_app'
    ];
    
    // Test if users table exists and get structure
    try {
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['users_table'] = [
            'exists' => true,
            'columns' => array_column($columns, 'Field')
        ];
        
        // Count users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $userCount = $stmt->fetch(PDO::FETCH_ASSOC);
        $response['users_table']['count'] = $userCount['count'];
        
        // Get sample users (limit 3)
        $stmt = $pdo->query("SELECT id, fullName, email, role, isPremium, created_at FROM users LIMIT 3");
        $sampleUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response['users_table']['samples'] = $sampleUsers;
        
    } catch (PDOException $e) {
        $response['users_table'] = [
            'exists' => false,
            'error' => $e->getMessage()
        ];
    }
    
    // Test premium_plans table
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM premium_plans");
        $planCount = $stmt->fetch(PDO::FETCH_ASSOC);
        $response['premium_plans'] = [
            'exists' => true,
            'count' => $planCount['count']
        ];
    } catch (PDOException $e) {
        $response['premium_plans'] = [
            'exists' => false,
            'error' => $e->getMessage()
        ];
    }
    
} catch (PDOException $e) {
    $response['database'] = [
        'status' => 'error',
        'error_message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ];
}

// If POST request, test signup functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $response['post_request'] = [
        'received_data' => $input,
        'data_size' => strlen(file_get_contents('php://input'))
    ];
    
    // Test validation functions
    if ($input) {
        $response['validation_tests'] = [];
        
        // Test email validation
        if (isset($input['email'])) {
            $response['validation_tests']['email'] = filter_var($input['email'], FILTER_VALIDATE_EMAIL) !== false;
        }
        
        // Test password length
        if (isset($input['password'])) {
            $response['validation_tests']['password_length'] = strlen($input['password']) >= 6;
        }
        
        // Test phone format (basic)
        if (isset($input['phone'])) {
            $response['validation_tests']['phone_format'] = preg_match('/^\d{10}$/', $input['phone']);
        }
    }
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
