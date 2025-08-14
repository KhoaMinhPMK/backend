<?php
require_once 'config.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendErrorResponse('Method not allowed', 'Only GET method is allowed', 405);
    exit;
}

try {
    // Get query parameters
    $private_key = $_GET['private_key'] ?? '';
    $period = $_GET['period'] ?? '7d';
    
    if (empty($private_key)) {
        sendErrorResponse('Missing private key', 'Private key is required', 400);
        exit;
    }
    
    // Validate period parameter
    $valid_periods = ['1d', '7d', '30d', '90d', '1y'];
    if (!in_array($period, $valid_periods)) {
        sendErrorResponse('Invalid period', 'Period must be one of: ' . implode(', ', $valid_periods), 400);
        exit;
    }
    
    // Sanitize input
    $private_key = sanitizeInput($private_key);
    
    // Get database connection
    $pdo = getDatabaseConnection();
    
    // Check if user exists with this private key
    $stmt = $pdo->prepare("SELECT userId FROM user WHERE private_key_nguoi_nhan = ?");
    $stmt->execute([$private_key]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        sendErrorResponse('User not found', 'No user found with the provided private key', 404);
        exit;
    }
    
    // Calculate date range based on period
    $now = new DateTime();
    $period_map = [
        '1d' => 1,
        '7d' => 7,
        '30d' => 30,
        '90d' => 90,
        '1y' => 365
    ];
    
    $days_back = $period_map[$period];
    $start_date = (clone $now)->modify("-{$days_back} days")->format('Y-m-d H:i:s');
    
    // Get vital signs data for the user within the specified period
    $stmt = $pdo->prepare("
        SELECT id, private_key, blood_pressure_systolic, blood_pressure_diastolic, heart_rate, created_at
        FROM vital_signs 
        WHERE private_key = ? AND created_at >= ?
        ORDER BY created_at ASC
    ");
    
    $stmt->execute([$private_key, $start_date]);
    $vital_signs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log the query for debugging
    logError('Vital signs data retrieved', [
        'private_key' => $private_key,
        'period' => $period,
        'start_date' => $start_date,
        'record_count' => count($vital_signs)
    ]);
    
    sendSuccessResponse([
        'vital_signs' => $vital_signs,
        'period' => $period,
        'start_date' => $start_date,
        'total_records' => count($vital_signs)
    ], 'Vital signs data retrieved successfully');
    
} catch (PDOException $e) {
    logError('Database error in get_vital_signs', [
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
        'private_key' => $private_key ?? 'Not provided',
        'trace' => $e->getTraceAsString()
    ]);
    
    sendErrorResponse('Database error', 'Database error: ' . $e->getMessage(), 500);
    
} catch (Exception $e) {
    logError('Unexpected error in get_vital_signs', [
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
        'private_key' => $private_key ?? 'Not provided',
        'trace' => $e->getTraceAsString()
    ]);
    
    sendErrorResponse('Server error', 'Server error: ' . $e->getMessage(), 500);
}
?> 