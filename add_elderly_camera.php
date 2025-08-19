<?php
// Disable all error output to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Database configuration
$host = '127.0.0.1';
$dbname = 'viegrand';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    $elderlyPrivateKey = $input['elderly_private_key'] ?? null;
    $cameraUrl = $input['camera_url'] ?? null;
    $room = $input['room'] ?? null;
    $relativeUserId = $input['relative_user_id'] ?? null;
    
    if (!$elderlyPrivateKey || !$cameraUrl || !$room || !$relativeUserId) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }
    
    // Validate URL format
    if (!filter_var($cameraUrl, FILTER_VALIDATE_URL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid camera URL format']);
        exit;
    }
    
    // First, verify that the relative user exists and has premium status
    $userStmt = $pdo->prepare("SELECT userId, private_key, role, premium_status FROM user WHERE userId = ? AND role = 'relative' AND premium_status = 1");
    $userStmt->execute([$relativeUserId]);
    $user = $userStmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found or does not have premium status']);
        exit;
    }
    
    // Verify that the elderly user exists and is in the same premium family
    $elderlyStmt = $pdo->prepare("SELECT userId, userName FROM user WHERE private_key = ? AND role = 'elderly'");
    $elderlyStmt->execute([$elderlyPrivateKey]);
    $elderly = $elderlyStmt->fetch();
    
    if (!$elderly) {
        echo json_encode(['success' => false, 'message' => 'Elderly user not found']);
        exit;
    }
    
    // Check if elderly user is in the same premium family as the relative
    $premiumStmt = $pdo->prepare("SELECT premium_key, elderly_keys FROM premium_subscriptions_json WHERE young_person_key = ?");
    $premiumStmt->execute([$user['private_key']]);
    $premium = $premiumStmt->fetch();
    
    if (!$premium) {
        echo json_encode(['success' => false, 'message' => 'Premium subscription not found']);
        exit;
    }
    
    $elderlyKeys = json_decode($premium['elderly_keys'], true);
    if (!is_array($elderlyKeys) || !in_array($elderlyPrivateKey, $elderlyKeys)) {
        echo json_encode(['success' => false, 'message' => 'Elderly user is not in your premium family']);
        exit;
    }
    
    // Check if camera record exists for this elderly user
    $existingStmt = $pdo->prepare("SELECT camera_links, room FROM users_with_cameras WHERE private_key = ?");
    $existingStmt->execute([$elderlyPrivateKey]);
    $existing = $existingStmt->fetch();
    
    if ($existing) {
        // Update existing record
        $currentLinks = json_decode($existing['camera_links'], true);
        if (!is_array($currentLinks)) {
            $currentLinks = [];
        }
        
        // Check if URL already exists
        if (in_array($cameraUrl, $currentLinks)) {
            echo json_encode(['success' => false, 'message' => 'Camera URL already exists for this user']);
            exit;
        }
        
        // Add new URL
        $currentLinks[] = $cameraUrl;
        $newLinksJson = json_encode($currentLinks);
        
        $updateStmt = $pdo->prepare("UPDATE users_with_cameras SET camera_links = ?, room = ? WHERE private_key = ?");
        $updateStmt->execute([$newLinksJson, $room, $elderlyPrivateKey]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Camera added successfully',
            'data' => [
                'elderly_name' => $elderly['userName'],
                'camera_url' => $cameraUrl,
                'total_cameras' => count($currentLinks)
            ]
        ]);
        
    } else {
        // Create new record
        $newLinks = [$cameraUrl];
        $newLinksJson = json_encode($newLinks);
        
        $insertStmt = $pdo->prepare("INSERT INTO users_with_cameras (private_key, camera_links, room) VALUES (?, ?, ?)");
        $insertStmt->execute([$elderlyPrivateKey, $newLinksJson, $room]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Camera added successfully',
            'data' => [
                'elderly_name' => $elderly['userName'],
                'camera_url' => $cameraUrl,
                'total_cameras' => 1
            ]
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?> 