<?php
/**
 * JWT Helper (Alias for backward compatibility)
 * This is an alias for the Jwt class to maintain backward compatibility
 */

require_once __DIR__ . '/Jwt.php';

class JwtHelper extends Jwt {
    // This class extends Jwt to provide backward compatibility
    // All methods are inherited from the parent Jwt class
}
