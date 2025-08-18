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

    // Check if user exists and get private key
    $userStmt = $pdo->prepare("SELECT userId, userName, private_key FROM user WHERE email = ?");
    $userStmt->execute([$email]);
    $user = $userStmt->fetch();

    if (!$user) {
        sendErrorResponse('User not found', 'No user found with this email address');
    }

    if (empty($user['private_key'])) {
        sendErrorResponse('Private key not found', 'User does not have a private key');
    }

    $privateKey = $user['private_key'];
    $userId = $user['userId'];

    // Create upload directory if it doesn't exist
    $uploadDir = 'uploads/face_data/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate filename using private key
    $timestamp = date('Y-m-d_H-i-s');
    $fileExtension = pathinfo($videoFile['name'], PATHINFO_EXTENSION);
    $filename = "face_data_{$privateKey}.{$fileExtension}";
    $filepath = $uploadDir . $filename;

    // Check if file already exists
    $fileExists = file_exists($filepath);
    $originalSize = $fileExists ? filesize($filepath) : 0;

    // Handle file upload - append if exists, create new if not
    if ($fileExists) {
        // Check if FFmpeg is available
        $ffmpegAvailable = false;
        $ffmpegOutput = [];
        exec('ffmpeg -version 2>&1', $ffmpegOutput, $ffmpegReturnCode);
        $ffmpegAvailable = ($ffmpegReturnCode === 0);
        
        if ($ffmpegAvailable) {
            // Use FFmpeg for proper video concatenation
            $tempNewFile = $videoFile['tmp_name'];
            
            // Create a temporary file list for FFmpeg
            $tempListFile = tempnam(sys_get_temp_dir(), 'video_list_');
            $listContent = "file '" . realpath($filepath) . "'\n";
            $listContent .= "file '" . realpath($tempNewFile) . "'\n";
            file_put_contents($tempListFile, $listContent);
            
            // Create temporary output file
            $tempOutputFile = tempnam(sys_get_temp_dir(), 'combined_video_') . '.' . $fileExtension;
            
            // Use FFmpeg to concatenate videos
            $ffmpegCommand = "ffmpeg -f concat -safe 0 -i '{$tempListFile}' -c copy '{$tempOutputFile}' 2>&1";
            $output = [];
            $returnCode = 0;
            
            exec($ffmpegCommand, $output, $returnCode);
            
            // Clean up temporary list file
            unlink($tempListFile);
            
            if ($returnCode !== 0) {
                error_log("FFmpeg concatenation failed: " . implode("\n", $output));
                sendErrorResponse('Failed to append video', 'Video concatenation failed: ' . implode("\n", $output));
            }
            
            // Replace original file with combined file
            if (!rename($tempOutputFile, $filepath)) {
                sendErrorResponse('Failed to save combined video', 'Could not save the combined video file');
            }
            
            // Log the append operation
            $newSize = filesize($filepath);
            error_log("Video content appended using FFmpeg - Original size: {$originalSize}, New size: {$newSize}");
            
        } else {
            // Fallback: Create a new file with timestamp to preserve both videos
            $timestamp = date('Y-m-d_H-i-s');
            $newFilename = "face_data_{$privateKey}_{$timestamp}.{$fileExtension}";
            $newFilepath = $uploadDir . $newFilename;
            
            // Move uploaded file to new location
            if (!move_uploaded_file($videoFile['tmp_name'], $newFilepath)) {
                sendErrorResponse('Failed to save video', 'Could not save the uploaded video file');
            }
            
            // Update filepath to new file
            $filepath = $newFilepath;
            $filename = $newFilename;
            
            // Log the fallback operation
            error_log("FFmpeg not available - Created new file: {$newFilename} (FFmpeg concatenation not possible)");
        }
        
    } else {
        // Move uploaded file for new file
        if (!move_uploaded_file($videoFile['tmp_name'], $filepath)) {
            sendErrorResponse('Failed to save video', 'Could not save the uploaded video file');
        }
    }

    // Get final file size
    $finalSize = filesize($filepath);

    // Save face data record to database
    $insertStmt = $pdo->prepare("
        INSERT INTO face_data (user_id, email, video_path, file_size, upload_date, status, private_key, is_appended) 
        VALUES (?, ?, ?, ?, NOW(), 'uploaded', ?, ?)
    ");
    
    $isAppended = $fileExists ? 1 : 0;
    $insertStmt->execute([
        $userId,
        $email,
        $filepath,
        $finalSize,
        $privateKey,
        $isAppended
    ]);

    $faceDataId = $pdo->lastInsertId();

    // Log the upload
    $logMessage = $fileExists 
        ? "Face data appended - User: {$user['userName']} ({$email}), File: {$filename}, Original size: {$originalSize}, Final size: {$finalSize} bytes"
        : "Face data uploaded - User: {$user['userName']} ({$email}), File: {$filename}, Size: {$finalSize} bytes";
    
    error_log($logMessage);

    // Return success response
    sendSuccessResponse([
        'face_data_id' => $faceDataId,
        'filename' => $filename,
        'private_key' => $privateKey,
        'file_size' => $finalSize,
        'upload_date' => date('Y-m-d H:i:s'),
        'user_name' => $user['userName'],
        'is_appended' => $isAppended,
        'original_size' => $originalSize,
        'message' => $fileExists ? 'Face data appended successfully' : 'Face data uploaded successfully'
    ], $fileExists ? 'Face data appended successfully' : 'Face data uploaded successfully');

} catch (Exception $e) {
    error_log("Face data upload error: " . $e->getMessage());
    sendErrorResponse('Upload failed', $e->getMessage(), 500);
}
?> 