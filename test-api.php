<?php
/**
 * Simple API Test Script
 * Test the CAM-GD Homes API endpoints
 */

// Configuration
$baseUrl = 'http://localhost/CAM-GD%20HOMES/api';
$testResults = [];

/**
 * Make HTTP request
 */
function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => array_merge([
            'Content-Type: application/json',
            'Accept: application/json'
        ], $headers)
    ]);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'success' => !$error && $response !== false,
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}

/**
 * Test an endpoint
 */
function testEndpoint($name, $url, $method = 'GET', $data = null, $headers = [], $expectedCode = 200) {
    global $testResults;
    
    echo "Testing: $name ($method $url)\n";
    
    $result = makeRequest($url, $method, $data, $headers);
    
    $success = $result['success'] && $result['http_code'] == $expectedCode;
    
    $testResults[] = [
        'name' => $name,
        'url' => $url,
        'method' => $method,
        'success' => $success,
        'http_code' => $result['http_code'],
        'expected_code' => $expectedCode,
        'response' => $result['response'],
        'error' => $result['error']
    ];
    
    if ($success) {
        echo " PASS - HTTP {$result['http_code']}\n";
    } else {
        echo " FAIL - HTTP {$result['http_code']} (expected $expectedCode)\n";
        if ($result['error']) {
            echo "   Error: {$result['error']}\n";
        }
    }
    
    // Show response preview
    if ($result['response']) {
        $decoded = json_decode($result['response'], true);
        if ($decoded) {
            echo "   Response: " . (isset($decoded['status']) ? $decoded['status'] : 'unknown') . "\n";
            if (isset($decoded['message'])) {
                echo "   Message: {$decoded['message']}\n";
            }
        }
    }
    
    echo "\n";
    
    return $result;
}

echo "=== CAM-GD Homes API Test Suite ===\n\n";

// Test 1: Health Check
testEndpoint(
    'Health Check',
    "$baseUrl/health",
    'GET'
);

// Test 2: API Status
testEndpoint(
    'API Status',
    "$baseUrl/status",
    'GET'
);

// Test 3: Database Test
testEndpoint(
    'Database Connection Test',
    "$baseUrl/test/database",
    'GET'
);

// Test 4: API Documentation
testEndpoint(
    'API Documentation',
    "$baseUrl/docs",
    'GET'
);

// Test 5: User Registration
$registerData = [
    'fullname' => 'Test User',
    'email' => 'test@example.com',
    'password' => 'password123',
    'role' => 'client'
];

$registerResult = testEndpoint(
    'User Registration',
    "$baseUrl/auth/register",
    'POST',
    $registerData,
    [],
    201
);

// Test 6: User Login (if registration was successful)
if ($registerResult['http_code'] == 201 || $registerResult['http_code'] == 409) { // 409 = already exists
    $loginData = [
        'email' => 'test@example.com',
        'password' => 'password123'
    ];
    
    $loginResult = testEndpoint(
        'User Login',
        "$baseUrl/auth/login",
        'POST',
        $loginData
    );
    
    // Extract token for authenticated requests
    $token = null;
    if ($loginResult['response']) {
        $loginResponse = json_decode($loginResult['response'], true);
        if (isset($loginResponse['data']['tokens']['access_token'])) {
            $token = $loginResponse['data']['tokens']['access_token'];
        }
    }
    
    // Test 7: Get Current User (authenticated)
    if ($token) {
        testEndpoint(
            'Get Current User Profile',
            "$baseUrl/users/me",
            'GET',
            null,
            ["Authorization: Bearer $token"]
        );
    }
}

// Test 8: Invalid Endpoint (should return 404)
testEndpoint(
    'Invalid Endpoint (404 Test)',
    "$baseUrl/invalid/endpoint",
    'GET',
    null,
    [],
    404
);

// Summary
echo "=== Test Summary ===\n";
$passed = 0;
$failed = 0;

foreach ($testResults as $test) {
    if ($test['success']) {
        $passed++;
        echo " {$test['name']}\n";
    } else {
        $failed++;
        echo " {$test['name']} - HTTP {$test['http_code']}\n";
    }
}

echo "\nTotal Tests: " . count($testResults) . "\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";

if ($failed == 0) {
    echo "\n All tests passed! Your API is fully functional.\n";
} else {
    echo "\n  Some tests failed. Check the details above.\n";
}

echo "\n=== Next Steps ===\n";
echo "1. Copy api/.env.example to api/.env and configure your settings\n";
echo "2. Ensure MongoDB is running and accessible\n";
echo "3. Run: composer install (if you haven't already)\n";
echo "4. Test your API at: $baseUrl/health\n";
echo "5. View documentation at: $baseUrl/docs\n";
?>
