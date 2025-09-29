<?php
// Include the ClientController
require_once __DIR__ . '/../controllers/ClientController.php';

// Create a new instance of ClientController
$clientController = new ClientController();

// Client Dashboard Routes
$router->get('/client/dashboard', function() use ($clientController) {
    $clientController->getDashboard();
});

$router->get('/client/saved-properties', function() use ($clientController) {
    $clientController->getSavedProperties();
});

$router->get('/client/tours', function() use ($clientController) {
    $clientController->getScheduledTours();
});

$router->get('/client/properties/recent', function() use ($clientController) {
    $clientController->getRecentlyViewedProperties();
});

$router->get('/client/notifications', function() use ($clientController) {
    $clientController->getNotifications();
});
