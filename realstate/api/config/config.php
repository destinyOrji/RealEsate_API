<?php
/**
 * Application Configuration
 * Centralized configuration settings with MongoDB Database Connection
 */

// Require the MongoDB library
require_once __DIR__ . '/../../vendor/autoload.php';

// Use MongoDB classes
use MongoDB\Client;
use MongoDB\Driver\Exception\Exception as MongoException;

// Load environment variables from .env file if it exists
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

// Application settings
define('APP_NAME', getenv('APP_NAME') ?: 'CAM-GD Homes');
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN));
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');

// JWT Settings
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'your-256-bit-secret');
define('JWT_EXPIRE', getenv('JWT_EXPIRE') ?: 3600); // 1 hour
define('JWT_REFRESH_EXPIRE', getenv('JWT_REFRESH_EXPIRE') ?: 604800); // 7 days

// Database settings
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '27017');
define('DB_NAME', getenv('DB_NAME') ?: 'cam_gd_homes');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Database Class - MongoDB Connection (Singleton Pattern)
 */
class Database {
    private static $instance = null;
    private $client;
    private $db;
    
    /**
     * Constructor - establishes MongoDB connection
     */
    private function __construct() {
        try {
            // Create connection string
            $dsn = "mongodb://";
            if (DB_USER && DB_PASS) {
                $dsn .= DB_USER . ":" . DB_PASS . "@";
            }
            $dsn .= DB_HOST . ":" . DB_PORT;
            
            // Connection options
            $options = [
                'connectTimeoutMS' => 30000,
                'socketTimeoutMS' => 30000,
                'serverSelectionTimeoutMS' => 5000,
                'retryWrites' => true,
                'w' => 'majority'
            ];
            
            // Create a new client and connect to the server
            $this->client = new Client($dsn, $options);
            
            // Test the connection
            $this->client->listDatabases();
            
            // Select the database
            $this->db = $this->client->selectDatabase(DB_NAME);
            
            error_log('Successfully connected to MongoDB');
            
        } catch (MongoException $e) {
            error_log('MongoDB Connection Error: ' . $e->getMessage());
            $this->handleError($e);
        }
    }
    
    /**
     * Get database instance (Singleton pattern)
     * 
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get database connection
     * 
     * @return MongoDB\Database
     */
    public function getDb() {
        return $this->db;
    }
    
    /**
     * Get collection
     * 
     * @param string $name Collection name
     * @return MongoDB\Collection
     */
    public function getCollection($name) {
        return $this->db->selectCollection($name);
    }
    
    /**
     * Handle database errors
     */
    private function handleError($exception) {
        error_log('Database Error: ' . $exception->getMessage());
        
        if (!headers_sent() && php_sapi_name() !== 'cli') {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => APP_DEBUG ? $exception->getMessage() : null
            ]);
        } else {
            die('Database connection failed: ' . $exception->getMessage());
        }
        exit;
    }
    
    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}
    
    /**
     * Prevent unserializing of the instance
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Mail settings
define('MAIL_DRIVER', getenv('MAIL_DRIVER') ?: 'smtp');
define('MAIL_HOST', getenv('MAIL_HOST') ?: 'smtp.mailtrap.io');
define('MAIL_PORT', getenv('MAIL_PORT') ?: 2525);
define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: '');
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: '');
define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION') ?: 'tls');
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'noreply@camgdhomes.com');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'CAM-GD Homes');

// File upload settings
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// CORS settings
define('ALLOWED_ORIGINS', [
    'http://localhost:3000',
    'http://localhost',
    'https://camgdhomes.com'
]);

// Error reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Timezone
date_default_timezone_set(getenv('TIMEZONE') ?: 'UTC');

// Set custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return;
    }
    
    $error = [
        'type' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ];
    
    error_log(json_encode($error));
    
    if (APP_DEBUG && !headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'An error occurred',
            'error' => $error
        ]);
    }
    
    return true; // Don't execute PHP internal error handler
});

// Set exception handler
set_exception_handler(function($exception) {
    $error = [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ];
    
    error_log(json_encode($error));
    
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'An unexpected error occurred',
            'error' => APP_DEBUG ? $error : null
        ]);
    }
});

// Set shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        error_log(json_encode($error));
        
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'A fatal error occurred',
                'error' => APP_DEBUG ? $error : null
            ]);
        }
    }
});
