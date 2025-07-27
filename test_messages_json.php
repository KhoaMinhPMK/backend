<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'success' => true,
    'data' => [
        'messages' => [
            [
                'id' => '1',
                'conversationId' => 'conv_1fd7e09c6c647f98a9aaabed96b60327',
                'senderPhone' => '0000000001',
                'receiverPhone' => '0000000003',
                'messageText' => 'Test message from API',
                'timestamp' => '2025-07-27T12:00:00.000Z',
                'isRead' => false
            ]
        ],
        'conversation' => [
            'conversationId' => 'conv_1fd7e09c6c647f98a9aaabed96b60327',
            'participants' => ['0000000001', '0000000003'],
            'lastMessage' => 'Test message from API',
            'lastMessageAt' => '2025-07-27T12:00:00.000Z'
        ],
        'total' => 1
    ],
    'message' => 'Test response successful'
]);
?> 