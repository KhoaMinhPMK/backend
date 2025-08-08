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
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get user ID from request
    $userId = $_GET['userId'] ?? null;
    
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID is required']);
        exit;
    }
    
    // First, check if the restricted_contents column exists
    $checkColumnStmt = $pdo->prepare("SHOW COLUMNS FROM user LIKE 'restricted_contents'");
    $checkColumnStmt->execute();
    $columnExists = $checkColumnStmt->fetch();
    
    if (!$columnExists) {
        // Column doesn't exist, return empty array
        echo json_encode([
            'success' => true,
            'restricted_contents' => [],
            'message' => 'Column not found, returning empty array'
        ]);
        exit;
    }
    
    // Get restricted content for the user
    $stmt = $pdo->prepare("SELECT restricted_contents FROM user WHERE userId = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $restrictedContents = $result['restricted_contents'] ? json_decode($result['restricted_contents'], true) : [];
        echo json_encode([
            'success' => true,
            'restricted_contents' => $restrictedContents
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 