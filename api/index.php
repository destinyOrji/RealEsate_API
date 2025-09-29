<?php
/**
 * API Entry Point
 */

// Start output buffering to prevent any output before JSON
ob_start();

// Disable error display to prevent HTML errors in JSON responses
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Log errors instead of displaying them
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/api_errors.log');

// Set headers for API responses
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if vendor/autoload.php exists, if not, try to include MongoDB manually
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    // Fallback: Try to include MongoDB extension directly
    if (!extension_loaded('mongodb')) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'MongoDB extension not loaded. Please install MongoDB PHP extension or run: composer install'
        ]);
        exit();
    }
}

// Include configuration (this includes Database class)
require_once __DIR__ . '/config/config.php';

// Include helper functions if they exist
if (file_exists(__DIR__ . '/helpers/functions.php')) {
    require_once __DIR__ . '/helpers/functions.php';
}

// Include the Router
require_once __DIR__ . '/core/Router.php';
$router = new Router();

// Basic test route for development
if (defined('APP_DEBUG') && APP_DEBUG) {
    $router->get('/test', function() {
        echo json_encode(['status' => 'success', 'message' => 'Router is working!']);
        exit;
    });
}

// Debug route for development only
if (defined('APP_DEBUG') && APP_DEBUG) {
    $router->get('/debug/routes', function() use ($router) {
        $reflection = new ReflectionClass($router);
        $routesProperty = $reflection->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routes = $routesProperty->getValue($router);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Registered routes',
            'data' => array_map(function($route) {
                return [
                    'method' => $route['method'],
                    'path' => $route['path'],
                    'pattern' => $route['pattern']
                ];
            }, $routes)
        ]);
        exit;
    });
}

// Debug route for development only
if (defined('APP_DEBUG') && APP_DEBUG) {
    $router->get('/debug/request', function() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $basePath = '/api';
        $processedUri = $uri;
        
        if (strpos($uri, $basePath) === 0) {
            $processedUri = substr($uri, strlen($basePath));
        }
        
        $processedUri = strtolower($processedUri);
        $normalizedUri = '/' . trim($processedUri, '/');
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Request debug info',
            'data' => [
                'method' => $_SERVER['REQUEST_METHOD'],
                'original_uri' => $_SERVER['REQUEST_URI'],
                'parsed_path' => $uri,
                'after_base_removal' => $processedUri,
                'normalized' => $normalizedUri,
                'base_path' => $basePath
            ]
        ]);
        exit;
    });
}

// Development auth check endpoint
if (defined('APP_DEBUG') && APP_DEBUG) {
    $router->post('/auth/check-user', function() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['email'])) {
            echo json_encode(['status' => 'error', 'message' => 'Email required']);
            exit;
        }
        
        require_once __DIR__ . '/models/User.php';
        $userModel = new User();
        $user = $userModel->getByEmail($data['email']);
        
        if ($user) {
            echo json_encode([
                'status' => 'success',
                'message' => 'User found',
                'data' => [
                    'email' => $user['email'],
                    'fullname' => $user['fullname'] ?? 'N/A',
                    'role' => $user['role'] ?? 'N/A',
                    'status' => $user['status'] ?? 'N/A',
                    'has_password' => !empty($user['password'])
                ]
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'User not found with this email'
            ]);
        }
        exit;
    });
}

// Development debug login endpoint
if (defined('APP_DEBUG') && APP_DEBUG) {
    $router->post('/auth/debug-login', function() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['email']) || empty($data['password'])) {
            echo json_encode(['status' => 'error', 'message' => 'Email and password required']);
            exit;
        }
        
        require_once __DIR__ . '/models/User.php';
        $userModel = new User();
        $user = $userModel->getByEmail($data['email']);
        
        if (!$user) {
            echo json_encode([
                'status' => 'error',
                'message' => 'User not found',
                'debug' => ['email_searched' => $data['email']]
            ]);
            exit;
        }
        
        $passwordMatch = password_verify($data['password'], $user['password']);
        
        echo json_encode([
            'status' => 'debug',
            'message' => 'Login debug info',
            'debug' => [
                'user_found' => true,
                'email' => $user['email'],
                'has_password' => !empty($user['password']),
                'password_length' => strlen($user['password'] ?? ''),
                'password_match' => $passwordMatch,
                'user_status' => $user['status'] ?? 'unknown',
                'user_role' => $user['role'] ?? 'unknown',
                'provided_password_length' => strlen($data['password'])
            ]
        ]);
        exit;
    });
}

// Development token validation endpoint
if (defined('APP_DEBUG') && APP_DEBUG) {
    $router->get('/auth/validate', function() {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }
        
        if (!$token) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No token provided',
                'debug' => [
                    'auth_header' => $authHeader,
                    'all_headers' => getallheaders()
                ]
            ]);
            exit;
        }
        
        require_once __DIR__ . '/helpers/Jwt.php';
        $jwt = new Jwt();
        $userData = $jwt->decode($token);
        
        if ($userData) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Token is valid',
                'data' => $userData,
                'debug' => [
                    'has_sub' => isset($userData->sub),
                    'has_user_id' => isset($userData->user_id),
                    'properties' => array_keys((array)$userData)
                ]
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid token',
                'token_preview' => substr($token, 0, 20) . '...'
            ]);
        }
        exit;
    });
}

// Include route files first to ensure they're loaded before direct definitions
$routeFiles = [
    'health.php',
    'auth.php',
    'users.php',
    'properties.php',
    'agent.php',
    'admin.php'
];

foreach ($routeFiles as $routeFile) {
    $routePath = __DIR__ . '/routes/' . $routeFile;
    if (file_exists($routePath)) {
        require_once $routePath;
    }
}

// Auth routes are handled in routes/auth.php

// Add user routes directly to ensure they're registered
require_once __DIR__ . '/controllers/UserController.php';
$userController = new UserController();

// Specific /users/me routes MUST come before the generic /users/{id} route
$router->get('/users/me', function() use ($userController) {
    $userController->getCurrentUser();
});

$router->put('/users/me', function() use ($userController) {
    $userController->updateCurrentUser();
});

$router->put('/users/me/password', function() use ($userController) {
    $userController->changePassword();
});

$router->get('/users/me/notifications', function() use ($userController) {
    $userController->getNotifications();
});

$router->get('/users/me/saved-properties', function() use ($userController) {
    $userController->getSavedProperties();
});

$router->get('/users/me/tours', function() use ($userController) {
    $userController->getScheduledTours();
});

// Get all users (admin only)
$router->get('/users', function() use ($userController) {
    $userController->getAllUsers();
});

// Get user by ID (admin only) - MUST come after /users/me routes
$router->get('/users/([^/]+)', function($id) use ($userController) {
    // Don't match 'me' as an ID
    if ($id === 'me') {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Endpoint not found']);
        return;
    }
    $userController->getUserById($id);
});

// Update user by ID (admin only)
$router->put('/users/([^/]+)', function($id) use ($userController) {
    if ($id === 'me') {
        $userController->updateCurrentUser();
        return;
    }
    $userController->updateUser($id);
});

// Delete user by ID (admin only)
$router->delete('/users/([^/]+)', function($id) use ($userController) {
    if ($id === 'me') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Cannot delete yourself']);
        return;
    }
    $userController->deleteUser($id);
});

// Define public routes that don't need authentication
$publicRoutes = [
    '/health',
    '/status', 
    '/test/database',
    '/docs',
    '/auth/register',
    '/auth/login',
    '/auth/forgot-password',
    '/auth/reset-password',
    '/auth/google',
    '/auth/google/url',
    '/auth/google/callback',
    '/auth/verify'
];

// Get current path
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$currentPath = str_replace('/api', '', $currentPath); // Remove /api prefix

// Check if current route is public
$isPublicRoute = false;
foreach ($publicRoutes as $publicRoute) {
    if ($currentPath === $publicRoute || strpos($currentPath, $publicRoute) === 0) {
        $isPublicRoute = true;
        break;
    }
}

// Route files are now loaded earlier in the file

// Set 404 handler
$router->notFound(function() {
    http_response_code(404);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Endpoint not found',
        'path' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
    ]);
});

// Error handling
set_exception_handler(function($exception) {
    // Clear any buffered output
    ob_end_clean();
    
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => defined('APP_DEBUG') && APP_DEBUG ? $exception->getTrace() : []
    ]);
    exit();
});


// Dispatch the request
try {
    if (isset($router) && method_exists($router, 'dispatch')) {
        $router->dispatch();
    } else {
        throw new Exception('Router not properly initialized');
    }
} catch (Exception $e) {
    // Clear any buffered output
    ob_end_clean();
    
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred while processing your request',
        'error' => defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'Internal server error',
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
    exit();
}
