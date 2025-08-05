<?php
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Method not allowed', 405);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log everything for debugging
error_log('=== REGISTER DEBUG ===');
error_log('Raw input: ' . $input);
error_log('Parsed data: ' . json_encode($data));
error_log('privateKey isset: ' . (isset($data['privateKey']) ? 'YES' : 'NO'));
error_log('privateKey value: ' . (isset($data['privateKey']) ? $data['privateKey'] : 'NOT SET'));
error_log('All keys: ' . json_encode(array_keys($data)));

// Return debug info
sendSuccessResponse([
    'received_keys' => array_keys($data),
    'privateKey_isset' => isset($data['privateKey']),
    'privateKey_value' => isset($data['privateKey']) ? $data['privateKey'] : null,
    'privateKey_after_sanitize' => isset($data['privateKey']) ? sanitizeInput($data['privateKey']) : null
], 'Debug info');
?>
