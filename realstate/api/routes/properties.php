<?php
// Include the PropertyController
require_once __DIR__ . '/../controllers/PropertyController.php';

// Create a new instance of PropertyController
$propertyController = new PropertyController();

// Get all properties with filters
$router->get('/properties', function() use ($propertyController) {
    $propertyController->getAllProperties();
});

// Get property details
$router->get('/properties/{id}', function($id) use ($propertyController) {
    $propertyController->getProperty($id);
});

// Save property (Client only)
$router->post('/properties/{id}/save', function($id) use ($propertyController) {
    $propertyController->saveProperty($id);
});

// Unsave property (Client only)
$router->delete('/properties/{id}/unsave', function($id) use ($propertyController) {
    $propertyController->unsaveProperty($id);
});

// Get property images
$router->get('/properties/{id}/images', function($id) use ($propertyController) {
    $propertyController->getPropertyImages($id);
});

// Upload property image (Agent/Admin only)
$router->post('/properties/{id}/images', function($id) use ($propertyController) {
    $propertyController->uploadPropertyImage($id);
});

// Delete property image (Agent/Admin only)
$router->delete('/properties/{id}/images/{imageId}', function($id, $imageId) use ($propertyController) {
    $propertyController->deletePropertyImage($id, $imageId);
});

// Get property reviews
$router->get('/properties/{id}/reviews', function($id) use ($propertyController) {
    $propertyController->getPropertyReviews($id);
});

// Add property review (Client only)
$router->post('/properties/{id}/reviews', function($id) use ($propertyController) {
    $propertyController->addPropertyReview($id);
});

// Search properties
$router->get('/properties/search', function() use ($propertyController) {
    $propertyController->searchProperties();
});

// Get featured properties
$router->get('/properties/featured', function() use ($propertyController) {
    $propertyController->getFeaturedProperties();
});

// Get similar properties
$router->get('/properties/{id}/similar', function($id) use ($propertyController) {
    $propertyController->getSimilarProperties($id);
});

// Get property statistics (Agent/Admin only)
$router->get('/properties/{id}/stats', function($id) use ($propertyController) {
    $propertyController->getPropertyStats($id);
});
