<?php
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Method not allowed', 'Only POST requests are allowed', 405);
}

try {
    // Validate required fields
    if (!isset($_POST['email']) || empty($_POST['email'])) {
        sendErrorResponse('Email is required', 'Missing email parameter');
    }

    if (!isset($_FILES['face_video']) || $_FILES['face_video']['error'] !== UPLOAD_ERR_OK) {
        sendErrorResponse('Video file is required', 'Missing or invalid video file');
    }

    $email = trim($_POST['email']);
    $videoFile = $_FILES['face_video'];

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendErrorResponse('Invalid email format', 'Please provide a valid email address');
    }

    // Validate video file
    $allowedTypes = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv'];
    $fileType = mime_content_type($videoFile['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        sendErrorResponse('Invalid video format', 'Only MP4, AVI, MOV, and WMV formats are allowed');
    }

    // Check file size (max 50MB)
    $maxSize = 50 * 1024 * 1024; // 50MB in bytes
    if ($videoFile['size'] > $maxSize) {
        sendErrorResponse('File too large', 'Video file must be less than 50MB');
    }

    // Get database connection
    $pdo = getDatabaseConnection();

    // Check if user exists
    $userStmt = $pdo->prepare("SELECT userId, userName FROM user WHERE email = ?");
    $userStmt->execute([$email]);
    $user = $userStmt->fetch();

    if (!$user) {
        sendErrorResponse('User not found', 'No user found with this email address');
    }

    // Create upload directory if it doesn't exist
    $uploadDir = 'uploads/face_data/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $timestamp = date('Y-m-d_H-i-s');
    $userId = $user['userId'];
    $fileExtension = pathinfo($videoFile['name'], PATHINFO_EXTENSION);
    $filename = "face_data_{$userId}_{$timestamp}.{$fileExtension}";
    $filepath = $uploadDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($videoFile['tmp_name'], $filepath)) {
        sendErrorResponse('Failed to save video', 'Could not save the uploaded video file');
    }

    // Save face data record to database
    $insertStmt = $pdo->prepare("
        INSERT INTO face_data (user_id, email, video_path, file_size, upload_date, status) 
        VALUES (?, ?, ?, ?, NOW(), 'uploaded')
    ");
    
    $insertStmt->execute([
        $userId,
        $email,
        $filepath,
        $videoFile['size']
    ]);

    $faceDataId = $pdo->lastInsertId();

    // Log the upload
    error_log("Face data uploaded - User: {$user['userName']} ({$email}), File: {$filename}, Size: {$videoFile['size']} bytes");

    // Return success response
    sendSuccessResponse([
        'face_data_id' => $faceDataId,
        'filename' => $filename,
        'file_size' => $videoFile['size'],
        'upload_date' => date('Y-m-d H:i:s'),
        'user_name' => $user['userName']
    ], 'Face data uploaded successfully');

} catch (Exception $e) {
    error_log("Face data upload error: " . $e->getMessage());
    sendErrorResponse('Upload failed', $e->getMessage(), 500);
}
?> 