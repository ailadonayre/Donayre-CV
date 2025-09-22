<?php
// validator.php - Input validation class following OOP principles

class Validator {
    private $errors = [];
    
    /**
     * Validate required fields
     */
    public function required($value, $fieldName) {
        if (empty(trim($value))) {
            $this->errors[] = "$fieldName is required";
            return false;
        }
        return true;
    }
    
    /**
     * Validate email format
     */
    public function email($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "Please enter a valid email address";
            return false;
        }
        return true;
    }
    
    /**
     * Validate password length
     */
    public function password($password, $minLength = 6) {
        if (strlen($password) < $minLength) {
            $this->errors[] = "Password must be at least $minLength characters long";
            return false;
        }
        return true;
    }
    
    /**
     * Validate username length
     */
    public function username($username, $minLength = 3) {
        if (strlen($username) < $minLength) {
            $this->errors[] = "Username must be at least $minLength characters long";
            return false;
        }
        return true;
    }
    
    /**
     * Check if passwords match
     */
    public function passwordsMatch($password, $confirmPassword) {
        if ($password !== $confirmPassword) {
            $this->errors[] = "Passwords do not match";
            return false;
        }
        return true;
    }
    
    /**
     * Validate login form (username and password required)
     */
    public function validateLogin($username, $password) {
        $this->errors = []; // Reset errors
        
        if (!$this->required($username, 'Username') || !$this->required($password, 'Password')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate registration form
     */
    public function validateRegistration($username, $email, $password, $confirmPassword = null) {
        $this->errors = []; // Reset errors
        
        $isValid = true;
        
        if (!$this->required($username, 'Username')) $isValid = false;
        if (!$this->required($email, 'Email')) $isValid = false;
        if (!$this->required($password, 'Password')) $isValid = false;
        
        if ($confirmPassword !== null && !$this->required($confirmPassword, 'Confirm Password')) {
            $isValid = false;
        }
        
        // Additional validations only if required fields are present
        if (!empty($username) && !$this->username($username)) $isValid = false;
        if (!empty($email) && !$this->email($email)) $isValid = false;
        if (!empty($password) && !$this->password($password)) $isValid = false;
        if ($confirmPassword !== null && !empty($password) && !empty($confirmPassword) && !$this->passwordsMatch($password, $confirmPassword)) {
            $isValid = false;
        }
        
        return $isValid;
    }
    
    /**
     * Get validation errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Get first error message
     */
    public function getFirstError() {
        return !empty($this->errors) ? $this->errors[0] : '';
    }
    
    /**
     * Check if there are any errors
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Get all errors as a formatted string
     */
    public function getErrorsAsString($separator = '. ') {
        return implode($separator, $this->errors);
    }
}