<?php
/**
 * User Model
 * Handles user authentication and profile management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/Jwt.php';

class User {
    private $collection;
    private $jwt;

    public function __construct() {
        $db = Database::getInstance();
        $this->collection = $db->getCollection('users');
        $this->jwt = new Jwt();
    }

    /**
     * Create a new user
     */
    public function create($data) {
        try {
            if ($this->emailExists($data['email'])) {
                throw new Exception('Email already registered');
            }

            $userData = [
                'fullname' => $data['fullname'],
                'email' => strtolower(trim($data['email'])),
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'phone' => $data['phone'] ?? '',
                'role' => $data['role'] ?? 'user',
                'status' => 'active',
                'avatar' => $data['avatar'] ?? null,
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ];

            $result = $this->collection->insertOne($userData);
            return (string)$result->getInsertedId();
            
        } catch (Exception $e) {
            error_log('User creation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get user by ID
     */
    public function getById($id) {
        try {
            $user = $this->collection->findOne(
                ['_id' => new MongoDB\BSON\ObjectId($id)],
                [
                    'projection' => ['password' => 0],
                    'typeMap' => ['root' => 'array', 'document' => 'array']
                ]
            );

            if ($user) {
                $user['id'] = (string)$user['_id'];
                unset($user['_id']);
                return $user;
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log('Error getting user by ID: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user by email
     */
    public function getByEmail($email) {
        try {
            return $this->collection->findOne(
                ['email' => strtolower(trim($email))],
                ['typeMap' => ['root' => 'array', 'document' => 'array']]
            );
        } catch (Exception $e) {
            error_log('Error getting user by email: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Verify user credentials
     */
    public function verifyCredentials($email, $password) {
        $user = $this->getByEmail($email);
        
        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }
        
        // Remove password before returning
        unset($user['password']);
        return $user;
    }

    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        try {
            $updateData = [];
            
            if (isset($data['fullname'])) {
                $updateData['fullname'] = $data['fullname'];
            }
            
            if (isset($data['phone'])) {
                $updateData['phone'] = $data['phone'];
            }
            
            if (isset($data['avatar'])) {
                $updateData['avatar'] = $data['avatar'];
            }
            
            $updateData['updated_at'] = new MongoDB\BSON\UTCDateTime();
            
            $result = $this->collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($userId)],
                ['$set' => $updateData]
            );
            
            return $result->getModifiedCount() > 0;
            
        } catch (Exception $e) {
            error_log('Error updating user profile: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user password
     */
    public function updatePassword($userId, $currentPassword, $newPassword) {
        try {
            // First verify current password
            $user = $this->collection->findOne(
                ['_id' => new MongoDB\BSON\ObjectId($userId)],
                ['typeMap' => ['root' => 'array', 'document' => 'array']]
            );
            
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                return false;
            }
            
            // Update password
            $result = $this->collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($userId)],
                [
                    '$set' => [
                        'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                        'updated_at' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
            
            return $result->getModifiedCount() > 0;
            
        } catch (Exception $e) {
            error_log('Error updating password: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if email exists
     */
    public function emailExists($email) {
        return $this->collection->countDocuments(['email' => strtolower(trim($email))]) > 0;
    }

    /**
     * Generate JWT token
     */
    public function generateToken($userId, $role) {
        $payload = [
            'sub' => (string)$userId,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24) // 24 hours
        ];
        
        return $this->jwt->encode($payload);
    }

    /**
     * Get all users with pagination and filters
     */
    public function getAll($filters = [], $page = 1, $limit = 10) {
        try {
            $filter = [];
            
            // Apply filters
            if (isset($filters['role'])) {
                $filter['role'] = $filters['role'];
            }
            
            if (isset($filters['status'])) {
                $filter['status'] = $filters['status'];
            }
            
            if (isset($filters['search'])) {
                $filter['$or'] = [
                    ['fullname' => ['$regex' => $filters['search'], '$options' => 'i']],
                    ['email' => ['$regex' => $filters['search'], '$options' => 'i']]
                ];
            }
            
            $options = [
                'skip' => ($page - 1) * $limit,
                'limit' => $limit,
                'sort' => ['created_at' => -1],
                'projection' => [
                    'password' => 0 // Don't return password
                ],
                'typeMap' => ['root' => 'array', 'document' => 'array']
            ];
            
            $cursor = $this->collection->find($filter, $options);
            $users = iterator_to_array($cursor);
            
            // Convert ObjectId to string for JSON serialization
            foreach ($users as &$user) {
                $user['id'] = (string)$user['_id'];
                unset($user['_id']);
            }
            
            // Get total count for pagination
            $total = $this->collection->countDocuments($filter);
            
            return [
                'data' => $users,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ];
            
        } catch (Exception $e) {
            error_log('Error getting users: ' . $e->getMessage());
            return [
                'data' => [],
                'pagination' => [
                    'total' => 0,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => 0
                ]
            ];
        }
    }

    /**
     * Update user
     */
    public function update($id, $data) {
        try {
            $updateData = ['$set' => []];
            
            $updatableFields = [
                'fullname', 'email', 'phone', 'avatar', 'status'
            ];
            
            foreach ($updatableFields as $field) {
                if (isset($data[$field])) {
                    if ($field === 'email') {
                        $updateData['$set'][$field] = strtolower(trim($data[$field]));
                    } else {
                        $updateData['$set'][$field] = $data[$field];
                    }
                }
            }
            
            // Handle password update separately
            if (isset($data['password'])) {
                $updateData['$set']['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            $updateData['$set']['updated_at'] = new MongoDB\BSON\UTCDateTime();
            
            $result = $this->collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($id)],
                $updateData
            );
            
            return $result->getModifiedCount() > 0;
            
        } catch (Exception $e) {
            error_log('Error updating user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user status
     */
    public function updateStatus($id, $status) {
        try {
            $result = $this->collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($id)],
                [
                    '$set' => [
                        'status' => $status,
                        'updated_at' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
            
            return $result->getModifiedCount() > 0;
            
        } catch (Exception $e) {
            error_log('Error updating user status: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete user
     */
    public function delete($id) {
        try {
            $result = $this->collection->deleteOne(
                ['_id' => new MongoDB\BSON\ObjectId($id)]
            );
            
            return $result->getDeletedCount() > 0;
            
        } catch (Exception $e) {
            error_log('Error deleting user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get users by role
     */
    public function getByRole($role, $page = 1, $limit = 10) {
        return $this->getAll(['role' => $role], $page, $limit);
    }

    /**
     * Change user password
     */
    public function changePassword($id, $currentPassword, $newPassword) {
        try {
            // First verify current password
            $user = $this->collection->findOne(
                ['_id' => new MongoDB\BSON\ObjectId($id)],
                ['typeMap' => ['root' => 'array', 'document' => 'array']]
            );
            
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                return false;
            }
            
            // Update with new password
            $result = $this->collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($id)],
                [
                    '$set' => [
                        'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                        'updated_at' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
            
            return $result->getModifiedCount() > 0;
            
        } catch (Exception $e) {
            error_log('Error changing password: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Reset password
     */
    public function resetPassword($email, $newPassword) {
        try {
            $result = $this->collection->updateOne(
                ['email' => strtolower(trim($email))],
                [
                    '$set' => [
                        'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                        'updated_at' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
            
            return $result->getModifiedCount() > 0;
            
        } catch (Exception $e) {
            error_log('Error resetting password: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user profile with additional data
     */
    public function getProfile($id) {
        try {
            $user = $this->getById($id);
            if (!$user) {
                return null;
            }
            
            // Add additional profile data
            $user['total_properties'] = 0;
            $user['saved_properties'] = 0;
            
            // If user is an agent, get their properties count
            if ($user['role'] === 'agent') {
                $propertyModel = new Property();
                $properties = $propertyModel->getByAgent($id, 1, 1);
                $user['total_properties'] = $properties['pagination']['total'];
            }
            
            return $user;
            
        } catch (Exception $e) {
            error_log('Error getting user profile: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Count total users
     */
    public function count($role = null) {
        try {
            $filter = [];
            if ($role) {
                $filter['role'] = $role;
            }
            return $this->collection->countDocuments($filter);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Count users by status
     */
    public function countByStatus($status) {
        try {
            return $this->collection->countDocuments(['status' => $status]);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Count users by role
     */
    public function countByRole($role) {
        try {
            return $this->collection->countDocuments(['role' => $role]);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get recent registrations
     */
    public function getRecentRegistrations($days = 7) {
        try {
            $cutoffDate = new MongoDB\BSON\UTCDateTime((time() - ($days * 24 * 60 * 60)) * 1000);
            return $this->collection->countDocuments(['created_at' => ['$gte' => $cutoffDate]]);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get registrations today
     */
    public function getRegistrationsToday() {
        try {
            $startOfDay = new MongoDB\BSON\UTCDateTime(strtotime('today') * 1000);
            return $this->collection->countDocuments(['created_at' => ['$gte' => $startOfDay]]);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get recent logins
     */
    public function getRecentLogins($limit = 10) {
        try {
            return $this->collection->find(
                ['last_login' => ['$exists' => true]],
                [
                    'sort' => ['last_login' => -1],
                    'limit' => $limit,
                    'projection' => ['fullname' => 1, 'email' => 1, 'last_login' => 1]
                ]
            )->toArray();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Update user's last login timestamp
     */
    public function updateLastLogin($id) {
        try {
            $result = $this->collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($id)],
                ['$set' => ['last_login' => new MongoDB\BSON\UTCDateTime()]]
            );
            return $result->getModifiedCount() > 0;
        } catch (Exception $e) {
            error_log('Error updating last login: ' . $e->getMessage());
            return false;
        }
    }
}
