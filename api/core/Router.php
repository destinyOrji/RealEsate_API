<?php
/**
 * Router Class
 * Handles routing for the application
 */
class Router {
    private $routes = [];
    private $notFoundHandler;
    private $basePath = '/api';
    
    /**
     * Add a route
     */
    public function add($method, $path, $handler) {
        $normalizedPath = $this->normalizePath($path);
        $pattern = $this->convertToPattern($normalizedPath);
        
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $normalizedPath,
            'handler' => $handler,
            'pattern' => $pattern
        ];
    }
    
    /**
     * Add a GET route
     */
    public function get($path, $handler) {
        $this->add('GET', $path, $handler);
    }
    
    /**
     * Add a POST route
     */
    public function post($path, $handler) {
        $this->add('POST', $path, $handler);
    }
    
    /**
     * Add a PUT route
     */
    public function put($path, $handler) {
        $this->add('PUT', $path, $handler);
    }
    
    /**
     * Add a DELETE route
     */
    public function delete($path, $handler) {
        $this->add('DELETE', $path, $handler);
    }
    
    /**
     * Add a PATCH route
     */
    public function patch($path, $handler) {
        $this->add('PATCH', $path, $handler);
    }
    
    /**
     * Set 404 handler
     */
    public function notFound($handler) {
        $this->notFoundHandler = $handler;
    }
    
    /**
     * Dispatch the request
     */
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $this->getRequestUri();
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $uri, $matches)) {
                // Remove the full match
                array_shift($matches);
                
                // Include all parameters (both numeric and named)
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    } else {
                        // Add numeric matches as positional parameters
                        $params[] = $value;
                    }
                }
                
                // Call the handler with parameters
                $this->callHandler($route['handler'], $params);
                return;
            }
        }
        
        // No route matched
        $this->handleNotFound();
    }
    
    /**
     * Call the route handler
     */
    private function callHandler($handler, $params = []) {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
        } elseif (is_string($handler) && strpos($handler, '@') !== false) {
            list($controller, $method) = explode('@', $handler, 2);
            $controller = new $controller();
            call_user_func_array([$controller, $method], $params);
        } else {
            throw new Exception('Invalid route handler');
        }
    }
    
    /**
     * Handle 404 Not Found
     */
    private function handleNotFound() {
        if ($this->notFoundHandler) {
            call_user_func($this->notFoundHandler);
        } else {
            header('HTTP/1.0 404 Not Found');
            echo '404 Not Found';
        }
    }
    
    /**
     * Get the request URI
     */
    private function getRequestUri() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove base path
        if (strpos($uri, $this->basePath) === 0) {
            $uri = substr($uri, strlen($this->basePath));
        }
        
        // Convert to lowercase for case-insensitive routing
        $uri = strtolower($uri);
        
        return $this->normalizePath($uri);
    }
    
    /**
     * Normalize the path
     */
    private function normalizePath($path) {
        return '/' . trim($path, '/');
    }
    
    /**
     * Convert route path to regex pattern
     */
    private function convertToPattern($path) {
        // Handle regex patterns that are already in the path (like ([^/]+))
        if (strpos($path, '(') !== false) {
            // Path already contains regex, just add delimiters
            return '#^' . $path . '$#';
        }
        
        // Escape special regex characters except for route parameters
        $pattern = preg_quote($path, '#');
        
        // Convert route parameters {param} to named capture groups
        $pattern = preg_replace('/\\\{([a-zA-Z0-9_]+)\\\}/', '(?P<$1>[^/]+)', $pattern);
        
        // Add start and end delimiters
        return '#^' . $pattern . '$#';
    }
}
