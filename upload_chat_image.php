<?php
require_once __DIR__ . '/config.php';

setCorsHeaders();

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	sendErrorResponse('Method not allowed', 'Only POST allowed', 405);
}

// Ensure file exists
if (!isset($_FILES['image'])) {
	sendErrorResponse('No file uploaded', 'Missing image field', 400);
}

$image = $_FILES['image'];

// Basic validation
if ($image['error'] !== UPLOAD_ERR_OK) {
	sendErrorResponse('Upload error', 'Error code: ' . $image['error'], 400);
}

$allowedTypes = [
	'image/jpeg' => 'jpg',
	'image/png' => 'png',
	'image/gif' => 'gif',
	'image/webp' => 'webp',
];

$mimeType = mime_content_type($image['tmp_name']);
if (!isset($allowedTypes[$mimeType])) {
	sendErrorResponse('Unsupported file type', 'MIME: ' . $mimeType, 400);
}

$ext = $allowedTypes[$mimeType];
$uploadDir = __DIR__ . '/uploads/chat_images/';
if (!is_dir($uploadDir)) {
	if (!mkdir($uploadDir, 0755, true)) {
		sendErrorResponse('Failed to create upload directory', 'Permission denied', 500);
	}
}

// Create unique filename
$filename = 'chat_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$destPath = $uploadDir . $filename;

if (!move_uploaded_file($image['tmp_name'], $destPath)) {
	sendErrorResponse('Failed to save uploaded file', 'move_uploaded_file failed', 500);
}

// Build public URL relative path
$relativePath = '/uploads/chat_images/' . $filename;

sendSuccessResponse([
	'url' => $relativePath,
	'filename' => $filename,
	'size' => filesize($destPath),
	'mimeType' => $mimeType,
], 'Image uploaded'); 