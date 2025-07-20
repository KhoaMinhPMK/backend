<?php
header('Content-Type: application/json');

// Simple test without database
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

$response = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'input' => $input,
    'raw_input' => file_get_contents('php://input'),
    'post_data' => $_POST,
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
    'time' => date('Y-m-d H:i:s')
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>
