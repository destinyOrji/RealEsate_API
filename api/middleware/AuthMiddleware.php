<?php
/**
 * Authentication Middleware
 * Handles JWT token verification and role-based access control
 */

class AuthMiddleware {
    private $jwt;
    
    public function __construct() {
        $this->jwt = new Jwt();
    }
    
    /**
     * Verify JWT token from Authorization header
     */
    public function authenticate() {
        $authHeader = $this->getAuthorizationHeader();
        
        if (!$authHeader) {
            $this->sendUnauthorized('No token provided');
        }
        
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            
            try {
                $payload = $this->jwt->decode($token);
                
                if (!isset($payload->sub)) {
                    $this->sendUnauthorized('Invalid token');
                }
                
                // Add user ID and role to request for use in controllers
                $_SERVER['USER_ID'] = $payload->sub;
                $_SERVER['USER_ROLE'] = $payload->role ?? 'user';
                
                return true;
                
            } catch (Exception $e) {
                $this->sendUnauthorized('Invalid or expired token');
            }
        }
        
        $this->sendUnauthorized('Invalid authorization header');
    }
    
    /**
     * Check if user has required role
     */
    public function requireRole($roles) {
        $this->authenticate();
        
        $userRole = $_SERVER['USER_ROLE'] ?? null;
        
        if (is_string($roles)) {
            $roles = [$roles];
        }
        
        if (!in_array($userRole, $roles)) {
            $this->sendForbidden('Insufficient permissions');
        }
        
        return true;
    }
    
    /**
     * Check if user is the owner of a resource
     */
    public function requireOwnership($ownerId) {
        $this->authenticate();
        
        $userId = $_SERVER['USER_ID'] ?? null;
        $userRole = $_SERVER['USER_ROLE'] ?? null;
        
        // Allow admins to access any resource
        if ($userRole === 'admin') {
            return true;
        }
        
        if ($userId !== $ownerId) {
            $this->sendForbidden('You do not own this resource');
        }
        
        return true;
    }
    
    /**
     * Get authorization header
     */
    private function getAuthorizationHeader() {
        $headers = null;
        
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );
            
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        
        return $headers;
    }
    
    /**
     * Send 401 Unauthorized response
     */
    private function sendUnauthorized($message = 'Unauthorized') {
        header('HTTP/1.1 401 Unauthorized');
        header('Content-Type: application/json');
        
        echo json_encode([
            'status' => 'error',
            'message' => $message
        ]);
        
        exit;
    }
    
    /**
     * Send 403 Forbidden response
     */
    private function sendForbidden($message = 'Forbidden') {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: application/json');
        
        echo json_encode([
            'status' => 'error',
            'message' => $message
        ]);
        
        exit;
    }
}
