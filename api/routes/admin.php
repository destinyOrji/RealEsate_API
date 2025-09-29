<?php
// Include the AdminController
require_once __DIR__ . '/../controllers/AdminController.php';

// Create a new instance of AdminController
$adminController = new AdminController();

// Admin Dashboard Routes
$router->get('/admin/dashboard', function() use ($adminController) {
    $adminController->getDashboard();
});

// User Management
$router->get('/admin/users', function() use ($adminController) {
    $adminController->getAllUsers();
});

// Agent Management
$router->get('/admin/agents', function() use ($adminController) {
    $adminController->getAllAgents();
});

// Property Management
$router->get('/admin/properties', function() use ($adminController) {
    $adminController->getAllProperties();
});

// Transactions
$router->get('/admin/transactions', function() use ($adminController) {
    $adminController->getAllTransactions();
});

// Agent Applications
$router->get('/admin/agent-applications', function() use ($adminController) {
    $adminController->getAgentApplications();
});

$router->put('/admin/agent-applications/([^/]+)/status', function($id) use ($adminController) {
    $adminController->updateApplicationStatus($id);
});

// System Status
$router->get('/admin/system-status', function() use ($adminController) {
    $adminController->getSystemStatus();
});

// Statistics
$router->get('/admin/stats/registrations', function() use ($adminController) {
    $adminController->getRegistrationStats();
});

$router->get('/admin/stats/properties', function() use ($adminController) {
    $adminController->getPropertyStats();
});

$router->get('/admin/stats/financials', function() use ($adminController) {
    $adminController->getFinancialStats();
});

// System Activities
$router->get('/admin/activities', function() use ($adminController) {
    $adminController->getSystemActivities();
});

// User Management Actions
$router->get('/admin/users/([^/]+)', function($id) use ($adminController) {
    $adminController->getUserById($id);
});

$router->put('/admin/users/([^/]+)/status', function($id) use ($adminController) {
    $adminController->updateUserStatus($id);
});

$router->delete('/admin/users/([^/]+)', function($id) use ($adminController) {
    $adminController->deleteUser($id);
});

// Property Management Actions
$router->put('/admin/properties/([^/]+)/status', function($id) use ($adminController) {
    $adminController->updatePropertyStatus($id);
});

$router->delete('/admin/properties/([^/]+)', function($id) use ($adminController) {
    $adminController->deleteProperty($id);
});

// System Statistics
$router->get('/admin/system/stats', function() use ($adminController) {
    $adminController->getSystemStats();
});

// Settings
$router->get('/admin/settings', function() use ($adminController) {
    $adminController->getSettings();
});

$router->put('/admin/settings', function() use ($adminController) {
    $adminController->updateSettings();
});
