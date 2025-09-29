<?php
class SessionManager {
    
    public function __construct() {
        $this->startSession();
    }
    
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    public function has($key) {
        return isset($_SESSION[$key]);
    }
    
    public function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    public function loginUser($userData) {
        $this->set('user_id', $userData['id'] ?? 'admin');
        $this->set('username', $userData['username']);
        $this->set('email', $userData['email']);
        $this->set('logged_in', true);
        $this->set('login_time', time());
    }
    
    public function isLoggedIn() {
        return $this->get('logged_in', false) === true;
    }
    
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
    
    public function logout() {
        session_unset();
        session_destroy();
    }
    
    public function requireLogin($redirectTo = 'login.php') {
        if (!$this->isLoggedIn()) {
            header("Location: $redirectTo");
            exit;
        }
    }
    
    public function redirectIfLoggedIn($redirectTo = 'index.php') {
        if ($this->isLoggedIn()) {
            header("Location: $redirectTo");
            exit;
        }
    }
    
    public function setFlash($type, $message) {
        $this->set('flash_' . $type, $message);
    }
    
    public function getFlash($type) {
        $message = $this->get('flash_' . $type);
        if ($message) {
            $this->remove('flash_' . $type);
        }
        return $message;
    }
    
    public function updateActivity() {
        $this->set('last_activity', time());
    }
}