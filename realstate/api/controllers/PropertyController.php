<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Property.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class PropertyController extends BaseController {
    private $propertyModel;
    private $auth;

    public function __construct() {
        $this->propertyModel = new Property();
        $this->auth = new AuthMiddleware();
    }

    // Get all properties with filters
    public function getAllProperties() {
        try {
            $page = $this->getQueryParam('page', 1);
            $limit = $this->getQueryParam('limit', 10);
            $filters = $this->getFilters();
            $sort = $this->getSortParams();
            
            $result = $this->propertyModel->getAll($filters, $page, $limit, $sort);
            $this->jsonResponse($result);
            
        } catch (Exception $e) {
            $this->errorResponse('Failed to fetch properties', 500);
        }
    }

    // Get property by ID
    public function getProperty($id) {
        try {
            $property = $this->propertyModel->getById($id, true);
            
            if (!$property) {
                return $this->errorResponse('Property not found', 404);
            }
            
            $this->jsonResponse(['data' => $this->formatProperty($property)]);
            
        } catch (Exception $e) {
            $this->errorResponse('Failed to fetch property', 500);
        }
    }

    // Create new property (Agent/Admin only)
    public function createProperty() {
        try {
            $this->auth->requireRole(['agent', 'admin']);
            $data = $this->getJsonBody();
            
            if (!$this->validatePropertyData($data)) {
                return $this->errorResponse('Invalid property data', 400);
            }
            
            $this->checkOwnership($data['agent_id']);
            $propertyId = $this->propertyModel->create($data);
            
            $this->jsonResponse([
                'message' => 'Property created',
                'id' => $propertyId
            ], 201);
            
        } catch (Exception $e) {
            $this->errorResponse('Failed to create property', 500);
        }
    }

    // Update property (Owner/Admin only)
    public function updateProperty($id) {
        try {
            $this->auth->authenticate();
            $data = $this->getJsonBody();
            $property = $this->getPropertyOrFail($id);
            
            $this->checkPropertyOwnership($property);
            $success = $this->propertyModel->update($id, $data);
            
            $this->jsonResponse(['message' => 'Property updated']);
            
        } catch (Exception $e) {
            $this->errorResponse('Failed to update property', 500);
        }
    }

    // Delete property (Owner/Admin only)
    public function deleteProperty($id) {
        try {
            $this->auth->authenticate();
            $property = $this->getPropertyOrFail($id);
            
            $this->checkPropertyOwnership($property);
            $this->propertyModel->delete($id);
            
            $this->jsonResponse(['message' => 'Property deleted']);
            
        } catch (Exception $e) {
            $this->errorResponse('Failed to delete property', 500);
        }
    }

    // Save property to user's list (Client only)
    public function saveProperty($id) {
        try {
            $this->auth->requireRole('client');
            $userId = $_SERVER['USER_ID'];
            
            $this->propertyModel->saveProperty($id, $userId);
            $this->jsonResponse(['message' => 'Property saved']);
            
        } catch (Exception $e) {
            $this->errorResponse('Failed to save property', 500);
        }
    }

    // Remove property from user's list (Client only)
    public function unsaveProperty($id) {
        try {
            $this->auth->requireRole('client');
            $userId = $_SERVER['USER_ID'];
            
            $this->propertyModel->unsaveProperty($id, $userId);
            $this->jsonResponse(['message' => 'Property unsaved']);
            
        } catch (Exception $e) {
            $this->errorResponse('Failed to unsave property', 500);
        }
    }

    // Search properties
    public function searchProperties() {
        try {
            $query = $this->getQueryParam('q', '');
            $page = $this->getQueryParam('page', 1);
            $limit = $this->getQueryParam('limit', 10);
            $filters = $this->getFilters();
            
            $result = $this->propertyModel->search($query, $filters, $page, $limit);
            $this->jsonResponse($result);
            
        } catch (Exception $e) {
            $this->errorResponse('Failed to search properties', 500);
        }
    }

    // Private helper methods
    private function getFilters() {
        $filters = [];
        $filterFields = ['type', 'status', 'min_price', 'max_price', 'bedrooms', 'bathrooms'];
        
        foreach ($filterFields as $field) {
            if ($this->hasQueryParam($field)) {
                $filters[$field] = $this->getQueryParam($field);
            }
        }
        
        return $filters;
    }
    
    private function getSortParams() {
        $sortParam = $this->getQueryParam('sort', '-created_at');
        if (!$sortParam) return [];
        
        $direction = $sortParam[0] === '-' ? -1 : 1;
        $field = ltrim($sortParam, '-');
        return [$field => $direction];
    }
    
    private function validatePropertyData($data) {
        $required = ['title', 'price', 'type', 'property_type', 'agent_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) return false;
        }
        return true;
    }
    
    private function checkOwnership($agentId) {
        $userRole = $_SERVER['USER_ROLE'] ?? '';
        $userId = $_SERVER['USER_ID'] ?? null;
        
        if ($userRole === 'agent' && $agentId !== $userId) {
            throw new Exception('Unauthorized');
        }
    }
    
    private function getPropertyOrFail($id) {
        $property = $this->propertyModel->getById($id);
        if (!$property) {
            throw new Exception('Property not found');
        }
        return $property;
    }
    
    private function checkPropertyOwnership($property) {
        $userRole = $_SERVER['USER_ROLE'] ?? '';
        $userId = $_SERVER['USER_ID'] ?? null;
        
        if ($userRole !== 'admin' && $property['agent_id'] !== $userId) {
            throw new Exception('Unauthorized');
        }
    }
    
    private function formatProperty($property) {
        $property['id'] = (string)$property['_id'];
        unset($property['_id']);
        return $property;
    }
}
