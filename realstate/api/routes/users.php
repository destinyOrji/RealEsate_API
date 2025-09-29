<?php
// Include the UserController
require_once __DIR__ . '/../controllers/UserController.php';

// Create a new instance of UserController
$userController = new UserController();

// Get current user profile
$router->get('/users/me', function() use ($userController) {
    $userController->getCurrentUser();
});

// Update current user profile
$router->put('/users/me', function() use ($userController) {
    $userController->updateCurrentUser();
});

// Change current user password
$router->put('/users/me/password', function() use ($userController) {
    $userController->changePassword();
});

// Upload profile picture
$router->post('/users/me/avatar', function() use ($userController) {
    $userController->uploadAvatar();
});

// Delete profile picture
$router->delete('/users/me/avatar', function() use ($userController) {
    $userController->deleteAvatar();
});

// Get user's saved properties
$router->get('/users/me/saved-properties', function() use ($userController) {
    $userController->getSavedProperties();
});

// Get user's scheduled tours
$router->get('/users/me/tours', function() use ($userController) {
    $userController->getScheduledTours();
});

// Get user's notifications
$router->get('/users/me/notifications', function() use ($userController) {
    $userController->getNotifications();
});

// Mark notification as read
$router->put('/users/me/notifications/{id}/read', function($id) use ($userController) {
    $userController->markNotificationAsRead($id);
});

// Mark all notifications as read
$router->put('/users/me/notifications/read-all', function() use ($userController) {
    $userController->markAllNotificationsAsRead();
});

// Get user's messages
$router->get('/users/me/messages', function() use ($userController) {
    $userController->getMessages();
});

// Send message
$router->post('/users/me/messages', function() use ($userController) {
    $userController->sendMessage();
});

// Delete message
$router->delete('/users/me/messages/{id}', function($id) use ($userController) {
    $userController->deleteMessage($id);
});

// Get user's reviews
$router->get('/users/me/reviews', function() use ($userController) {
    $userController->getUserReviews();
});

// Delete user account
$router->delete('/users/me', function() use ($userController) {
    $userController->deleteAccount();
});
