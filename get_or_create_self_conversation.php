<?php
require_once 'config.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  sendErrorResponse('Method not allowed', 'Method not allowed', 405);
  exit;
}

try {
  $conn = getDatabaseConnection();

  $input = file_get_contents('php://input');
  $data = json_decode($input, true);
  if (json_last_error() !== JSON_ERROR_NONE) {
    sendErrorResponse('Invalid JSON format', 'Bad request', 400);
    exit;
  }

  $userPhone = isset($data['user_phone']) ? sanitizeInput($data['user_phone']) : null;
  $userEmail = isset($data['user_email']) ? sanitizeInput($data['user_email']) : null;

  if (!$userPhone) {
    sendErrorResponse('user_phone is required', 'Bad request', 400);
    exit;
  }

  // Optional: verify that the phone belongs to the provided email
  if ($userEmail) {
    $verifySql = "SELECT phone FROM user WHERE email = ? LIMIT 1";
    $verifyStmt = $conn->prepare($verifySql);
    $verifyStmt->execute([$userEmail]);
    $row = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || $row['phone'] !== $userPhone) {
      sendErrorResponse('User verification failed', 'Forbidden', 403);
      exit;
    }
  }

  // Try to find existing self conversation
  $selectSql = "SELECT id FROM conversations WHERE participant1_phone = ? AND participant2_phone = ? LIMIT 1";
  $stmt = $conn->prepare($selectSql);
  $stmt->execute([$userPhone, $userPhone]);
  $conv = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($conv) {
    sendSuccessResponse(['conversation_id' => $conv['id']], 'Self conversation exists');
    exit;
  }

  // Create a new self conversation
  $conversationId = 'conv_' . md5($userPhone . $userPhone);
  $insertSql = "INSERT INTO conversations (id, participant1_phone, participant2_phone, created_at, last_activity) VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
  $insertStmt = $conn->prepare($insertSql);
  $insertStmt->execute([$conversationId, $userPhone, $userPhone]);

  sendSuccessResponse(['conversation_id' => $conversationId], 'Self conversation created');

} catch (Exception $e) {
  sendErrorResponse('Server error: ' . $e->getMessage(), 'Internal server error', 500);
} 