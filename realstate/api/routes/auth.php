<?php
/**
 * Authentication Routes
 * Defines routes for user authentication
 */

// Include the AuthController
require_once __DIR__ . '/../controllers/AuthController.php';

// Initialize the AuthController
$authController = new AuthController();

// Register routes
$router->post('/auth/register', function() use ($authController) {
    $authController->register();
});

$router->post('/auth/login', function() use ($authController) {
    $authController->login();
});

$router->post('/auth/forgot-password', function() use ($authController) {
    $authController->forgotPassword();
});

$router->post('/auth/reset-password', function() use ($authController) {
    $authController->resetPassword();
});

$router->post('/auth/refresh', function() use ($authController) {
    $authController->refreshToken();
});

$router->post('/auth/logout', function() use ($authController) {
    $authController->logout();
});

$router->get('/auth/verify/([^/]+)', function($token) use ($authController) {
    $authController->verifyEmail($token);
});