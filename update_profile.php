<?php
require_once 'config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow PATCH method
if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    sendErrorResponse('Method not allowed', 'Only PATCH method is allowed', 405);
}

try {
    // Get Authorization header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
        sendErrorResponse('Authorization required', 'Missing or invalid authorization token', 401);
    }
    
    $token = substr($authHeader, 7); // Remove 'Bearer ' prefix
    
    // Extract user ID from token (format: {userId}_{randomToken})
    $tokenParts = explode('_', $token, 2);
    if (count($tokenParts) !== 2) {
        sendErrorResponse('Invalid token format', 'Token format is invalid', 401);
    }
    
    $userId = (int)$tokenParts[0];
    if ($userId <= 0) {
        sendErrorResponse('Invalid token', 'Token contains invalid user ID', 401);
    }
    
    // Get and validate input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!is_array($input) || empty($input)) {
        sendErrorResponse('Invalid input', 'Request body must contain valid JSON data', 400);
    }
    
    $pdo = getDatabaseConnection();
    
    // Check if user exists and is active
    $stmt = $pdo->prepare("SELECT id, fullName, email, phone, age, address, gender, active FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentUser) {
        sendErrorResponse('User not found', 'User does not exist', 404);
    }
    
    if (!$currentUser['active']) {
        sendErrorResponse('Account inactive', 'Your account has been deactivated', 403);
    }
    
    // Validate and prepare update fields
    $updateFields = [];
    $updateValues = [];
    
    // Full name validation
    if (isset($input['fullName'])) {
        $fullName = trim($input['fullName']);
        if (empty($fullName)) {
            sendErrorResponse('Invalid full name', 'Full name cannot be empty', 400);
        }
        if (strlen($fullName) > 100) {
            sendErrorResponse('Invalid full name', 'Full name cannot exceed 100 characters', 400);
        }
        $updateFields[] = 'fullName = ?';
        $updateValues[] = $fullName;
    }
    
    // Phone validation
    if (isset($input['phone'])) {
        $phone = trim($input['phone']);
        if (!empty($phone)) {
            // Vietnamese phone number validation (10-11 digits, starts with 0)
            if (!preg_match('/^0[0-9]{9,10}$/', $phone)) {
                sendErrorResponse('Invalid phone number', 'Phone number must be a valid Vietnamese number (10-11 digits starting with 0)', 400);
            }
            
            // Check if phone number is already taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
            $stmt->execute([$phone, $userId]);
            if ($stmt->fetch()) {
                sendErrorResponse('Phone number taken', 'This phone number is already registered to another account', 409);
            }
        }
        $updateFields[] = 'phone = ?';
        $updateValues[] = $phone;
    }
    
    // Age validation
    if (isset($input['age'])) {
        $age = $input['age'];
        if ($age !== null && $age !== '') {
            if (!is_numeric($age) || $age < 1 || $age > 150) {
                sendErrorResponse('Invalid age', 'Age must be between 1 and 150', 400);
            }
            $age = (int) $age;
        } else {
            $age = null;
        }
        $updateFields[] = 'age = ?';
        $updateValues[] = $age;
    }
    
    // Address validation
    if (isset($input['address'])) {
        $address = trim($input['address']);
        if (strlen($address) > 255) {
            sendErrorResponse('Invalid address', 'Address cannot exceed 255 characters', 400);
        }
        $updateFields[] = 'address = ?';
        $updateValues[] = $address ?: null;
    }
    
    // Gender validation
    if (isset($input['gender'])) {
        $gender = trim($input['gender']);
        if (!empty($gender)) {
            // Convert Vietnamese to English and validate
            $genderMap = [
                'nam' => 'male',
                'nữ' => 'female', 
                'nu' => 'female',
                'khác' => 'other',
                'khac' => 'other',
                'male' => 'male',
                'female' => 'female',
                'other' => 'other'
            ];
            
            $genderLower = strtolower($gender);
            if (!array_key_exists($genderLower, $genderMap)) {
                sendErrorResponse('Invalid gender', 'Gender must be one of: Nam, Nữ, Khác, male, female, other', 400);
            }
            
            $gender = $genderMap[$genderLower];
        } else {
            $gender = null;
        }
        $updateFields[] = 'gender = ?';
        $updateValues[] = $gender;
    }
    
    // Blood type validation
    if (isset($input['bloodType'])) {
        $bloodType = trim($input['bloodType']);
        if (!empty($bloodType)) {
            // Valid blood types
            $validBloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'A', 'B', 'AB', 'O'];
            if (!in_array(strtoupper($bloodType), $validBloodTypes)) {
                sendErrorResponse('Invalid blood type', 'Blood type must be one of: A+, A-, B+, B-, AB+, AB-, O+, O-, A, B, AB, O', 400);
            }
            $bloodType = strtoupper($bloodType);
        } else {
            $bloodType = null;
        }
        $updateFields[] = 'bloodType = ?';
        $updateValues[] = $bloodType;
    }
    
    // Allergies validation
    if (isset($input['allergies'])) {
        $allergies = trim($input['allergies']);
        if (strlen($allergies) > 1000) {
            sendErrorResponse('Invalid allergies', 'Allergies information cannot exceed 1000 characters', 400);
        }
        $updateFields[] = 'allergies = ?';
        $updateValues[] = $allergies ?: null;
    }
    
    // Medical conditions validation
    if (isset($input['medicalConditions'])) {
        $medicalConditions = trim($input['medicalConditions']);
        if (strlen($medicalConditions) > 1000) {
            sendErrorResponse('Invalid medical conditions', 'Medical conditions information cannot exceed 1000 characters', 400);
        }
        $updateFields[] = 'medicalConditions = ?';
        $updateValues[] = $medicalConditions ?: null;
    }
    
    // Medications validation
    if (isset($input['medications'])) {
        $medications = trim($input['medications']);
        if (strlen($medications) > 1000) {
            sendErrorResponse('Invalid medications', 'Medications information cannot exceed 1000 characters', 400);
        }
        $updateFields[] = 'medications = ?';
        $updateValues[] = $medications ?: null;
    }
    
    // Emergency contact name validation
    if (isset($input['emergencyContactName'])) {
        $emergencyContactName = trim($input['emergencyContactName']);
        if (strlen($emergencyContactName) > 100) {
            sendErrorResponse('Invalid emergency contact name', 'Emergency contact name cannot exceed 100 characters', 400);
        }
        $updateFields[] = 'emergencyContactName = ?';
        $updateValues[] = $emergencyContactName ?: null;
    }
    
    // Emergency contact phone validation
    if (isset($input['emergencyContactPhone'])) {
        $emergencyContactPhone = trim($input['emergencyContactPhone']);
        if (!empty($emergencyContactPhone)) {
            // Vietnamese phone number validation (10-11 digits, starts with 0)
            if (!preg_match('/^0[0-9]{9,10}$/', $emergencyContactPhone)) {
                sendErrorResponse('Invalid emergency contact phone', 'Emergency contact phone must be a valid Vietnamese number (10-11 digits starting with 0)', 400);
            }
        }
        $updateFields[] = 'emergencyContactPhone = ?';
        $updateValues[] = $emergencyContactPhone ?: null;
    }
    
    // Emergency contact relation validation
    if (isset($input['emergencyContactRelation'])) {
        $emergencyContactRelation = trim($input['emergencyContactRelation']);
        if (strlen($emergencyContactRelation) > 50) {
            sendErrorResponse('Invalid emergency contact relation', 'Emergency contact relation cannot exceed 50 characters', 400);
        }
        $updateFields[] = 'emergencyContactRelation = ?';
        $updateValues[] = $emergencyContactRelation ?: null;
    }
    
    // Check if there are any fields to update
    if (empty($updateFields)) {
        sendErrorResponse('No data to update', 'No valid fields provided for update', 400);
    }
    
    // Add updated_at field
    $updateFields[] = 'updated_at = NOW()';
    
    // Build and execute update query
    $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $updateValues[] = $userId; // Add user ID for WHERE clause
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($updateValues);
    
    if (!$result) {
        sendErrorResponse('Update failed', 'Failed to update user profile', 500);
    }
    
    // Get updated user data
    $stmt = $pdo->prepare("
        SELECT id, fullName, email, phone, age, address, gender, role, active, 
               isPremium, premiumEndDate, premiumTrialUsed, created_at, updated_at,
               bloodType, allergies, medicalConditions, medications, 
               emergencyContactName, emergencyContactPhone, emergencyContactRelation
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$updatedUser) {
        sendErrorResponse('User not found after update', 'Failed to retrieve updated user data', 500);
    }
    
    // Format response data
    $updatedUser['createdAt'] = $updatedUser['created_at'];
    $updatedUser['updatedAt'] = $updatedUser['updated_at'];
    unset($updatedUser['created_at'], $updatedUser['updated_at']);
    
    // Convert gender back to Vietnamese for frontend
    if ($updatedUser['gender']) {
        $genderDisplayMap = [
            'male' => 'Nam',
            'female' => 'Nữ', 
            'other' => 'Khác'
        ];
        $updatedUser['gender'] = $genderDisplayMap[$updatedUser['gender']] ?? $updatedUser['gender'];
    }
    
    // Success response
    sendSuccessResponse($updatedUser, 'Profile updated successfully');
    
} catch (PDOException $e) {
    error_log("Database Error in update_profile.php: " . $e->getMessage());
    sendErrorResponse('Database error', 'An error occurred while updating profile', 500);
    
} catch (Exception $e) {
    error_log("Error in update_profile.php: " . $e->getMessage());
    sendErrorResponse('Server error', $e->getMessage(), 500);
}
?>
