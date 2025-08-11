<?php
require_once 'config.php';

// Firebase Cloud Messaging configuration for HTTP v1 API
define('FCM_PROJECT_ID', 'viegrand-487bd'); // Your actual Firebase project ID
define('FCM_SERVICE_ACCOUNT_FILE', __DIR__ . '/firebase-service-account.json');
define('FCM_URL', 'https://fcm.googleapis.com/v1/projects/' . FCM_PROJECT_ID . '/messages:send');

/**
 * Get OAuth2 access token for Firebase
 */
function getFirebaseAccessToken() {
    if (!file_exists(FCM_SERVICE_ACCOUNT_FILE)) {
        error_log("❌ Firebase service account file not found: " . FCM_SERVICE_ACCOUNT_FILE);
        return false;
    }
    
    $serviceAccount = json_decode(file_get_contents(FCM_SERVICE_ACCOUNT_FILE), true);
    
    // Create JWT token
    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $payload = json_encode([
        'iss' => $serviceAccount['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => time() + 3600,
        'iat' => time()
    ]);
    
    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = '';
    openssl_sign($base64Header . "." . $base64Payload, $signature, $serviceAccount['private_key'], 'SHA256');
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    $jwt = $base64Header . "." . $base64Payload . "." . $base64Signature;
    
    // Exchange JWT for access token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $tokenData = json_decode($response, true);
        return $tokenData['access_token'];
    }
    
    error_log("❌ Failed to get access token: $response");
    return false;
}

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
        
        // Get access token
        $accessToken = getFirebaseAccessToken();
        if (!$accessToken) {
            return [
                'success' => false,
                'message' => 'Failed to get Firebase access token'
            ];
        }
        
        // Convert all data values to strings (FCM requirement)
        // FCM has restrictions on data payload keys - no underscores allowed
        $stringData = [];
        foreach (array_merge($data, [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'sound' => 'default',
            'status' => 'done',
            'screen' => 'message'
        ]) as $key => $value) {
            // Convert underscores to dots for FCM compatibility
            $fcmKey = str_replace('_', '.', $key);
            $stringData[$fcmKey] = (string)$value;
        }
        
        // Prepare FCM HTTP v1 API payload
        $fcmPayload = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'data' => $stringData,
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                        'channel_id' => 'viegrand_messages'
                    ]
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1
                        ]
                    ]
                ]
            ]
        ];
        
        // Send to FCM HTTP v1 API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, FCM_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
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
        
        if ($httpCode === 200 && isset($responseData['name'])) {
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
// Only run this if the file is accessed directly, not when included
if (basename($_SERVER['SCRIPT_NAME']) === 'send_push_notification.php' && $_SERVER['REQUEST_METHOD'] === 'POST') {
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