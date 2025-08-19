<?php
// Disable all error output to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
    
    $userId = $_GET['user_id'] ?? null;
    
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'Missing user_id parameter']);
        exit;
    }
    
    // First, verify that the user exists and has premium status
    $userStmt = $pdo->prepare("SELECT userId, private_key, role, premium_status FROM user WHERE userId = ? AND role = 'relative' AND premium_status = 1");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found or does not have premium status']);
        exit;
    }
    
    // Find the premium subscription for this user
    $premiumStmt = $pdo->prepare("SELECT premium_key, elderly_keys FROM premium_subscriptions_json WHERE young_person_key = ?");
    $premiumStmt->execute([$user['private_key']]);
    $premium = $premiumStmt->fetch();
    
    if (!$premium) {
        echo json_encode(['success' => false, 'message' => 'Premium subscription not found for this user']);
        exit;
    }
    
    // Parse elderly_keys array
    $elderlyKeys = json_decode($premium['elderly_keys'], true);
    if (!is_array($elderlyKeys)) {
        $elderlyKeys = [];
    }
    
    if (empty($elderlyKeys)) {
        echo json_encode([
            'success' => true,
            'message' => 'No elderly users in this premium subscription',
            'data' => []
        ]);
        exit;
    }
    
    // Get details for each elderly user with their cameras
    $elderlyUsers = [];
    foreach ($elderlyKeys as $key) {
        $elderlyStmt = $pdo->prepare("SELECT userId, userName, email, phone, age, gender FROM user WHERE private_key = ? AND role = 'elderly'");
        $elderlyStmt->execute([$key]);
        $elderly = $elderlyStmt->fetch();
        
        if ($elderly) {
            // Get camera data for this elderly user
            $cameraStmt = $pdo->prepare("SELECT camera_links FROM users_with_cameras WHERE private_key = ?");
            $cameraStmt->execute([$key]);
            $cameraData = $cameraStmt->fetch();
            
            $cameraLinks = [];
            if ($cameraData && $cameraData['camera_links']) {
                $cameraLinks = json_decode($cameraData['camera_links'], true);
                if (!is_array($cameraLinks)) {
                    $cameraLinks = [];
                }
            }
            
            $elderlyUsers[] = [
                'userId' => (int)$elderly['userId'],
                'userName' => $elderly['userName'],
                'email' => $elderly['email'],
                'phone' => $elderly['phone'],
                'age' => (int)$elderly['age'],
                'gender' => $elderly['gender'],
                'private_key' => $key,
                'cameras' => $cameraLinks
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Elderly users with cameras retrieved successfully',
        'data' => $elderlyUsers
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?> 