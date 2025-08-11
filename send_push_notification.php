<?php
require_once 'config.php';

// Firebase Cloud Messaging configuration
define('FCM_SERVER_KEY', 'YOUR_FCM_SERVER_KEY_HERE'); // Replace with your actual FCM server key
define('FCM_URL', 'https://fcm.googleapis.com/fcm/send');

/**
 * Send push notification to a specific user
 * 
 * @param string $userEmail - Email of the user to send notification to
 * @param string $title - Notification title
 * @param string $body - Notification body
 * @param array $data - Additional data to send with notification
 * @return array - Response with success status and message
 */
function sendPushNotification($userEmail, $title, $body, $data = []) {
    try {
        $conn = getDatabaseConnection();
        
        // Get device token for the user
        $getTokenSql = "SELECT device_token FROM user WHERE email = ?";
        $getTokenStmt = $conn->prepare($getTokenSql);
        $getTokenStmt->execute([$userEmail]);
        $userData = $getTokenStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userData || !$userData['device_token']) {
            error_log("❌ No device token found for user: $userEmail");
            return [
                'success' => false,
                'message' => 'No device token found for user'
            ];
        }
        
        $deviceToken = $userData['device_token'];
        
        // Prepare FCM payload
        $fcmPayload = [
            'to' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'badge' => 1,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ],
            'data' => array_merge($data, [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'sound' => 'default',
                'status' => 'done',
                'screen' => 'message'
            ]),
            'priority' => 'high',
            'content_available' => true
        ];
        
        // Send to FCM
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, FCM_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: key=' . FCM_SERVER_KEY,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmPayload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log("❌ cURL Error sending push notification: $curlError");
            return [
                'success' => false,
                'message' => 'cURL Error: ' . $curlError
            ];
        }
        
        $responseData = json_decode($response, true);
        
        if ($httpCode === 200 && isset($responseData['success']) && $responseData['success'] == 1) {
            error_log("✅ Push notification sent successfully to $userEmail");
            return [
                'success' => true,
                'message' => 'Push notification sent successfully',
                'fcm_response' => $responseData
            ];
        } else {
            error_log("❌ FCM Error: HTTP $httpCode - $response");
            return [
                'success' => false,
                'message' => 'FCM Error: ' . $response,
                'fcm_response' => $responseData
            ];
        }
        
    } catch (Exception $e) {
        error_log("❌ Exception sending push notification: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Exception: ' . $e->getMessage()
        ];
    }
}

/**
 * Send push notification to multiple users
 * 
 * @param array $userEmails - Array of user emails
 * @param string $title - Notification title
 * @param string $body - Notification body
 * @param array $data - Additional data to send with notification
 * @return array - Response with success status and results
 */
function sendPushNotificationToMultiple($userEmails, $title, $body, $data = []) {
    $results = [];
    $successCount = 0;
    $failureCount = 0;
    
    foreach ($userEmails as $email) {
        $result = sendPushNotification($email, $title, $body, $data);
        $results[$email] = $result;
        
        if ($result['success']) {
            $successCount++;
        } else {
            $failureCount++;
        }
    }
    
    return [
        'success' => $failureCount === 0,
        'message' => "Sent to $successCount users, failed for $failureCount users",
        'results' => $results,
        'success_count' => $successCount,
        'failure_count' => $failureCount
    ];
}

// API endpoint for testing push notifications
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    setCorsHeaders();
    
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            sendErrorResponse('Invalid JSON format', 'Bad request', 400);
            exit;
        }
        
        $userEmail = isset($data['user_email']) ? sanitizeInput($data['user_email']) : null;
        $title = isset($data['title']) ? sanitizeInput($data['title']) : null;
        $body = isset($data['body']) ? sanitizeInput($data['body']) : null;
        $notificationData = isset($data['data']) ? $data['data'] : [];
        
        if (!$userEmail || !$title || !$body) {
            sendErrorResponse('Missing required fields: user_email, title, body', 'Bad request', 400);
            exit;
        }
        
        $result = sendPushNotification($userEmail, $title, $body, $notificationData);
        
        if ($result['success']) {
            sendSuccessResponse($result, $result['message']);
        } else {
            sendErrorResponse($result['message'], 'Push notification failed', 500);
        }
        
    } catch (Exception $e) {
        error_log('❌ send_push_notification.php - Exception: ' . $e->getMessage());
        sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
    }
}
?> 