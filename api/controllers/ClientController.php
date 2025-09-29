<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Property.php';
require_once __DIR__ . '/../models/Tour.php';
require_once __DIR__ . '/../models/Notification.php';

class ClientController extends BaseController {
    private $userModel;
    private $propertyModel;
    private $tourModel;
    private $notificationModel;

    public function __construct() {
        $this->userModel = new User();
        $this->propertyModel = new Property();
        $this->tourModel = new Tour();
        $this->notificationModel = new Notification();
    }

    /**
     * Get client dashboard overview
     */
    public function getDashboard() {
        try {
            $userId = $this->getCurrentUserId();
            
            // Get saved properties count
            $savedProperties = $this->propertyModel->getSavedProperties($userId);
            
            // Get upcoming tours
            $upcomingTours = $this->tourModel->getUserTours($userId, 'upcoming');
            
            // Get recent notifications
            $notifications = $this->notificationModel->getUserNotifications($userId, 5);
            
            // Get recently viewed properties
            $recentProperties = $this->propertyModel->getRecentlyViewed($userId, 5);
            
            $this->jsonResponse([
                'saved_properties_count' => count($savedProperties),
                'upcoming_tours_count' => count($upcomingTours),
                'recent_notifications' => $notifications,
                'recent_properties' => $recentProperties
            ]);
            
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get saved properties
     */
    public function getSavedProperties() {
        try {
            $userId = $this->getCurrentUserId();
            $page = $this->getQueryParam('page', 1);
            $limit = $this->getQueryParam('limit', 10);
            
            $result = $this->propertyModel->getSavedProperties($userId, $page, $limit);
            
            $this->jsonResponse([
                'data' => $result['data'],
                'pagination' => $result['pagination']
            ]);
            
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get scheduled tours
     */
    public function getScheduledTours() {
        try {
            $userId = $this->getCurrentUserId();
            $status = $this->getQueryParam('status', 'upcoming'); // 'upcoming' or 'past'
            
            $tours = $this->tourModel->getUserTours($userId, $status);
            
            $this->jsonResponse([
                'data' => $tours
            ]);
            
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get recently viewed properties
     */
    public function getRecentlyViewedProperties() {
        try {
            $userId = $this->getCurrentUserId();
            $limit = $this->getQueryParam('limit', 10);
            
            $properties = $this->propertyModel->getRecentlyViewed($userId, $limit);
            
            $this->jsonResponse([
                'data' => $properties
            ]);
            
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get notifications
     */
    public function getNotifications() {
        try {
            $userId = $this->getCurrentUserId();
            $limit = $this->getQueryParam('limit', 10);
            
            $notifications = $this->notificationModel->getUserNotifications($userId, $limit);
            
            $this->jsonResponse([
                'data' => $notifications
            ]);
            
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get current user ID from JWT token
     */
    private function getCurrentUserId() {
        $token = $this->getBearerToken();
        $jwt = new Jwt();
        $payload = $jwt->decode($token);
        
        if (!isset($payload->sub)) {
            throw new Exception('Invalid token');
        }
        
        return $payload->sub;
    }
}
