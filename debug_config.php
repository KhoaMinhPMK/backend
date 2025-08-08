<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'success' => true,
    'message' => 'Debug file loaded',
    'config_file_exists' => file_exists('config.php'),
    'config_file_path' => realpath('config.php'),
    'current_dir' => getcwd()
]);

if (file_exists('config.php')) {
    require_once 'config.php';
    
    echo json_encode([
        'success' => true,
        'message' => 'Config loaded',
        'DB_HOST' => defined('DB_HOST') ? DB_HOST : 'NOT_DEFINED',
        'DB_NAME' => defined('DB_NAME') ? DB_NAME : 'NOT_DEFINED',
        'DB_USER' => defined('DB_USER') ? DB_USER : 'NOT_DEFINED',
        'DB_PASS' => defined('DB_PASS') ? (strlen(DB_PASS) > 0 ? 'SET' : 'EMPTY') : 'NOT_DEFINED'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Config file not found'
    ]);
}
?> 