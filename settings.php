<?php
require_once 'config.php';

// Set CORS headers
setCorsHeaders();

// Chỉ cho phép GET và PUT method
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'PUT'])) {
    sendErrorResponse('Method not allowed', 'Only GET and PUT methods are allowed', 405);
}

try {
    // Kết nối database
    $pdo = getDatabaseConnection();
    
    // Lấy dữ liệu từ request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // GET - Lấy settings
        // Tạm thời return default settings vì chưa có authentication
        $defaultSettings = [
            'id' => 1,
            'userId' => 1,
            'language' => 'vi',
            'isDarkMode' => false,
            'elderly_notificationsEnabled' => true,
            'elderly_soundEnabled' => true,
            'elderly_vibrationEnabled' => true,
            'relative_appNotificationsEnabled' => true,
            'relative_emailAlertsEnabled' => true,
            'relative_smsAlertsEnabled' => true,
            'createdAt' => date('Y-m-d H:i:s'),
            'updatedAt' => date('Y-m-d H:i:s')
        ];
        
        sendSuccessResponse($defaultSettings, 'Settings retrieved successfully');
        
    } else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // PUT - Cập nhật settings
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }
        
        // Sanitize input
        $input = sanitizeInput($input);
        
        // Validate required fields (tối thiểu phải có userId)
        if (empty($input['userId'])) {
            throw new Exception('User ID is required');
        }
        
        $userId = $input['userId'];
        
        // Kiểm tra xem settings đã tồn tại chưa
        $stmt = $pdo->prepare("SELECT id FROM user_settings WHERE userId = ?");
        $stmt->execute([$userId]);
        $existingSettings = $stmt->fetch();
        
        if ($existingSettings) {
            // Update existing settings
            $stmt = $pdo->prepare("
                UPDATE user_settings SET 
                language = ?, 
                isDarkMode = ?, 
                elderly_notificationsEnabled = ?, 
                elderly_soundEnabled = ?, 
                elderly_vibrationEnabled = ?, 
                relative_appNotificationsEnabled = ?, 
                relative_emailAlertsEnabled = ?, 
                relative_smsAlertsEnabled = ?, 
                updated_at = NOW()
                WHERE userId = ?
            ");
            
            $stmt->execute([
                $input['language'] ?? 'vi',
                $input['isDarkMode'] ?? false,
                $input['elderly_notificationsEnabled'] ?? true,
                $input['elderly_soundEnabled'] ?? true,
                $input['elderly_vibrationEnabled'] ?? true,
                $input['relative_appNotificationsEnabled'] ?? true,
                $input['relative_emailAlertsEnabled'] ?? true,
                $input['relative_smsAlertsEnabled'] ?? true,
                $userId
            ]);
        } else {
            // Insert new settings
            $stmt = $pdo->prepare("
                INSERT INTO user_settings (
                    userId, language, isDarkMode, 
                    elderly_notificationsEnabled, elderly_soundEnabled, elderly_vibrationEnabled,
                    relative_appNotificationsEnabled, relative_emailAlertsEnabled, relative_smsAlertsEnabled,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $userId,
                $input['language'] ?? 'vi',
                $input['isDarkMode'] ?? false,
                $input['elderly_notificationsEnabled'] ?? true,
                $input['elderly_soundEnabled'] ?? true,
                $input['elderly_vibrationEnabled'] ?? true,
                $input['relative_appNotificationsEnabled'] ?? true,
                $input['relative_emailAlertsEnabled'] ?? true,
                $input['relative_smsAlertsEnabled'] ?? true
            ]);
        }
        
        // Lấy settings đã cập nhật
        $stmt = $pdo->prepare("
            SELECT id, userId, language, isDarkMode, 
                   elderly_notificationsEnabled, elderly_soundEnabled, elderly_vibrationEnabled,
                   relative_appNotificationsEnabled, relative_emailAlertsEnabled, relative_smsAlertsEnabled,
                   created_at, updated_at
            FROM user_settings WHERE userId = ?
        ");
        $stmt->execute([$userId]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Convert timestamps to ISO format
        $settings['createdAt'] = $settings['created_at'];
        $settings['updatedAt'] = $settings['updated_at'];
        unset($settings['created_at'], $settings['updated_at']);
        
        sendSuccessResponse($settings, 'Settings updated successfully');
    }
    
} catch (PDOException $e) {
    // Database error
    error_log("Database Error: " . $e->getMessage());
    sendErrorResponse('Database error occurred', 'Internal server error', 500);
    
} catch (Exception $e) {
    // Validation or other error
    sendErrorResponse($e->getMessage(), 'Bad request', 400);
}
?> 