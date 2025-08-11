<?php
require_once 'config.php';

echo "🔍 Firebase Setup Diagnostic\n";
echo "============================\n\n";

// Check 1: Service account file
echo "1️⃣ Checking Firebase service account file...\n";
$serviceAccountFile = __DIR__ . '/firebase-service-account.json';

if (file_exists($serviceAccountFile)) {
    echo "✅ Service account file exists\n";
    
    $fileSize = filesize($serviceAccountFile);
    echo "   File size: " . number_format($fileSize) . " bytes\n";
    
    $fileContent = file_get_contents($serviceAccountFile);
    $serviceAccount = json_decode($fileContent, true);
    
    if ($serviceAccount) {
        echo "✅ JSON is valid\n";
        echo "   Project ID: " . ($serviceAccount['project_id'] ?? 'NOT FOUND') . "\n";
        echo "   Client Email: " . ($serviceAccount['client_email'] ?? 'NOT FOUND') . "\n";
        echo "   Private Key: " . (isset($serviceAccount['private_key']) ? 'PRESENT' : 'MISSING') . "\n";
    } else {
        echo "❌ Invalid JSON in service account file\n";
        echo "   JSON Error: " . json_last_error_msg() . "\n";
    }
} else {
    echo "❌ Service account file not found: $serviceAccountFile\n";
    echo "🔧 Please download the service account key from Firebase Console\n";
}

echo "\n";

// Check 2: Project ID configuration
echo "2️⃣ Checking project ID configuration...\n";
$projectId = 'viegrand-487bd'; // Your actual Firebase project ID
echo "   Configured Project ID: $projectId\n";

// Check 3: Test OAuth2 token generation
echo "\n3️⃣ Testing OAuth2 token generation...\n";

if (file_exists($serviceAccountFile)) {
    $serviceAccount = json_decode(file_get_contents($serviceAccountFile), true);
    
    if ($serviceAccount && isset($serviceAccount['private_key'])) {
        echo "   Creating JWT token...\n";
        
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
        $signResult = openssl_sign($base64Header . "." . $base64Payload, $signature, $serviceAccount['private_key'], 'SHA256');
        
        if ($signResult) {
            echo "   ✅ JWT signature created successfully\n";
            
            $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
            $jwt = $base64Header . "." . $base64Payload . "." . $base64Signature;
            
            echo "   Exchanging JWT for access token...\n";
            
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
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                echo "   ❌ cURL Error: $curlError\n";
            } else {
                echo "   HTTP Code: $httpCode\n";
                
                if ($httpCode === 200) {
                    $tokenData = json_decode($response, true);
                    if (isset($tokenData['access_token'])) {
                        echo "   ✅ Access token obtained successfully!\n";
                        echo "   Token type: " . ($tokenData['token_type'] ?? 'unknown') . "\n";
                        echo "   Expires in: " . ($tokenData['expires_in'] ?? 'unknown') . " seconds\n";
                    } else {
                        echo "   ❌ No access token in response\n";
                        echo "   Response: " . substr($response, 0, 200) . "...\n";
                    }
                } else {
                    echo "   ❌ Failed to get access token\n";
                    echo "   Response: " . substr($response, 0, 200) . "...\n";
                }
            }
        } else {
            echo "   ❌ Failed to create JWT signature\n";
            echo "   OpenSSL Error: " . openssl_error_string() . "\n";
        }
    } else {
        echo "   ❌ Invalid service account data or missing private key\n";
    }
} else {
    echo "   ❌ Service account file not found\n";
}

echo "\n";

// Check 4: Test FCM endpoint
echo "4️⃣ Testing FCM endpoint...\n";
$fcmUrl = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";
echo "   FCM URL: $fcmUrl\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $fcmUrl);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 401) {
    echo "   ✅ FCM endpoint is accessible (401 expected without auth)\n";
} else {
    echo "   ⚠️ FCM endpoint returned HTTP $httpCode\n";
}

echo "\n";

// Check 5: Database connection
echo "5️⃣ Checking database connection...\n";
try {
    $conn = getDatabaseConnection();
    echo "   ✅ Database connection successful\n";
    
    // Check if we have any users with device tokens
    $tokenCountSql = "SELECT COUNT(*) as count FROM user WHERE device_token IS NOT NULL";
    $tokenCountStmt = $conn->prepare($tokenCountSql);
    $tokenCountStmt->execute();
    $tokenCount = $tokenCountStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "   Users with device tokens: $tokenCount\n";
    
} catch (Exception $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n";
}

echo "\n🎯 Summary:\n";
echo "If you see ❌ errors above, please fix them before testing push notifications.\n";
echo "The most common issues are:\n";
echo "1. Missing or invalid service account file\n";
echo "2. Wrong project ID\n";
echo "3. Network connectivity issues\n";
echo "4. Invalid private key format\n";
?> 