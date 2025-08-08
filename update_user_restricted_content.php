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
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get data from request
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['userId'] ?? null;
    $restrictedContents = $input['restricted_contents'] ?? [];
    
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID is required']);
        exit;
    }
    
    // Validate restricted_contents is an array
    if (!is_array($restrictedContents)) {
        http_response_code(400);
        echo json_encode(['error' => 'restricted_contents must be an array']);
        exit;
    }
    
    // First, check if the restricted_contents column exists
    $checkColumnStmt = $pdo->prepare("SHOW COLUMNS FROM user LIKE 'restricted_contents'");
    $checkColumnStmt->execute();
    $columnExists = $checkColumnStmt->fetch();
    
    if (!$columnExists) {
        // Column doesn't exist, create it first
        try {
            $createColumnStmt = $pdo->prepare("ALTER TABLE user ADD COLUMN restricted_contents json DEFAULT NULL COMMENT 'Array of keywords that this elderly user should not watch'");
            $createColumnStmt->execute();
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create restricted_contents column: ' . $e->getMessage()]);
            exit;
        }
    }
    
    // Update restricted content for the user
    $stmt = $pdo->prepare("UPDATE user SET restricted_contents = ? WHERE userId = ?");
    $result = $stmt->execute([json_encode($restrictedContents), $userId]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Restricted content updated successfully',
            'restricted_contents' => $restrictedContents
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'User not found or no changes made']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 