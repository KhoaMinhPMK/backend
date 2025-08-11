<?php
require_once 'config.php';

setCorsHeaders();

// Chỉ cho phép POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Method not allowed', 'Method not allowed', 405);
    exit;
}

try {
    $conn = getDatabaseConnection();
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse('Invalid JSON format', 'Bad request', 400);
        exit;
    }
    
    $email = isset($data['email']) ? sanitizeInput($data['email']) : null;
    $deviceToken = isset($data['device_token']) ? sanitizeInput($data['device_token']) : null;
    
    if (!$email) {
        sendErrorResponse('Email is required', 'Bad request', 400);
        exit;
    }
    
    if (!$deviceToken) {
        sendErrorResponse('Device token is required', 'Bad request', 400);
        exit;
    }
    
    // Check if device_token column exists, if not create it
    $checkColumnSql = "SHOW COLUMNS FROM user LIKE 'device_token'";
    $checkColumnStmt = $conn->prepare($checkColumnSql);
    $checkColumnStmt->execute();
    $columnExists = $checkColumnStmt->rowCount() > 0;
    
    if (!$columnExists) {
        // Add device_token column
        $addColumnSql = "ALTER TABLE user ADD COLUMN device_token VARCHAR(255) NULL";
        $addColumnStmt = $conn->prepare($addColumnSql);
        $addColumnStmt->execute();
        error_log("✅ Device token column added to user table");
    }
    
    // Update device token for the user
    $updateSql = "UPDATE user SET device_token = ? WHERE email = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateResult = $updateStmt->execute([$deviceToken, $email]);
    
    if ($updateResult) {
        $affectedRows = $updateStmt->rowCount();
        if ($affectedRows > 0) {
            error_log("✅ Device token updated for user: $email");
            sendSuccessResponse(['updated' => true], 'Device token updated successfully');
        } else {
            error_log("⚠️ No user found with email: $email");
            sendErrorResponse('User not found', 'User not found', 404);
        }
    } else {
        error_log("❌ Failed to update device token for user: $email");
        sendErrorResponse('Failed to update device token', 'Database error', 500);
    }
    
} catch (Exception $e) {
    error_log('❌ update_device_token.php - Exception caught: ' . $e->getMessage());
    error_log('❌ update_device_token.php - Stack trace: ' . $e->getTraceAsString());
    sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
}
?> 