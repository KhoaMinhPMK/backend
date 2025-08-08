<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    $relativeUserId = $input['relative_user_id'] ?? null;
    $elderlyPrivateKey = $input['elderly_private_key'] ?? null;
    
    if (!$relativeUserId || !$elderlyPrivateKey) {
        echo json_encode(['success' => false, 'message' => 'Relative user ID and elderly private key are required']);
        exit;
    }
    
    // First, verify that the relative user exists and has premium status
    $relativeStmt = $pdo->prepare("SELECT userId, private_key, role, premium_status FROM user WHERE userId = ? AND role = 'relative' AND premium_status = 1");
    $relativeStmt->execute([$relativeUserId]);
    $relative = $relativeStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$relative) {
        echo json_encode(['success' => false, 'message' => 'Relative user not found or does not have premium status']);
        exit;
    }
    
    // Find the premium subscription for this relative
    $premiumStmt = $pdo->prepare("SELECT premium_key, elderly_keys FROM premium_subscriptions_json WHERE young_person_key = ?");
    $premiumStmt->execute([$relative['private_key']]);
    $premium = $premiumStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$premium) {
        echo json_encode(['success' => false, 'message' => 'Premium subscription not found for this user']);
        exit;
    }
    
    // Parse elderly_keys array
    $elderlyKeys = json_decode($premium['elderly_keys'], true);
    if (!is_array($elderlyKeys)) {
        $elderlyKeys = [];
    }
    
    // Check if the elderly user is in the subscription
    if (!in_array($elderlyPrivateKey, $elderlyKeys)) {
        echo json_encode(['success' => false, 'message' => 'Elderly user is not in this premium subscription']);
        exit;
    }
    
    // Get elderly user information before removal
    $elderlyStmt = $pdo->prepare("SELECT userName FROM user WHERE private_key = ? AND role = 'elderly'");
    $elderlyStmt->execute([$elderlyPrivateKey]);
    $elderly = $elderlyStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$elderly) {
        echo json_encode(['success' => false, 'message' => 'Elderly user not found']);
        exit;
    }
    
    // Remove the elderly private key from the array
    $updatedElderlyKeys = array_values(array_filter($elderlyKeys, function($key) use ($elderlyPrivateKey) {
        return $key !== $elderlyPrivateKey;
    }));
    
    // Update the premium subscription
    $updateStmt = $pdo->prepare("UPDATE premium_subscriptions_json SET elderly_keys = ? WHERE premium_key = ?");
    $result = $updateStmt->execute([json_encode($updatedElderlyKeys), $premium['premium_key']]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Elderly user removed from premium subscription successfully',
            'data' => [
                'removed_elderly' => $elderly['userName'],
                'elderly_count' => count($updatedElderlyKeys),
                'premium_key' => $premium['premium_key']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove elderly user from premium subscription']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?> 