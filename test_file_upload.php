<?php
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Simple file upload test endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Log all incoming data
        error_log("Test file upload - POST data: " . print_r($_POST, true));
        error_log("Test file upload - FILES data: " . print_r($_FILES, true));
        
        $response = [
            'success' => true,
            'message' => 'File upload test successful',
            'received_data' => [
                'post' => $_POST,
                'files' => $_FILES,
                'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
                'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'not set',
            ],
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        
        // If a file was uploaded, provide more details
        if (isset($_FILES['face_video']) && $_FILES['face_video']['error'] === UPLOAD_ERR_OK) {
            $response['file_info'] = [
                'name' => $_FILES['face_video']['name'],
                'size' => $_FILES['face_video']['size'],
                'type' => $_FILES['face_video']['type'],
                'tmp_name' => $_FILES['face_video']['tmp_name'],
            ];
        }
        
        sendSuccessResponse($response, 'File upload test completed');
        
    } catch (Exception $e) {
        error_log("Test file upload error: " . $e->getMessage());
        sendErrorResponse('Test upload failed', $e->getMessage());
    }
}

// GET request - show server info
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    sendSuccessResponse([
        'status' => 'ok',
        'message' => 'File upload test endpoint is working',
        'server_info' => [
            'php_version' => PHP_VERSION,
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit'),
            'file_uploads' => ini_get('file_uploads'),
        ],
        'timestamp' => date('Y-m-d H:i:s'),
    ], 'File upload test endpoint ready');
}
?> 