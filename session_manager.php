<?php
// session_manager.php - Session management class following OOP principles

class SessionManager {
    
    public function __construct() {
        $this->startSession();
    }
    
    /**
     * Start session if not already started
     */
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Set session variable
     */
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session variable
     */
    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session variable exists
     */
    public function has($key) {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session variable
     */
    public function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    /**
     * Login user - set session variables
     */
    public function loginUser($userData) {
        $this->set('user_id', $userData['id'] ?? 'admin');
        $this->set('username', $userData['username']);
        $this->set('email', $userData['email']);
        $this->set('logged_in', true);
        $this->set('login_time', time());
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return $this->get('logged_in', false) === true;
    }
    
    /**
     * Get current user data
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $this->get('user_id'),
            'username' => $this->get('username'),
            'email' => $this->get('email'),
            'login_time' => $this->get('login_time')
        ];
    }
    
    /**
     * Logout user - destroy session
     */
    public function logout() {
        session_unset();
        session_destroy();
    }
    
    /**
     * Redirect if not logged in
     */
    public function requireLogin($redirectTo = 'login.php') {
        if (!$this->isLoggedIn()) {
            header("Location: $redirectTo");
            exit;
        }
    }
    
    /**
     * Redirect if already logged in
     */
    public function redirectIfLoggedIn($redirectTo = 'index.php') {
        if ($this->isLoggedIn()) {
            header("Location: $redirectTo");
            exit;
        }
    }
    
    /**
     * Set flash message
     */
    public function setFlash($type, $message) {
        $this->set('flash_' . $type, $message);
    }
    
    /**
     * Get and remove flash message
     */
    public function getFlash($type) {
        $message = $this->get('flash_' . $type);
        if ($message) {
            $this->remove('flash_' . $type);
        }
        return $message;
    }
    
    /**
     * Update session activity
     */
    public function updateActivity() {
        $this->set('last_activity', time());
    }
}