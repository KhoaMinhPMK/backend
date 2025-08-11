<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Log all incoming requests
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'Unknown',
    'raw_input' => file_get_contents('php://input'),
    'get_params' => $_GET,
    'post_params' => $_POST,
    'headers' => getallheaders()
];

// Save to log file
$logFile = __DIR__ . '/api_debug.log';
file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND | LOCK_EX);

// Return debug info
echo json_encode([
    'success' => true,
    'message' => 'API call logged',
    'debug_info' => $logData
]);
?> 