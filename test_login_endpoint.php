<?php

// Simple test script to test login endpoint
// Run this with: php test_login_endpoint.php

$baseUrl = 'http://localhost:8000/api'; // Adjust your base URL

// Test data - make sure this user exists in your database
$loginData = [
    'email' => 'user@example.com', // Change to existing user email
    'password' => 'password'       // Change to correct password
];

// Make login request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
echo "Response: \n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT);

// Test the response structure
$data = json_decode($response, true);

if ($httpCode === 200 && isset($data['success']) && $data['success']) {
    echo "\n✅ Login successful!\n";
    
    if (isset($data['pengumuman'])) {
        if ($data['has_announcement']) {
            echo "✅ User has announcement:\n";
            echo "   Status: " . $data['pengumuman']['status_lolos'] . "\n";
            echo "   Kategori: " . ($data['pengumuman']['kategori_lomba'] ?? 'N/A') . "\n";
            echo "   Ranking: " . ($data['pengumuman']['ranking'] ?? 'N/A') . "\n";
        } else {
            echo "ℹ️  User has no announcement yet\n";
        }
    } else {
        echo "❌ Pengumuman data not found in response\n";
    }
} else {
    echo "\n❌ Login failed!\n";
    if (isset($data['error'])) {
        echo "Error: " . $data['error'] . "\n";
    }
}
