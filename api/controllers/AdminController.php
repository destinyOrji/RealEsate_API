<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Property.php';

class AdminController extends BaseController {
    private $userModel;
    private $propertyModel;

    public function __construct() {
        $this->userModel = new User();
        $this->propertyModel = new Property();
    }

    /**
     * Get admin dashboard statistics
     */
    public function getDashboard() {
        try {
            // Get basic statistics
            $stats = [
                'total_users' => $this->userModel->count(),
                'total_properties' => $this->propertyModel->count(),
                'active_properties' => $this->propertyModel->countByStatus('active'),
                'pending_properties' => $this->propertyModel->countByStatus('pending'),
                'recent_registrations' => $this->userModel->getRecentRegistrations(7),
                'recent_properties' => $this->propertyModel->getRecent(5)
            ];

            $this->successResponse('Dashboard statistics retrieved', $stats);
        } catch (Exception $e) {
            $this->errorResponse('Failed to get dashboard statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get all users with pagination
     */
    public function getAllUsers() {
        try {
            $page = $this->getQueryParam('page', 1);
            $limit = $this->getQueryParam('limit', 10);
            $role = $this->getQueryParam('role', null);

            $users = $this->userModel->getAll($page, $limit, $role);
            $total = $this->userModel->count($role);

            $this->successResponse('Users retrieved successfully', [
                'users' => $users,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        } catch (Exception $e) {
            $this->errorResponse('Failed to get users: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get user by ID
     */
    public function getUserById($id) {
        try {
            $user = $this->userModel->getById($id);
            
            if (!$user) {
                $this->errorResponse('User not found', 404);
            }

            $this->successResponse('User found', $user);
        } catch (Exception $e) {
            $this->errorResponse('Failed to get user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update user status
     */
    public function updateUserStatus($id) {
        try {
            $data = $this->getJsonBody();
            
            if (!isset($data['status'])) {
                $this->errorResponse('Status is required', 400);
            }

            $allowedStatuses = ['active', 'inactive', 'suspended'];
            if (!in_array($data['status'], $allowedStatuses)) {
                $this->errorResponse('Invalid status. Allowed: ' . implode(', ', $allowedStatuses), 400);
            }

            $result = $this->userModel->updateStatus($id, $data['status']);
            
            if ($result) {
                $this->successResponse('User status updated successfully');
            } else {
                $this->errorResponse('Failed to update user status', 500);
            }
        } catch (Exception $e) {
            $this->errorResponse('Failed to update user status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete user
     */
    public function deleteUser($id) {
        try {
            $user = $this->userModel->getById($id);
            
            if (!$user) {
                $this->errorResponse('User not found', 404);
            }

            $result = $this->userModel->delete($id);
            
            if ($result) {
                $this->successResponse('User deleted successfully');
            } else {
                $this->errorResponse('Failed to delete user', 500);
            }
        } catch (Exception $e) {
            $this->errorResponse('Failed to delete user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get all properties with pagination
     */
    public function getAllProperties() {
        try {
            $page = $this->getQueryParam('page', 1);
            $limit = $this->getQueryParam('limit', 10);
            $status = $this->getQueryParam('status', null);

            $properties = $this->propertyModel->getAll($page, $limit, $status);
            $total = $this->propertyModel->count($status);

            $this->successResponse('Properties retrieved successfully', [
                'properties' => $properties,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        } catch (Exception $e) {
            $this->errorResponse('Failed to get properties: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update property status
     */
    public function updatePropertyStatus($id) {
        try {
            $data = $this->getJsonBody();
            
            if (!isset($data['status'])) {
                $this->errorResponse('Status is required', 400);
            }

            $allowedStatuses = ['active', 'inactive', 'pending', 'sold', 'rented'];
            if (!in_array($data['status'], $allowedStatuses)) {
                $this->errorResponse('Invalid status. Allowed: ' . implode(', ', $allowedStatuses), 400);
            }

            $result = $this->propertyModel->updateStatus($id, $data['status']);
            
            if ($result) {
                $this->successResponse('Property status updated successfully');
            } else {
                $this->errorResponse('Failed to update property status', 500);
            }
        } catch (Exception $e) {
            $this->errorResponse('Failed to update property status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete property
     */
    public function deleteProperty($id) {
        try {
            $property = $this->propertyModel->getById($id);
            
            if (!$property) {
                $this->errorResponse('Property not found', 404);
            }

            $result = $this->propertyModel->delete($id);
            
            if ($result) {
                $this->successResponse('Property deleted successfully');
            } else {
                $this->errorResponse('Failed to delete property', 500);
            }
        } catch (Exception $e) {
            $this->errorResponse('Failed to delete property: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get system statistics
     */
    public function getSystemStats() {
        try {
            $stats = [
                'users' => [
                    'total' => $this->userModel->count(),
                    'active' => $this->userModel->countByStatus('active'),
                    'clients' => $this->userModel->countByRole('client'),
                    'agents' => $this->userModel->countByRole('agent'),
                    'admins' => $this->userModel->countByRole('admin')
                ],
                'properties' => [
                    'total' => $this->propertyModel->count(),
                    'active' => $this->propertyModel->countByStatus('active'),
                    'pending' => $this->propertyModel->countByStatus('pending'),
                    'sold' => $this->propertyModel->countByStatus('sold'),
                    'rented' => $this->propertyModel->countByStatus('rented')
                ],
                'recent_activity' => [
                    'new_users_today' => $this->userModel->getRegistrationsToday(),
                    'new_properties_today' => $this->propertyModel->getCreatedToday(),
                    'recent_logins' => $this->userModel->getRecentLogins(10)
                ]
            ];

            $this->successResponse('System statistics retrieved', $stats);
        } catch (Exception $e) {
            $this->errorResponse('Failed to get system statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get application settings
     */
    public function getSettings() {
        $this->successResponse('Application settings', [
            'app_name' => APP_NAME ?? 'CAM-GD Homes',
            'version' => '1.0.0',
            'environment' => APP_ENV ?? 'production',
            'debug_mode' => APP_DEBUG ?? false,
            'message' => 'Settings endpoint - implement as needed'
        ]);
    }

    /**
     * Update application settings
     */
    public function updateSettings() {
        $data = $this->getJsonBody();
        
        $this->successResponse('Settings updated successfully', [
            'updated_settings' => $data,
            'message' => 'Update settings endpoint - implement as needed'
        ]);
    }

    // Additional methods called by admin routes
    public function getAllAgents() {
        $this->successResponse('All agents', [
            'message' => 'Get all agents endpoint - implement as needed'
        ]);
    }

    public function getAllTransactions() {
        $this->successResponse('All transactions', [
            'message' => 'Get all transactions endpoint - implement as needed'
        ]);
    }

    public function getAgentApplications() {
        $this->successResponse('Agent applications', [
            'message' => 'Get agent applications endpoint - implement as needed'
        ]);
    }

    public function updateApplicationStatus($id) {
        $this->successResponse('Application status updated', [
            'id' => $id,
            'message' => 'Update application status endpoint - implement as needed'
        ]);
    }

    public function getSystemStatus() {
        $this->successResponse('System status', [
            'status' => 'operational',
            'uptime' => '99.9%',
            'message' => 'System status endpoint - implement as needed'
        ]);
    }

    public function getRegistrationStats() {
        $this->successResponse('Registration statistics', [
            'message' => 'Registration stats endpoint - implement as needed'
        ]);
    }

    public function getPropertyStats() {
        $this->successResponse('Property statistics', [
            'message' => 'Property stats endpoint - implement as needed'
        ]);
    }

    public function getFinancialStats() {
        $this->successResponse('Financial statistics', [
            'message' => 'Financial stats endpoint - implement as needed'
        ]);
    }

    public function getSystemActivities() {
        $this->successResponse('System activities', [
            'message' => 'System activities endpoint - implement as needed'
        ]);
    }
}
?>
