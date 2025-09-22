<?php
// user.php - User class following OOP principles

class User {
    private $db;
    private $username;
    private $email;
    private $passwordHash;
    
    public function __construct($db = null) {
        $this->db = $db;
    }
    
    /**
     * Set user properties
     */
    public function setUsername($username) {
        $this->username = $this->sanitizeInput($username);
        return $this;
    }
    
    public function setEmail($email) {
        $this->email = $this->sanitizeInput($email);
        return $this;
    }
    
    public function setPassword($password) {
        $this->passwordHash = password_hash($password, PASSWORD_DEFAULT);
        return $this;
    }
    
    /**
     * Get user properties
     */
    public function getUsername() {
        return $this->username;
    }
    
    public function getEmail() {
        return $this->email;
    }
    
    /**
     * Sanitize input data
     */
    private function sanitizeInput($data) {
        return htmlspecialchars(stripslashes(trim($data)));
    }
    
    /**
     * Register a new user in database
     */
    public function register() {
        if (!$this->db) {
            return ['success' => false, 'message' => 'Database connection not available'];
        }
        
        try {
            // Check if username or email already exists
            $checkStmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $checkStmt->execute([$this->username, $this->email]);
            
            if ($checkStmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            
            // Insert new user
            $stmt = $this->db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$this->username, $this->email, $this->passwordHash]);
            
            return ['success' => true, 'message' => 'Registration successful'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Authenticate user (supports both hardcoded admin and database users)
     */
    public function authenticate($username, $password) {
        // Check hardcoded admin credentials first
        if ($username === 'admin' && $password === '1234') {
            return ['success' => true, 'message' => 'Login Successful', 'user' => ['username' => 'admin', 'email' => 'admin@admin.com']];
        }
        
        // If database is available, check database users
        if ($this->db) {
            try {
                $stmt = $this->db->prepare("SELECT id, username, email, password_hash FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $username]);
                
                if ($stmt->rowCount() > 0) {
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (password_verify($password, $user['password_hash'])) {
                        // Update last login
                        $updateStmt = $this->db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                        $updateStmt->execute([$user['id']]);
                        
                        return ['success' => true, 'message' => 'Login Successful', 'user' => $user];
                    }
                }
            } catch (PDOException $e) {
                return ['success' => false, 'message' => 'Login failed: Database error'];
            }
        }
        
        return ['success' => false, 'message' => 'Invalid Username or Password'];
    }
}