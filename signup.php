<?php
require_once 'config.php';
require_once 'user.php';
require_once 'validator.php';
require_once 'session_manager.php';

$sessionManager = new SessionManager();
$validator = new Validator();
$user = new User($db ?? null);

$sessionManager->redirectIfLoggedIn('index.php');

$error = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (!$validator->validateRegistration($username, $email, $password, $confirmPassword)) {
        $error = $validator->getErrorsAsString();
    } else {
        $result = $user->setUsername($username)
                       ->setEmail($email)
                       ->setPassword($password)
                       ->register();
        
        if ($result['success']) {
            $sessionManager->setFlash('success', 'Registration successful! Please log in with your new account.');
            
            header('Location: login.php');
            exit;
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
    <style>
        .password-wrapper {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 5px;
            font-size: 1.1rem;
            transition: color 0.3s ease;
            z-index: 10;
        }
        
        .password-toggle:hover {
            color: var(--accent-color);
        }
        
        .password-toggle:focus {
            outline: 2px solid var(--accent-color);
            outline-offset: 2px;
            border-radius: 4px;
        }
        
        .form-input-with-toggle {
            padding-right: 45px;
        }
        
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

        .auth-divider {
            text-align: center;
            margin: 15px 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <button class="dark-mode-toggle" id="darkModeToggle" aria-label="Toggle dark mode">
        <i class="fas fa-moon"></i>
    </button>

    <div class="auth-container">
        <form class="auth-form" method="POST" action="" id="signupForm">
            <div class="auth-header">
                <h1 class="auth-title">Join Us!</h1>
                <p class="auth-subtitle">Create your account to view the resume</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
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
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input form-input-with-toggle"
                        placeholder="Create a password"
                        minlength="6"
                        required
                    >
                    <button 
                        type="button" 
                        class="password-toggle" 
                        onclick="togglePassword('password', this)"
                        aria-label="Toggle password visibility"
                    >
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="password-strength" id="passwordStrength"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-input form-input-with-toggle"
                        placeholder="Confirm your password"
                        minlength="6"
                        required
                    >
                    <button 
                        type="button" 
                        class="password-toggle" 
                        onclick="togglePassword('confirm_password', this)"
                        aria-label="Toggle password visibility"
                    >
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
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
        </form>
    </div>

    <script src="js/script.js"></script>
    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                button.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                button.setAttribute('aria-label', 'Show password');
            }
        }

        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strengthDiv.textContent = '';
                return;
            }
            
            let strength = 0;
            let feedback = [];
            
            if (password.length >= 6) strength += 1;
            else feedback.push('At least 6 characters');
            
            if (/[a-z]/.test(password)) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
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
</body>
</html>