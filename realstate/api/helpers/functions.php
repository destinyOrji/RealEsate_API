<?php
/**
 * Helper Functions
 */

/**
 * Get JSON input from request body
 */
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}

/**
 * Send JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Send success response
 */
function successResponse($data = null, $message = 'Success') {
    jsonResponse([
        'status' => 'success',
        'message' => $message,
        'data' => $data
    ]);
}

/**
 * Send error response
 */
function errorResponse($message = 'An error occurred', $statusCode = 400, $data = null) {
    jsonResponse([
        'status' => 'error',
        'message' => $message,
        'data' => $data
    ], $statusCode);
}

/**
 * Validate required fields
 */
function validateRequired($data, $fields) {
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }
    return $missing;
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get authorization header
 */
function getAuthorizationHeader() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        return $headers['Authorization'];
    }
    if (isset($headers['authorization'])) {
        return $headers['authorization'];
    }
    return null;
}

/**
 * Extract Bearer token
 */
function getBearerToken() {
    $header = getAuthorizationHeader();
    if ($header && preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
        return $matches[1];
    }
    return null;
}
