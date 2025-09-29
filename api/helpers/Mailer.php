<?php
/**
 * Mailer Class
 * Handles sending emails
 */
class Mailer {
    /**
     * Send an email
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $message Email body
     * @param string $from Sender email (optional)
     * @return bool True on success, false on failure
     */
    public function send($to, $subject, $message, $from = null) {
        if ($from === null) {
            $from = 'noreply@' . $_SERVER['HTTP_HOST'];
        }
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . $from,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        return mail($to, $subject, $message, implode("\r\n", $headers));
    }
    
    /**
     * Send verification email
     * @param string $email User's email
     * @param string $token Verification token
     * @return bool True on success, false on failure
     */
    public function sendVerificationEmail($email, $token) {
        $verificationUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api/auth/verify/' . $token;
        
        $subject = 'Verify Your Email Address';
        $message = "
            <h2>Email Verification</h2>
            <p>Thank you for registering. Please click the link below to verify your email address:</p>
            <p><a href='$verificationUrl'>Verify Email</a></p>
            <p>If you did not create an account, please ignore this email.</p>
        ";
        
        return $this->send($email, $subject, $message);
    }
    
    /**
     * Send password reset email
     * @param string $email User's email
     * @param string $token Reset token
     * @return bool True on success, false on failure
     */
    public function sendPasswordResetEmail($email, $token) {
        $resetUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/reset-password?token=' . $token;
        
        $subject = 'Password Reset Request';
        $message = "
            <h2>Password Reset</h2>
            <p>You have requested to reset your password. Click the link below to set a new password:</p>
            <p><a href='$resetUrl'>Reset Password</a></p>
            <p>If you did not request a password reset, please ignore this email.</p>
            <p>This link will expire in 1 hour.</p>
        ";
        
        return $this->send($email, $subject, $message);
    }
}
