<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test the family validation logic
    $elderlyPrivateKey = $_GET['elderly_private_key'] ?? 'test_key';
    $excludeRelativeKey = $_GET['exclude_relative_key'] ?? 'relative_key_1';
    
    echo "Testing family validation for elderly: $elderlyPrivateKey\n";
    echo "Excluding relative: $excludeRelativeKey\n\n";
    
    // Check if this elderly user is already in another premium subscription
    $checkOtherPremiumStmt = $pdo->prepare("
        SELECT ps.premium_key, ps.young_person_key, u.userName as relative_name 
        FROM premium_subscriptions_json ps 
        JOIN user u ON u.private_key = ps.young_person_key 
        WHERE JSON_CONTAINS(ps.elderly_keys, ?) 
        AND ps.young_person_key != ?
    ");
    $checkOtherPremiumStmt->execute([json_encode($elderlyPrivateKey), $excludeRelativeKey]);
    $existingPremium = $checkOtherPremiumStmt->fetch();
    
    if ($existingPremium) {
        echo "❌ Elderly user is already in another family!\n";
        echo "Family managed by: " . $existingPremium['relative_name'] . "\n";
        echo "Premium key: " . $existingPremium['premium_key'] . "\n";
    } else {
        echo "✅ Elderly user is not in any other family - can be added!\n";
    }
    
    // Show all premium subscriptions for reference
    echo "\nAll premium subscriptions:\n";
    $allPremiumStmt = $pdo->prepare("SELECT premium_key, young_person_key, elderly_keys FROM premium_subscriptions_json");
    $allPremiumStmt->execute();
    $allPremium = $allPremiumStmt->fetchAll();
    
    foreach ($allPremium as $premium) {
        echo "Premium Key: " . $premium['premium_key'] . "\n";
        echo "Young Person Key: " . $premium['young_person_key'] . "\n";
        echo "Elderly Keys: " . $premium['elderly_keys'] . "\n";
        echo "---\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
} catch (Exception $e) {
    echo "Server error: " . $e->getMessage();
}
?> 