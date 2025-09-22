<?php
// signup.php - Revised with OOP principles
require_once 'config.php';
require_once 'user.php';
require_once 'validator.php';
require_once 'session_manager.php';

// Initialize classes
$sessionManager = new SessionManager();
$validator = new Validator();
$user = new User($db ?? null);

// If already logged in, redirect to resume
$sessionManager->redirectIfLoggedIn('index.php');

$error = '';
$success = '';
$username = '';
$email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate input using OOP validator
    if (!$validator->validateRegistration($username, $email, $password, $confirmPassword)) {
        $error = $validator->getErrorsAsString();
    } else {
        // Attempt registration
        $result = $user->setUsername($username)
                       ->setEmail($email)
                       ->setPassword($password)
                       ->register();
        
        if ($result['success']) {
            $success = $result['message'] . ' You can now log in.';
            // Clear form data on success
            $username = '';
            $email = '';
        } else {
            $error = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Aila Roshiele Donayre</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <button class="dark-mode-toggle" id="darkModeToggle" aria-label="Toggle dark mode">
        <i class="fas fa-moon"></i>
    </button>

    <div class="auth-container">
        <form class="auth-form" method="POST" action="" id="signupForm">
            <div class="auth-header">
                <h1 class="auth-title">Join Us</h1>
                <p class="auth-subtitle">Create your account to view the resume</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-input"
                    placeholder="Choose a username"
                    value="<?php echo htmlspecialchars($username); ?>"
                    minlength="3"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-input"
                    placeholder="Enter your email address"
                    value="<?php echo htmlspecialchars($email); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-input"
                    placeholder="Create a password"
                    minlength="6"
                    required
                >
                <div class="password-strength" id="passwordStrength"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    class="form-input"
                    placeholder="Confirm your password"
                    minlength="6"
                    required
                >
            </div>

            <button type="submit" class="btn-auth">
                <i class="fas fa-user-plus"></i>
                Create Account
            </button>

            <div class="auth-divider">
                <span>Already have an account?</span>
            </div>

            <div class="auth-link">
                <a href="login.php">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In Instead
                </a>
            </div>

            <div class="form-requirements">
                <div class="requirements-title">Account Requirements:</div>
                <ul class="requirements-list">
                    <li>Username must be at least 3 characters long</li>
                    <li>Valid email address required</li>
                    <li>Password must be at least 6 characters long</li>
                    <li>Passwords must match</li>
                </ul>
            </div>
        </form>
    </div>

    <script src="js/script.js"></script>
    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strengthDiv.textContent = '';
                return;
            }
            
            let strength = 0;
            let feedback = [];
            
            // Length check
            if (password.length >= 6) strength += 1;
            else feedback.push('At least 6 characters');
            
            // Complexity checks
            if (/[a-z]/.test(password)) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            // Display strength
            if (strength < 2) {
                strengthDiv.textContent = 'Weak password';
                strengthDiv.className = 'password-strength strength-weak';
            } else if (strength < 4) {
                strengthDiv.textContent = 'Medium password';
                strengthDiv.className = 'password-strength strength-medium';
            } else {
                strengthDiv.textContent = 'Strong password';
                strengthDiv.className = 'password-strength strength-strong';
            }
        });

        // Password confirmation check
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword.length === 0) {
                this.style.borderColor = 'var(--border-color)';
                return;
            }
            
            if (password === confirmPassword) {
                this.style.borderColor = '#27ae60';
            } else {
                this.style.borderColor = '#e74c3c';
            }
        });
    </script>
    <style>
        .form-requirements {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .requirements-title {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .requirements-list {
            color: var(--text-secondary);
            font-size: 0.8rem;
            line-height: 1.5;
            list-style-type: disc;
            padding-left: 20px;
        }
        
        .requirements-list li {
            margin-bottom: 5px;
        }

        .password-strength {
            margin-top: 5px;
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        .strength-weak { color: #e74c3c; }
        .strength-medium { color: #f39c12; }
        .strength-strong { color: #27ae60; }
    </style>
</body>
</html>