<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Jwt.php';

class AuthController extends BaseController {
    private $userModel;
    private $jwt;

    public function __construct() {
        $this->userModel = new User();
        $this->jwt = new Jwt();
    }

    /**
     * Register a new user
     */
    public function register() {
        $data = $this->getJsonBody();
        
        // Validate required fields
        $required = ['fullname', 'email', 'password'];
        $missing = $this->validateRequiredFields($data, $required);
        
        if (!empty($missing)) {
            $this->errorResponse('Missing required fields: ' . implode(', ', $missing), 400);
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errorResponse('Invalid email format', 400);
        }

        // Check if email already exists
        if ($this->userModel->emailExists($data['email'])) {
            $this->errorResponse('Email already registered', 409);
        }

        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Set default role if not provided
        if (!isset($data['role'])) {
            $data['role'] = 'client';
        }

        // Create user
        try {
            $userId = $this->userModel->create($data);
            
            if (!$userId) {
                throw new Exception('Failed to create user');
            }
            
            // Generate tokens
            $tokens = $this->generateTokens($userId, $data['role']);
            
            // Get user data (without password)
            $user = $this->userModel->getById($userId);
            
            $this->jsonResponse([
                'message' => 'User registered successfully',
                'user' => $user,
                'tokens' => $tokens
            ], 201);
            
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * User login
     */
    public function login() {
        $data = $this->getJsonBody();
        
        // Validate required fields
        $required = ['email', 'password'];
        $missing = $this->validateRequiredFields($data, $required);
        
        if (!empty($missing)) {
            $this->errorResponse('Missing required fields: ' . implode(', ', $missing), 400);
        }

        // Get user by email
        $user = $this->userModel->getByEmail($data['email']);
        
        if (!$user || !password_verify($data['password'], $user['password'])) {
            $this->errorResponse('Invalid email or password', 401);
        }

        // Check if user is active
        if ($user['status'] !== 'active') {
            $this->errorResponse('Account is not active', 403);
        }

        // Generate tokens
        $tokens = $this->generateTokens((string)$user['_id'], $user['role']);
        
        // Update last login
        $this->userModel->updateLastLogin((string)$user['_id']);
        
        // Remove sensitive data
        unset($user['password']);
        $user['id'] = (string)$user['_id'];
        unset($user['_id']);

        $this->jsonResponse([
            'message' => 'Login successful',
            'user' => $user,
            'tokens' => $tokens
        ]);
    }

    /**
     * Refresh access token
     */
    public function refreshToken() {
        $data = $this->getJsonBody();
        
        if (empty($data['refresh_token'])) {
            $this->errorResponse('Refresh token is required', 400);
        }

        try {
            $payload = $this->jwt->decode($data['refresh_token']);
            
            if ($payload->type !== 'refresh') {
                throw new Exception('Invalid token type');
            }

            // Get user from database
            $user = $this->userModel->getById($payload->sub);
            
            if (!$user) {
                throw new Exception('User not found', 404);
            }

            // Check if user is active
            if ($user['status'] !== 'active') {
                throw new Exception('Account is not active', 403);
            }

            // Generate new tokens
            $tokens = $this->generateTokens($payload->sub, $user['role']);
            
            $this->jsonResponse([
                'message' => 'Token refreshed successfully',
                'tokens' => $tokens
            ]);
            
        } catch (Exception $e) {
            $this->errorResponse('Invalid or expired refresh token', 401);
        }
    }

    /**
     * Request password reset
     */
    public function forgotPassword() {
        $data = $this->getJsonBody();
        
        if (empty($data['email'])) {
            $this->errorResponse('Email is required', 400);
        }

        $user = $this->userModel->getByEmail($data['email']);
        
        if ($user) {
            // Generate reset token (1 hour expiry)
            $resetToken = $this->jwt->encode([
                'sub' => (string)$user['_id'],
                'type' => 'reset',
                'exp' => time() + 3600 // 1 hour
            ]);

            // TODO: Send password reset email
            // $resetLink = "https://yourapp.com/reset-password?token=" . urlencode($resetToken);
            // $this->sendPasswordResetEmail($user['email'], $resetLink);
        }

        // Always return success to prevent email enumeration
        $this->jsonResponse([
            'message' => 'If your email exists in our system, you will receive a password reset link'
        ]);
    }

    /**
     * Reset password with token
     */
    public function resetPassword() {
        $data = $this->getJsonBody();
        
        $required = ['token', 'new_password'];
        $missing = $this->validateRequiredFields($data, $required);
        
        if (!empty($missing)) {
            $this->errorResponse('Missing required fields: ' . implode(', ', $missing), 400);
        }

        try {
            $payload = $this->jwt->decode($data['token']);
            
            if ($payload->type !== 'reset') {
                throw new Exception('Invalid token type');
            }

            // Hash new password
            $hashedPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
            
            // Update password
            $success = $this->userModel->updatePassword($payload->sub, $hashedPassword);
            
            if (!$success) {
                throw new Exception('Failed to update password');
            }
            
            $this->jsonResponse([
                'message' => 'Password reset successful'
            ]);
            
        } catch (Exception $e) {
            $this->errorResponse('Invalid or expired reset token', 400);
        }
    }

    /**
     * User logout
     */
    public function logout() {
        // In a stateless JWT system, the client should discard the tokens
        $this->jsonResponse([
            'message' => 'Logout successful'
        ]);
    }

    /**
     * Generate access and refresh tokens
     */
    private function generateTokens($userId, $role) {
        $now = time();
        
        // Access token (15 minutes expiry)
        $accessToken = $this->jwt->encode([
            'sub' => $userId,
            'role' => $role,
            'type' => 'access',
            'iat' => $now,
            'exp' => $now + 900 // 15 minutes
        ]);
        
        // Refresh token (7 days expiry)
        $refreshToken = $this->jwt->encode([
            'sub' => $userId,
            'type' => 'refresh',
            'iat' => $now,
            'exp' => $now + (7 * 24 * 3600) // 7 days
        ]);
        
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => 900 // 15 minutes in seconds
        ];
    }
}
