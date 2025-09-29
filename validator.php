<?php

class Validator {
    private $errors = [];
    
    public function required($value, $fieldName) {
        if (empty(trim($value))) {
            $this->errors[] = "$fieldName is required";
            return false;
        }
        return true;
    }
    
    public function email($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "Please enter a valid email address";
            return false;
        }
        return true;
    }
    
    public function password($password, $minLength = 6) {
        if (strlen($password) < $minLength) {
            $this->errors[] = "Password must be at least $minLength characters long";
            return false;
        }
        return true;
    }
    
    public function username($username, $minLength = 3) {
        if (strlen($username) < $minLength) {
            $this->errors[] = "Username must be at least $minLength characters long";
            return false;
        }
        return true;
    }
    
    public function passwordsMatch($password, $confirmPassword) {
        if ($password !== $confirmPassword) {
            $this->errors[] = "Passwords do not match";
            return false;
        }
        return true;
    }
    
    public function validateLogin($username, $password) {
        $this->errors = []; // Reset errors
        
        if (!$this->required($username, 'Username') || !$this->required($password, 'Password')) {
            return false;
        }
        
        return true;
    }
    
    public function validateRegistration($username, $email, $password, $confirmPassword = null) {
        $this->errors = [];
        
        $isValid = true;
        
        if (!$this->required($username, 'Username')) $isValid = false;
        if (!$this->required($email, 'Email')) $isValid = false;
        if (!$this->required($password, 'Password')) $isValid = false;
        
        if ($confirmPassword !== null && !$this->required($confirmPassword, 'Confirm Password')) {
            $isValid = false;
        }
        
        if (!empty($username) && !$this->username($username)) $isValid = false;
        if (!empty($email) && !$this->email($email)) $isValid = false;
        if (!empty($password) && !$this->password($password)) $isValid = false;
        if ($confirmPassword !== null && !empty($password) && !empty($confirmPassword) && !$this->passwordsMatch($password, $confirmPassword)) {
            $isValid = false;
        }
        
        return $isValid;
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    public function getFirstError() {
        return !empty($this->errors) ? $this->errors[0] : '';
    }
    
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    public function getErrorsAsString($separator = '. ') {
        return implode($separator, $this->errors);
    }
}