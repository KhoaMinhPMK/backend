<?php
require_once 'config.php';

// Test login với user khác nhau
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Login Test - Premium Status</h2>";

// Test users
$testUsers = [
    ['email' => 'admin@viegrand.site', 'password' => 'admin123'],
    // Add more test users if you have them
];

foreach ($testUsers as $credentials) {
    echo "<h3>Testing login: {$credentials['email']}</h3>";
    
    // Simulate POST request
    $postData = json_encode($credentials);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://viegrand.site/backend/login.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($postData)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p>HTTP Code: $httpCode</p>";
    
    if ($response) {
        $data = json_decode($response, true);
        if ($data && isset($data['data']['user']['premium'])) {
            $premium = $data['data']['user']['premium'];
            echo "<div style='background: " . ($premium['isPremium'] ? '#ccffcc' : '#ffcccc') . "; padding: 10px; border: 1px solid #ccc;'>";
            echo "<h4>Premium Status:</h4>";
            echo "<ul>";
            echo "<li>isPremium: " . ($premium['isPremium'] ? 'TRUE' : 'FALSE') . "</li>";
            echo "<li>daysRemaining: " . ($premium['daysRemaining'] ?? 'N/A') . "</li>";
            echo "<li>premiumStartDate: " . ($premium['premiumStartDate'] ?? 'N/A') . "</li>";
            echo "<li>premiumEndDate: " . ($premium['premiumEndDate'] ?? 'N/A') . "</li>";
            echo "<li>Plan: " . (isset($premium['plan']['name']) ? $premium['plan']['name'] : 'None') . "</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<p>No premium data in response</p>";
        }
        
        echo "<details><summary>Full Response</summary>";
        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        echo "</details>";
    } else {
        echo "<p>No response</p>";
    }
    
    echo "<hr>";
}
?>
