<?php
// Agent routes

require_once __DIR__ . '/../controllers/AgentController.php';

$agentController = new AgentController();

// Agent Application Routes
$router->post('/agent/apply', function() use ($agentController) {
    $agentController->applyForAgent();
});

$router->get('/agent/applications', function() use ($agentController) {
    $agentController->getAllApplications();
});

$router->get('/agent/applications/([^/]+)', function($id) use ($agentController) {
    $agentController->getApplicationById($id);
});

$router->put('/agent/applications/([^/]+)/status', function($id) use ($agentController) {
    $agentController->updateApplicationStatus($id);
});

$router->delete('/agent/applications/([^/]+)', function($id) use ($agentController) {
    $agentController->deleteApplication($id);
});

$router->get('/agent/dashboard', function() use ($agentController) {
    $agentController->getDashboard();
});

// Get agent's properties with filters
$router->get('/agent/properties', function() use ($agentController) {
    $agentController->getAgentProperties();
});

// Get scheduled tours for agent
$router->get('/agent/tours', function() use ($agentController) {
    $agentController->getScheduledTours();
});

// Get agent's earnings and commissions
$router->get('/agent/earnings', function() use ($agentController) {
    $agentController->getEarnings();
});

// Get agent's performance metrics
$router->get('/agent/performance', function() use ($agentController) {
    $agentController->getPerformanceMetrics();
});

// Create new property (Agent only)
$router->post('/properties', function() use ($agentController) {
    $agentController->createProperty();
});

// Update property (Agent/Admin only)
$router->put('/properties/([^/]+)', function($id) use ($agentController) {
    $agentController->updateProperty($id);
});

// Delete property (Agent/Admin only)
$router->delete('/properties/([^/]+)', function($id) use ($agentController) {
    $agentController->deleteProperty($id);
});

// Schedule property tour
$router->post('/properties/([^/]+)/schedule-tour', function($id) use ($agentController) {
    $agentController->schedulePropertyTour($id);
});
