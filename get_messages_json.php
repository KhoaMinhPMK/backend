<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

try {
    // Đọc JSON file từ server
    $serverUrl = 'http://localhost:3000/debug/messages';
    $response = file_get_contents($serverUrl);
    
    if ($response === false) {
        throw new Exception('Không thể kết nối đến server');
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['messages'])) {
        throw new Exception('Dữ liệu không hợp lệ từ server');
    }
    
    $messages = $data['messages'];
    
    // Lọc theo conversation_id nếu có
    if (isset($_GET['conversation_id'])) {
        $conversationId = $_GET['conversation_id'];
        $messages = array_filter($messages, function($msg) use ($conversationId) {
            return $msg['conversationId'] === $conversationId;
        });
        $messages = array_values($messages); // Reset array keys
    }
    
    // Sắp xếp theo timestamp
    usort($messages, function($a, $b) {
        return strtotime($a['timestamp']) - strtotime($b['timestamp']);
    });
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'count' => count($messages)
    ]);
    
} catch (Exception $e) {
    error_log("get_messages_json error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 