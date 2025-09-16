<?php
// login.php
require_once 'auth.php';

// If already logged in, redirect to resume
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_POST) {
    $username = validateInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $result = loginUser($username, $password);
        if ($result['success']) {
            header('Location: index.php');
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
    <title>Login - Aila Roshiele Donayre</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--accent-color), var(--accent-hover));
            position: relative;
            overflow: hidden;
        }
        
        .auth-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.1;
        }
        
        .auth-form {
            background: var(--bg-primary);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 1;
            border: 1px solid var(--border-color);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .auth-title {
            color: var(--accent-color);
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .auth-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
            font-weight: 500;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-label {
            display: block;
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            background: var(--bg-secondary);
            color: var(--text-primary);
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 35, 60, 0.2);
        }
        
        .form-input::placeholder {
            color: var(--text-muted);
            font-weight: 400;
        }
        
        .btn-auth {
            width: 100%;
            padding: 15px;
            background: var(--accent-color);
            color: var(--white);
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-auth:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 35, 60, 0.3);
        }
        
        .btn-auth:active {
            transform: translateY(0);
        }
        
        .auth-divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
        }
        
        .auth-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--border-color);
        }
        
        .auth-divider span {
            background: var(--bg-primary);
            padding: 0 15px;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .auth-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .auth-link a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .auth-link a:hover {
            color: var(--accent-hover);
            text-decoration: underline;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
        }
        
        .alert-error {
            background: rgba(239, 35, 60, 0.1);
            color: var(--accent-color);
            border: 1px solid rgba(239, 35, 60, 0.3);
        }
        
        .alert-success {
            background: rgba(46, 204, 113, 0.1);
            color: #27ae60;
            border: 1px solid rgba(46, 204, 113, 0.3);
        }
        
        .auth-demo {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            text-align: center;
        }
        
        .demo-title {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .demo-credentials {
            color: var(--text-secondary);
            font-size: 0.8rem;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <button class="dark-mode-toggle" id="darkModeToggle" aria-label="Toggle dark mode">
        <i class="fas fa-moon"></i>
    </button>

    <div class="auth-container">
        <form class="auth-form" method="POST" action="">
            <div class="auth-header">
                <h1 class="auth-title">Welcome Back</h1>
                <p class="auth-subtitle">Sign in to view the resume</p>
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
                <label for="username" class="form-label">Username or Email</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-input"
                    placeholder="Enter your username or email"
                    value="<?php echo htmlspecialchars($username ?? ''); ?>"
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
                    placeholder="Enter your password"
                    required
                >
            </div>

            <button type="submit" class="btn-auth">
                <i class="fas fa-sign-in-alt"></i>
                Sign In
            </button>

            <div class="auth-divider">
                <span>Don't have an account?</span>
            </div>

            <div class="auth-link">
                <a href="signup.php">
                    <i class="fas fa-user-plus"></i>
                    Create New Account
                </a>
            </div>

            <div class="auth-demo">
                <div class="demo-title">Demo Access</div>
                <div class="demo-credentials">
                    Create an account or use: demo@test.com / demo123<br>
                    <small>(Create this account via signup if it doesn't exist)</small>
                </div>
            </div>
        </form>
    </div>

    <script src="js/script.js"></script>
</body>
</html>