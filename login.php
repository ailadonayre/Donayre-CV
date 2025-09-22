<?php
// login.php - Revised with OOP principles
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate input using OOP validator
    if (!$validator->validateLogin($username, $password)) {
        $error = "All fields are required!";
    } else {
        // Attempt authentication
        $result = $user->authenticate($username, $password);
        
        if ($result['success']) {
            // Login successful - set session and redirect
            $sessionManager->loginUser($result['user']);
            $sessionManager->setFlash('success', $result['message']);
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
</head>
<body>
    <button class="dark-mode-toggle" id="darkModeToggle" aria-label="Toggle dark mode">
        <i class="fas fa-moon"></i>
    </button>

    <div class="auth-container">
        <form class="auth-form" method="POST" action="">
            <div class="auth-header">
                <h1 class="auth-title">Welcome!</h1>
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
                    placeholder="Enter your username"
                    value="<?php echo htmlspecialchars($username); ?>"
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
                Log in
            </button>

            <div class="auth-link">
                <a href="signup.php">
                    <i class="fas fa-user-plus"></i>
                    Create new account
                </a>
            </div>
        </form>
    </div>

    <script src="js/script.js"></script>
</body>
</html>