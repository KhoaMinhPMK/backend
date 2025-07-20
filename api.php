<?php
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Parse the URL to determine the endpoint
$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Remove query parameters and base path
$path = parse_url($requestUri, PHP_URL_PATH);

// Define routes
if (preg_match('/\/users\/me$/', $path)) {
    if ($method === 'PATCH') {
        include 'update_profile.php';
    } elseif ($method === 'GET') {
        include 'get_profile.php';
    } else {
        sendErrorResponse('Method not allowed', 'Method not allowed for this endpoint', 405);
    }
} elseif (preg_match('/\/users\/login$/', $path)) {
    if ($method === 'POST') {
        include 'login.php';
    } else {
        sendErrorResponse('Method not allowed', 'Only POST method allowed for login', 405);
    }
} elseif (preg_match('/\/users\/register$/', $path)) {
    if ($method === 'POST') {
        include 'signup.php';
    } else {
        sendErrorResponse('Method not allowed', 'Only POST method allowed for register', 405);
    }
} elseif (preg_match('/\/premium/', $path)) {
    include 'premium.php';
} elseif (preg_match('/\/settings/', $path)) {
    include 'settings.php';
} else {
    sendErrorResponse('Endpoint not found', 'The requested endpoint was not found', 404);
}
?>
