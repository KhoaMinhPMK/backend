<?php
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Test script to verify face data upload functionality
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $pdo = getDatabaseConnection();
        
        // Get test user with private key
        $stmt = $pdo->prepare("SELECT userId, userName, email, private_key FROM user WHERE private_key IS NOT NULL AND private_key != '' LIMIT 1");
        $stmt->execute();
        $user = $stmt->fetch();
        
        if (!$user) {
            sendErrorResponse('No test user found', 'No user with private key found in database');
        }
        
        // Check face_data table structure
        $stmt = $pdo->query("DESCRIBE face_data");
        $columns = $stmt->fetchAll();
        
        // Get recent face data uploads
        $stmt = $pdo->query("SELECT id, user_id, email, private_key, file_size, upload_date, is_appended FROM face_data ORDER BY upload_date DESC LIMIT 5");
        $recentUploads = $stmt->fetchAll();
        
        sendSuccessResponse([
            'test_user' => [
                'userId' => $user['userId'],
                'userName' => $user['userName'],
                'email' => $user['email'],
                'private_key' => $user['private_key']
            ],
            'table_structure' => $columns,
            'recent_uploads' => $recentUploads,
            'upload_directory' => 'uploads/face_data/',
            'directory_exists' => file_exists('uploads/face_data/'),
            'directory_writable' => is_writable('uploads/face_data/'),
        ], 'Face data upload test information');
        
    } catch (Exception $e) {
        sendErrorResponse('Test failed', $e->getMessage());
    }
}

// Test POST endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sendSuccessResponse([
        'message' => 'POST endpoint working',
        'timestamp' => date('Y-m-d H:i:s'),
    ], 'POST endpoint working');
}
?> 