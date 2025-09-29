<?php
/**
 * JWT Helper
 * Handles JSON Web Token generation and validation
 */
class Jwt {
    private $secretKey;
    private $algorithm = 'HS256';
    private $supportedAlgs = [
        'HS256' => 'sha256',
        'HS384' => 'sha384',
        'HS512' => 'sha512'
    ];

    public function __construct() {
        // Use a consistent secret key - in production, use environment variable
        $this->secretKey = getenv('JWT_SECRET') ?: 'CAM-GD-HOMES-JWT-SECRET-KEY-2024';
    }

    /**
     * Encode data into a JWT token
     * 
     * @param array $payload The data to encode
     * @return string The encoded JWT token
     */
    public function encode(array $payload) {
        $header = [
            'typ' => 'JWT',
            'alg' => $this->algorithm
        ];

        // Encode header and payload
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        
        // Create signature
        $signature = $this->sign($headerEncoded . '.' . $payloadEncoded);
        $signatureEncoded = $this->base64UrlEncode($signature);
        
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    /**
     * Decode and validate a JWT token
     * 
     * @param string $token The JWT token to decode
     * @return object The decoded payload
     * @throws Exception If the token is invalid
     */
    public function decode($token) {
        try {
            // Split the token into parts
            $tokenParts = explode('.', $token);
            
            if (count($tokenParts) !== 3) {
                return false;
            }
            
            list($headerEncoded, $payloadEncoded, $signatureEncoded) = $tokenParts;
            
            // Decode header and payload
            $header = json_decode($this->base64UrlDecode($headerEncoded), true);
            $payload = json_decode($this->base64UrlDecode($payloadEncoded));
            
            // Verify algorithm is supported
            if (empty($header['alg']) || !isset($this->supportedAlgs[$header['alg']])) {
                return false;
            }
            
            // Verify signature
            $signature = $this->base64UrlDecode($signatureEncoded);
            $expectedSignature = hash_hmac(
                $this->supportedAlgs[$header['alg']],
                $headerEncoded . '.' . $payloadEncoded,
                $this->secretKey,
                true
            );
            
            if (!hash_equals($expectedSignature, $signature)) {
                return false;
            }
            
            // Check token expiration
            if (isset($payload->exp) && $payload->exp < time()) {
                return false;
            }
            
            // Check token not before
            if (isset($payload->nbf) && $payload->nbf > time()) {
                return false;
            }
            
            // Check issued at
            if (isset($payload->iat) && $payload->iat > time()) {
                return false;
            }
            
            return $payload;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Create a signature for the given data
     */
    private function sign($data) {
        return hash_hmac(
            $this->supportedAlgs[$this->algorithm],
            $data,
            $this->secretKey,
            true
        );
    }

    /**
     * Base64Url encode a string
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64Url decode a string
     */
    private function base64UrlDecode($data) {
        $decoded = base64_decode(str_pad(
            strtr($data, '-_', '+/'),
            strlen($data) % 4,
            '=',
            STR_PAD_RIGHT
        ));
        
        if ($decoded === false) {
            throw new Exception('Invalid base64 string');
        }
        
        return $decoded;
    }
}