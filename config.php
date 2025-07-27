<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'viegrand');
define('DB_USER', 'root');
define('DB_PASS', '');

// API Configuration
define('API_VERSION', 'v1');
define('CORS_ORIGINS', ['*']); // Cho phép tất cả origins, có thể thay đổi theo domain cụ thể

// JWT Configuration
define('JWT_SECRET', 'viegrand_secret_key_2024');
define('JWT_ALGORITHM', 'HS256');

// Response helper functions
function sendSuccessResponse($data, $message = 'Success', $statusCode = 200) {
    error_log("🔍 sendSuccessResponse - Called with data: " . json_encode($data));
    error_log("🔍 sendSuccessResponse - Message: " . $message);
    error_log("🔍 sendSuccessResponse - Status code: " . $statusCode);
    
    http_response_code($statusCode);
    $response = [
        'success' => true,
        'data' => $data,
        'message' => $message
    ];
    
    $jsonResponse = json_encode($response);
    error_log("🔍 sendSuccessResponse - JSON response: " . $jsonResponse);
    
    echo $jsonResponse;
    error_log("✅ sendSuccessResponse - Response sent successfully");
    exit();
}

function sendErrorResponse($message, $error = 'Bad request', $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'error' => [
            'statusCode' => $statusCode,
            'message' => $message,
            'error' => $error,
            'timestamp' => date('Y-m-d H:i:s'),
            'path' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET'
        ]
    ]);
    exit();
}

// Database connection function
function getDatabaseConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
            DB_USER, 
            DB_PASS
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        sendErrorResponse('Database connection failed', 'Internal server error', 500);
    }
}

// CORS headers function
function setCorsHeaders() {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400');
    
    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

// Input validation helpers
function validateRequiredFields($input, $required_fields) {
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        throw new Exception("Missing required fields: " . implode(', ', $missing_fields));
    }
}

function validateEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
}

function validatePhone($phone) {
    // Đơn giản hóa: chỉ kiểm tra độ dài
    if (strlen($phone) < 10 || strlen($phone) > 15) {
        throw new Exception('Phone number must be between 10-15 characters');
    }
}

function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// JWT Token functions
function generateJWT($payload) {
    $header = json_encode(['typ' => 'JWT', 'alg' => JWT_ALGORITHM]);
    $payload = json_encode($payload);
    
    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, JWT_SECRET, true);
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64Header . "." . $base64Payload . "." . $base64Signature;
}

function verifyJWT($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }
    
    $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[0]));
    $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
    $signature = $parts[2];
    
    $expectedSignature = hash_hmac('sha256', $parts[0] . "." . $parts[1], JWT_SECRET, true);
    $base64ExpectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expectedSignature));
    
    if ($signature !== $base64ExpectedSignature) {
        return false;
    }
    
    return json_decode($payload, true);
}

// Utility function to get user ID from Authorization header
function getUserIdFromToken() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return null;
    }
    
    $token = $matches[1];
    $payload = verifyJWT($token);
    
    return $payload ? $payload['userId'] : null;
}

// Error logging
function logError($message, $context = []) {
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context,
        'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
        'method' => $_SERVER['REQUEST_METHOD'] ?? '',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];
    
    error_log(json_encode($log));
}

// Function to send notification to Node.js server
function send_socket_notification($to_phone, $payload) {
    //
    // IMPORTANT: Use the correct IP address or domain of your Node.js server.
    // If running on the same machine for development, you can use 'http://localhost:3000/notify'.
    // For production, use your public domain e.g., 'https://chat.viegrand.site/notify'.
    //
    $nodeServerUrl = 'https://chat.viegrand.site/notify'; 
    $secretKey = 'viegrand_super_secret_key_for_php_2025'; // Must match the key in server.js

    $data = [
        'to_phone' => $to_phone,
        'payload'  => $payload,
        'secret'   => $secretKey,
    ];

    $ch = curl_init($nodeServerUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen(json_encode($data))
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5-second timeout

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    // Log the result for debugging
    if ($error) {
        logError("cURL Error for Socket Notification: " . $error, ['to_phone' => $to_phone]);
        return false;
    }

    logError("Socket notification sent to $to_phone. Status: $httpcode. Response: $response");
    
    return $httpcode === 200;
}

// Function to generate conversation ID
function generateConversationId($phone1, $phone2) {
    // Sắp xếp để đảm bảo consistency
    $sorted = [$phone1, $phone2];
    sort($sorted);
    return 'conv_' . md5($sorted[0] . '_' . $sorted[1]);
}
?>
