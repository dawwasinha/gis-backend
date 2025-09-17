<?php

require_once 'vendor/autoload.php';

use Illuminate\Http\Request;
use App\Http\Controllers\ExamController;
use App\Http\Requests\ExamSubmitRequest;

// Simulate Laravel environment
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test 1: Valid request
echo "=== Test 1: Valid Request ===\n";
$controller = new ExamController();

// Simulate request with valid data
$request = new ExamSubmitRequest();
$request->merge([
    'userId' => '8',
    'durationInMinutes' => 45,
    'totalViolations' => 2,
    'isAutoSubmit' => false
]);

try {
    $response = $controller->submit($request);
    echo "Response Status: " . $response->status() . "\n";
    echo "Response Body: " . $response->getContent() . "\n\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// Test 2: Invalid user ID
echo "=== Test 2: Invalid User ID ===\n";
$request2 = new ExamSubmitRequest();
$request2->merge([
    'userId' => '99999',
    'durationInMinutes' => 45,
    'totalViolations' => 2,
    'isAutoSubmit' => false
]);

try {
    $response2 = $controller->submit($request2);
    echo "Response Status: " . $response2->status() . "\n";
    echo "Response Body: " . $response2->getContent() . "\n\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Missing required fields
echo "=== Test 3: Missing Required Fields ===\n";
$request3 = new ExamSubmitRequest();
$request3->merge([
    'userId' => '8',
    // Missing other fields
]);

try {
    $response3 = $controller->submit($request3);
    echo "Response Status: " . $response3->status() . "\n";
    echo "Response Body: " . $response3->getContent() . "\n\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

echo "Testing completed!\n";
