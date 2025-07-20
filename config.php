<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'viegrand_app');
define('DB_USER', 'root'); // Thay đổi theo cấu hình của bạn
define('DB_PASS', ''); // Thay đổi theo cấu hình của bạn

// JWT Secret (thay đổi thành secret key thật)
define('JWT_SECRET', 'viegrand_secret_key_2024');

// API Configuration
define('API_VERSION', 'v1');
define('ALLOWED_ORIGINS', ['*']); // Có thể thay đổi thành domain cụ thể

// Response helper functions
function sendSuccessResponse($data, $message = 'Success', $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => true,
        'data' => $data,
        'message' => $message
    ]);
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
            'path' => $_SERVER['REQUEST_URI'],
            'method' => $_SERVER['REQUEST_METHOD']
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
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        sendErrorResponse('Database connection failed', 'Internal server error', 500);
    }
}

// CORS headers
function setCorsHeaders() {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

// Input validation helper
function validateRequiredFields($input, $required_fields) {
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
}

// Email validation
function validateEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
}

// Password validation
function validatePassword($password) {
    if (strlen($password) < 6) {
        throw new Exception('Password must be at least 6 characters');
    }
}

// Phone validation
function validatePhone($phone) {
    if (strlen($phone) < 10) {
        throw new Exception('Phone number must be at least 10 digits');
    }
}

// Generate simple token (có thể thay thế bằng JWT thật)
function generateToken() {
    return bin2hex(random_bytes(32));
}

// JWT Helper Functions
function createJWT($userId, $email) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => $userId,
        'email' => $email,
        'iat' => time(),
        'exp' => time() + (24 * 60 * 60) // 24 hours
    ]);
    
    $headerEncoded = base64url_encode($header);
    $payloadEncoded = base64url_encode($payload);
    
    $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, JWT_SECRET, true);
    $signatureEncoded = base64url_encode($signature);
    
    return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
}

function verifyJWT($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }
    
    list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
    
    // Verify signature
    $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, JWT_SECRET, true);
    $expectedSignature = base64url_encode($signature);
    
    if ($signatureEncoded !== $expectedSignature) {
        return false;
    }
    
    // Decode payload
    $payload = json_decode(base64url_decode($payloadEncoded), true);
    if (!$payload) {
        return false;
    }
    
    // Check expiration
    if (isset($payload['exp']) && time() > $payload['exp']) {
        return false;
    }
    
    return $payload;
}

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

// Sanitize input
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}
?> 