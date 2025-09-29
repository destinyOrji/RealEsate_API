<?php
/**
 * Base Controller
 * Provides common functionality for all controllers
 */
class BaseController {
    /**
     * Send JSON response
     * 
     * @param mixed $data The data to send
     * @param int $statusCode HTTP status code
     */
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => $statusCode >= 200 && $statusCode < 300 ? 'success' : 'error',
            'data' => $data
        ]);
        exit();
    }

    /**
     * Send error response
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array $errors Additional error details
     */
    protected function errorResponse($message, $statusCode = 400, $errors = []) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ]);
        exit();
    }

    /**
     * Get request body as JSON
     * 
     * @return array Decoded JSON data
     */
    protected function getJsonBody() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errorResponse('Invalid JSON data', 400);
        }
        
        return $data;
    }

    /**
     * Get query parameters
     * 
     * @return array Query parameters
     */
    protected function getQueryParams() {
        return $_GET;
    }

    /**
     * Get request method
     * 
     * @return string Request method
     */
    protected function getRequestMethod() {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Get a specific query parameter
     * 
     * @param string $key Parameter key
     * @param mixed $default Default value if not found
     * @return mixed Parameter value or default
     */
    protected function getQueryParam($key, $default = null) {
        return $_GET[$key] ?? $default;
    }

    /**
     * Send success response
     * 
     * @param string $message Success message
     * @param mixed $data Response data
     * @param int $statusCode HTTP status code
     */
    protected function successResponse($message, $data = null, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        $response = [
            'status' => 'success',
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
        exit();
    }

    /**
     * Get request headers
     * 
     * @return array Request headers
     */
    protected function getHeaders() {
        return getallheaders();
    }

    /**
     * Get authorization header
     * 
     * @return string|null Authorization header or null if not set
     */
    protected function getAuthorizationHeader() {
        $headers = $this->getHeaders();
        if (isset($headers['Authorization'])) {
            return $headers['Authorization'];
        }
        if (isset($headers['authorization'])) {
            return $headers['authorization'];
        }
        return null;
    }

    /**
     * Get bearer token from authorization header
     * 
     * @return string|null Token or null if not found
     */
    protected function getBearerToken() {
        $authHeader = $this->getAuthorizationHeader();
        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Validate required fields in request data
     * 
     * @param array $data Request data
     * @param array $requiredFields List of required fields
     * @return array Array of missing fields
     */
    protected function validateRequiredFields($data, $requiredFields) {
        $missing = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        return $missing;
    }
}

// Base controller for authenticated users
class AuthenticatedController extends BaseController {
    protected $userId;
    protected $userRole;

    public function __construct() {
        $this->authenticate();
    }

    protected function authenticate() {
        $token = $this->getBearerToken();
        
        if (!$token) {
            $this->errorResponse('Authentication required', 401);
        }

        // Verify token and get user data
        // This is a placeholder - implement JWT verification here
        try {
            $userData = $this->verifyToken($token);
            $this->userId = $userData['user_id'];
            $this->userRole = $userData['role'];
        } catch (Exception $e) {
            $this->errorResponse('Invalid or expired token', 401);
        }
    }

    protected function verifyToken($token) {
        try {
            // Include JWT helper
            require_once __DIR__ . '/../helpers/Jwt.php';
            $jwt = new Jwt();
            
            // Decode and verify the token
            $payload = $jwt->decode($token);
            
            // Return user data from token
            return [
                'user_id' => $payload->sub,
                'role' => $payload->role ?? 'client',
                'type' => $payload->type ?? 'access'
            ];
            
        } catch (Exception $e) {
            throw new Exception('Invalid or expired token: ' . $e->getMessage());
        }
    }

    protected function requireRole($allowedRoles) {
        if (!is_array($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }
        
        if (!in_array($this->userRole, $allowedRoles)) {
            $this->errorResponse('Insufficient permissions', 403);
        }
    }
}

// Note: Specific controllers (AuthController, UserController, etc.) 
// are defined in their own files and extend BaseController or AuthenticatedController as needed
