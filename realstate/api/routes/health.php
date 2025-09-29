<?php
/**
 * Health Check Routes
 * Provides system status and health check endpoints
 */

// Health check endpoint
$router->get('/health', function() {
    try {
        // Test database connection
        $database = Database::getInstance();
        $db = $database->getDb();
        
        // Test basic database operation
        $collections = $db->listCollections();
        $dbStatus = 'connected';
        
    } catch (Exception $e) {
        $dbStatus = 'error: ' . $e->getMessage();
    }
    
    $response = [
        'status' => 'healthy',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0.0',
        'environment' => APP_ENV,
        'services' => [
            'api' => 'operational',
            'database' => $dbStatus,
            'mongodb' => extension_loaded('mongodb') ? 'loaded' : 'not loaded'
        ],
        'uptime' => [
            'server' => function_exists('sys_getloadavg') ? sys_getloadavg() : 'unknown',
            'php_version' => PHP_VERSION
        ]
    ];
    
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT);
});

// API status endpoint
$router->get('/status', function() {
    $endpoints = [
        'auth' => [
            'POST /api/auth/register' => 'User registration',
            'POST /api/auth/login' => 'User login',
            'POST /api/auth/refresh' => 'Token refresh',
            'POST /api/auth/logout' => 'User logout',
            'POST /api/auth/forgot-password' => 'Password reset request',
            'POST /api/auth/reset-password' => 'Password reset'
        ],
        'users' => [
            'GET /api/users/me' => 'Get current user profile',
            'PUT /api/users/me' => 'Update current user profile',
            'PUT /api/users/me/password' => 'Change password',
            'POST /api/users/me/avatar' => 'Upload avatar',
            'DELETE /api/users/me/avatar' => 'Delete avatar'
        ],
        'properties' => [
            'GET /api/properties' => 'List properties',
            'GET /api/properties/{id}' => 'Get property details',
            'POST /api/properties' => 'Create property (agents only)',
            'PUT /api/properties/{id}' => 'Update property (agents only)',
            'DELETE /api/properties/{id}' => 'Delete property (agents only)'
        ],
        'admin' => [
            'GET /api/admin/dashboard' => 'Admin dashboard stats',
            'GET /api/admin/users' => 'Manage users',
            'GET /api/admin/properties' => 'Manage properties',
            'GET /api/admin/agents' => 'Manage agents'
        ]
    ];
    
    $response = [
        'api_name' => APP_NAME,
        'version' => '1.0.0',
        'environment' => APP_ENV,
        'base_url' => APP_URL . '/api',
        'documentation' => APP_URL . '/api/docs',
        'endpoints' => $endpoints,
        'authentication' => [
            'type' => 'JWT Bearer Token',
            'header' => 'Authorization: Bearer {token}',
            'token_expiry' => JWT_EXPIRE . ' seconds',
            'refresh_expiry' => JWT_REFRESH_EXPIRE . ' seconds'
        ]
    ];
    
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT);
});

// Test database connection endpoint
$router->get('/test/database', function() {
    try {
        $database = Database::getInstance();
        $db = $database->getDb();
        
        // Test basic operations
        $collections = iterator_to_array($db->listCollections());
        $collectionNames = array_map(function($collection) {
            return $collection->getName();
        }, $collections);
        
        $response = [
            'status' => 'success',
            'message' => 'Database connection successful',
            'database' => DB_NAME,
            'collections' => $collectionNames,
            'collection_count' => count($collectionNames)
        ];
        
        http_response_code(200);
        
    } catch (Exception $e) {
        $response = [
            'status' => 'error',
            'message' => 'Database connection failed',
            'error' => $e->getMessage()
        ];
        
        http_response_code(500);
    }
    
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT);
});

// API documentation endpoint
$router->get('/docs', function() {
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . APP_NAME . ' API Documentation</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .endpoint { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .method { display: inline-block; padding: 5px 10px; border-radius: 3px; color: white; font-weight: bold; }
        .get { background: #28a745; }
        .post { background: #007bff; }
        .put { background: #ffc107; color: black; }
        .delete { background: #dc3545; }
        .path { font-family: monospace; font-size: 14px; }
        .description { margin-top: 10px; color: #666; }
    </style>
</head>
<body>
    <h1>' . APP_NAME . ' API Documentation</h1>
    <p>Base URL: <code>' . APP_URL . '/api</code></p>
    
    <h2>Authentication Endpoints</h2>
    <div class="endpoint">
        <span class="method post">POST</span>
        <span class="path">/auth/register</span>
        <div class="description">Register a new user account</div>
    </div>
    <div class="endpoint">
        <span class="method post">POST</span>
        <span class="path">/auth/login</span>
        <div class="description">Login with email and password</div>
    </div>
    <div class="endpoint">
        <span class="method post">POST</span>
        <span class="path">/auth/refresh</span>
        <div class="description">Refresh access token</div>
    </div>
    
    <h2>User Endpoints</h2>
    <div class="endpoint">
        <span class="method get">GET</span>
        <span class="path">/users/me</span>
        <div class="description">Get current user profile (requires authentication)</div>
    </div>
    <div class="endpoint">
        <span class="method put">PUT</span>
        <span class="path">/users/me</span>
        <div class="description">Update current user profile (requires authentication)</div>
    </div>
    
    <h2>Property Endpoints</h2>
    <div class="endpoint">
        <span class="method get">GET</span>
        <span class="path">/properties</span>
        <div class="description">List all properties</div>
    </div>
    <div class="endpoint">
        <span class="method get">GET</span>
        <span class="path">/properties/{id}</span>
        <div class="description">Get property details by ID</div>
    </div>
    
    <h2>System Endpoints</h2>
    <div class="endpoint">
        <span class="method get">GET</span>
        <span class="path">/health</span>
        <div class="description">API health check</div>
    </div>
    <div class="endpoint">
        <span class="method get">GET</span>
        <span class="path">/status</span>
        <div class="description">API status and available endpoints</div>
    </div>
    
    <h2>Authentication</h2>
    <p>Most endpoints require authentication. Include the JWT token in the Authorization header:</p>
    <code>Authorization: Bearer {your-jwt-token}</code>
    
    <h2>Response Format</h2>
    <p>All responses are in JSON format with the following structure:</p>
    <pre>{
  "status": "success|error",
  "message": "Response message",
  "data": { ... }
}</pre>
</body>
</html>';
    
    header('Content-Type: text/html');
    echo $html;
});
