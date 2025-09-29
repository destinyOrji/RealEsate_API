<?php
/**
 * User Controller
 * Handles user management operations with full CRUD functionality
 */

// Include required files
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Jwt.php';

class UserController extends BaseController {
    private $userModel;
    private $jwtHelper;
    
    public function __construct() {
        $this->userModel = new User();
        $this->jwtHelper = new Jwt();
    }

    /**
     * Get current user profile (/users/me)
     */
    public function getCurrentUser() {
        try {
            $token = $this->getBearerToken();
            if (!$token) {
                $this->errorResponse('Authentication required', 401);
            }

            $userData = $this->jwtHelper->decode($token);
            if (!$userData) {
                $this->errorResponse('Invalid token', 401);
            }

            $user = $this->userModel->getById($userData->sub);
            if (!$user) {
                $this->errorResponse('User not found', 404);
            }

            // Password is already excluded by the model, and _id is already converted to id
            $this->successResponse('User profile retrieved', $user);
        } catch (Exception $e) {
            $this->errorResponse('Failed to get user profile: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get user by ID (/users/{id})
     */
    public function getUserById($id) {
        try {
            $token = $this->getBearerToken();
            if (!$token) {
                $this->errorResponse('Authentication required', 401);
            }

            $userData = $this->jwtHelper->decode($token);
            if (!$userData) {
                $this->errorResponse('Invalid token', 401);
            }

            // Allow access if user is admin or requesting their own profile
            if ($userData->role !== 'admin' && $userData->sub !== $id) {
                $this->errorResponse('Access denied', 403);
            }

            $user = $this->userModel->getById($id);
            if (!$user) {
                $this->errorResponse('User not found', 404);
            }

            // Password is already excluded by the model, and _id is already converted to id
            $this->successResponse('User found', $user);
        } catch (Exception $e) {
            $this->errorResponse('Failed to get user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update current user profile (/users/me)
     */
    public function updateCurrentUser() {
        try {
            $token = $this->getBearerToken();
            if (!$token) {
                $this->errorResponse('Authentication required', 401);
            }

            $userData = $this->jwtHelper->decode($token);
            if (!$userData) {
                $this->errorResponse('Invalid token', 401);
            }

            $data = $this->getJsonBody();
            unset($data['password'], $data['email'], $data['role'], $data['_id'], $data['id']);

            $result = $this->userModel->update($userData->sub, $data);
            if ($result) {
                $this->successResponse('Profile updated successfully');
            } else {
                $this->errorResponse('Failed to update profile', 500);
            }
        } catch (Exception $e) {
            $this->errorResponse('Failed to update profile: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update user by ID (/users/{id}) - Admin only
     */
    public function updateUser($id) {
        try {
            $token = $this->getBearerToken();
            if (!$token) {
                $this->errorResponse('Authentication required', 401);
            }

            $userData = $this->jwtHelper->decode($token);
            if (!$userData || $userData->role !== 'admin') {
                $this->errorResponse('Admin access required', 403);
            }

            $data = $this->getJsonBody();
            
            // Hash password if provided
            if (isset($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $result = $this->userModel->update($id, $data);
            if ($result) {
                $this->successResponse('User updated successfully');
            } else {
                $this->errorResponse('Failed to update user', 500);
            }
        } catch (Exception $e) {
            $this->errorResponse('Failed to update user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete user by ID (/users/{id}) - Admin only
     */
    public function deleteUser($id) {
        try {
            $token = $this->getBearerToken();
            if (!$token) {
                $this->errorResponse('Authentication required', 401);
            }

            $userData = $this->jwtHelper->decode($token);
            if (!$userData || $userData->role !== 'admin') {
                $this->errorResponse('Admin access required', 403);
            }

            // Don't allow deleting yourself
            if ($userData->sub === $id) {
                $this->errorResponse('Cannot delete your own account', 400);
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
     * Get all users with pagination (/users) - Admin only
     */
    public function getAllUsers() {
        try {
            $token = $this->getBearerToken();
            if (!$token) {
                $this->errorResponse('Authentication required', 401);
            }

            $userData = $this->jwtHelper->decode($token);
            if (!$userData || $userData->role !== 'admin') {
                $this->errorResponse('Admin access required', 403);
            }

            $page = $this->getQueryParam('page', 1);
            $limit = $this->getQueryParam('limit', 10);
            $role = $this->getQueryParam('role');

            $users = $this->userModel->getAll($page, $limit, $role);
            $total = $this->userModel->count($role);

            $this->successResponse('Users retrieved', [
                'users' => $users,
                'pagination' => [
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        } catch (Exception $e) {
            $this->errorResponse('Failed to get users: ' . $e->getMessage(), 500);
        }
    }

    // Additional user-specific methods
    public function changePassword() {
        try {
            $token = $this->getBearerToken();
            if (!$token) {
                $this->errorResponse('Authentication required', 401);
            }

            $userData = $this->jwtHelper->decode($token);
            if (!$userData) {
                $this->errorResponse('Invalid token', 401);
            }

            $data = $this->getJsonBody();
            
            if (empty($data['current_password']) || empty($data['new_password'])) {
                $this->errorResponse('Current password and new password are required', 400);
            }

            if ($data['new_password'] !== $data['confirm_password']) {
                $this->errorResponse('New password and confirmation do not match', 400);
            }

            $user = $this->userModel->getById($userData->sub);
            if (!password_verify($data['current_password'], $user['password'])) {
                $this->errorResponse('Current password is incorrect', 400);
            }

            $hashedPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
            $result = $this->userModel->update($userData->sub, ['password' => $hashedPassword]);
            
            if ($result) {
                $this->successResponse('Password changed successfully');
            } else {
                $this->errorResponse('Failed to change password', 500);
            }
        } catch (Exception $e) {
            $this->errorResponse('Failed to change password: ' . $e->getMessage(), 500);
        }
    }

    public function getNotifications() {
        try {
            $token = $this->getBearerToken();
            if (!$token) {
                $this->errorResponse('Authentication required', 401);
            }

            $userData = $this->jwtHelper->decode($token);
            if (!$userData) {
                $this->errorResponse('Invalid token', 401);
            }

            $this->successResponse('Notifications retrieved', [
                'notifications' => [],
                'unread_count' => 0
            ]);
        } catch (Exception $e) {
            $this->errorResponse('Failed to get notifications: ' . $e->getMessage(), 500);
        }
    }

    public function getSavedProperties() {
        try {
            $token = $this->getBearerToken();
            if (!$token) {
                $this->errorResponse('Authentication required', 401);
            }

            $userData = $this->jwtHelper->decode($token);
            if (!$userData) {
                $this->errorResponse('Invalid token', 401);
            }

            $this->successResponse('Saved properties retrieved', [
                'properties' => [],
                'total' => 0
            ]);
        } catch (Exception $e) {
            $this->errorResponse('Failed to get saved properties: ' . $e->getMessage(), 500);
        }
    }

    public function getScheduledTours() {
        try {
            $token = $this->getBearerToken();
            if (!$token) {
                $this->errorResponse('Authentication required', 401);
            }

            $userData = $this->jwtHelper->decode($token);
            if (!$userData) {
                $this->errorResponse('Invalid token', 401);
            }

            $this->successResponse('Scheduled tours retrieved', [
                'tours' => [],
                'total' => 0
            ]);
        } catch (Exception $e) {
            $this->errorResponse('Failed to get scheduled tours: ' . $e->getMessage(), 500);
        }
    }
}
?>
