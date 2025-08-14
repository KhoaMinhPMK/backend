<?php
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Method not allowed', 'Only POST method is allowed', 405);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendErrorResponse('Invalid JSON input', 'Request body must be valid JSON', 400);
        exit;
    }
    
    // Validate required fields
    $required_fields = ['private_key', 'blood_pressure_systolic', 'blood_pressure_diastolic', 'heart_rate'];
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        sendErrorResponse('Missing required fields', 'Missing fields: ' . implode(', ', $missing_fields), 400);
        exit;
    }
    
    // Extract and sanitize data
    $private_key = sanitizeInput($input['private_key']);
    $blood_pressure_systolic = intval($input['blood_pressure_systolic']);
    $blood_pressure_diastolic = intval($input['blood_pressure_diastolic']);
    $heart_rate = intval($input['heart_rate']);
    
    // Validate data ranges
    if ($blood_pressure_systolic < 50 || $blood_pressure_systolic > 300) {
        sendErrorResponse('Invalid systolic blood pressure', 'Systolic blood pressure must be between 50 and 300 mmHg', 400);
        exit;
    }
    
    if ($blood_pressure_diastolic < 30 || $blood_pressure_diastolic > 200) {
        sendErrorResponse('Invalid diastolic blood pressure', 'Diastolic blood pressure must be between 30 and 200 mmHg', 400);
        exit;
    }
    
    if ($heart_rate < 30 || $heart_rate > 250) {
        sendErrorResponse('Invalid heart rate', 'Heart rate must be between 30 and 250 bpm', 400);
        exit;
    }
    
    // Get database connection
    $pdo = getDatabaseConnection();
    
    // Check if user exists with this private key
    $stmt = $pdo->prepare("SELECT userId FROM user WHERE private_key = ?");
    $stmt->execute([$private_key]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        sendErrorResponse('User not found', 'No user found with the provided private key', 404);
        exit;
    }
    
    // Always insert a new record for tracking health data over time
    $stmt = $pdo->prepare("
        INSERT INTO vital_signs (private_key, blood_pressure_systolic, blood_pressure_diastolic, heart_rate)
        VALUES (?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $private_key,
        $blood_pressure_systolic,
        $blood_pressure_diastolic,
        $heart_rate
    ]);
    
    if ($result) {
        // Log the insertion
        logError('Vital signs recorded', [
            'private_key' => $private_key,
            'systolic' => $blood_pressure_systolic,
            'diastolic' => $blood_pressure_diastolic,
            'heart_rate' => $heart_rate,
            'action' => 'insert_new_record'
        ]);
        
        sendSuccessResponse([
            'action' => 'recorded',
            'private_key' => $private_key,
            'blood_pressure_systolic' => $blood_pressure_systolic,
            'blood_pressure_diastolic' => $blood_pressure_diastolic,
            'heart_rate' => $heart_rate,
            'timestamp' => date('Y-m-d H:i:s')
        ], 'Vital signs recorded successfully');
    } else {
        sendErrorResponse('Database error', 'Failed to record vital signs', 500);
    }
    
} catch (PDOException $e) {
    logError('Database error in save_vital_signs', [
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
        'input' => $input ?? 'No input'
    ]);
    
    sendErrorResponse('Database error', 'An error occurred while saving vital signs data', 500);
    
} catch (Exception $e) {
    logError('Unexpected error in save_vital_signs', [
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
        'input' => $input ?? 'No input'
    ]);
    
    sendErrorResponse('Server error', 'An unexpected error occurred', 500);
}
?> 