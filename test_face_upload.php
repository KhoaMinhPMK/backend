<?php
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Simple test endpoint
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    sendSuccessResponse([
        'status' => 'ok',
        'message' => 'Face upload server is working',
        'timestamp' => date('Y-m-d H:i:s'),
        'server_info' => [
            'php_version' => PHP_VERSION,
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time'),
        ]
    ], 'Server is accessible');
}

// Test POST endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    sendSuccessResponse([
        'received_data' => $data,
        'message' => 'POST request received successfully',
        'timestamp' => date('Y-m-d H:i:s'),
    ], 'POST endpoint working');
}
?> 