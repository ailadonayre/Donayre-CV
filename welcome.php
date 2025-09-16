<?php
// welcome.php - Landing page
require_once 'auth.php';

// If already logged in, redirect to resume
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Aila Roshiele Donayre</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .landing-container {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .landing-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
        }
        
        .landing-content {
            text-align: center;
            color: var(--white);
            z-index: 2;
            position: relative;
            max-width: 600px;
            padding: 40px;
        }
        
        .landing-logo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 30px;
            border: 4px solid var(--white);
            object-fit: cover;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .landing-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .landing-subtitle {
            font-size: 1.5rem;
            font-weight: 400;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .landing-description {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 40px;
            opacity: 0.8;
        }
        
        .landing-actions {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-landing {
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-primary {
            background: var(--accent-color);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background: var(--accent-hover);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(239, 35, 60, 0.4);
        }
        
        .btn-secondary {
            background: transparent;
            color: var(--white);
            border: 2px solid var(--white);
        }
        
        .btn-secondary:hover {
            background: var(--white);
            color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 255, 255, 0.3);
        }
        
        .landing-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-top: 60px;
        }
        
        .feature-item {
            text-align: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            color: var(--accent-color);
            margin-bottom: 15px;
        }
        
        .feature-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .feature-description {
            font-size: 0.9rem;
            opacity: 0.8;
            line-height: 1.5;
        }
        
        @media (max-width: 768px) {
            .landing-title {
                font-size: 2.5rem;
            }
            
            .landing-subtitle {
                font-size: 1.3rem;
            }
            
            .landing-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-landing {
                width: 250px;
                justify-content: center;
            }
            
            .landing-features {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .landing-content {
                padding: 20px;
            }
            
            .landing-title {
                font-size: 2rem;
            }
            
            .landing-logo {
                width: 120px;
                height: 120px;
            }
        }
    </style>
</head>
<body>
    <button class="dark-mode-toggle" id="darkModeToggle" aria-label="Toggle dark mode">
        <i class="fas fa-moon"></i>
    </button>

    <div class="landing-container">
        <div class="landing-content">
            <img src="assets/img/arcd.jpeg" alt="Aila Roshiele Donayre" class="landing-logo">
            
            <h1 class="landing-title">Aila Roshiele Donayre</h1>
            <h2 class="landing-subtitle">Computer Science Student</h2>
            
            <p class="landing-description">
                Welcome to my digital resume! I'm a passionate Computer Science student with expertise in AI, 
                machine learning, and software development. Explore my achievements, projects, and professional journey.
            </p>
            
            <div class="landing-actions">
                <a href="login.php" class="btn-landing btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    View Resume
                </a>
                <a href="signup.php" class="btn-landing btn-secondary">
                    <i class="fas fa-user-plus"></i>
                    Create Account
                </a>
            </div>
            
            <div class="landing-features">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3 class="feature-title">AI Engineer</h3>
                    <p class="feature-description">
                        Specialized in machine learning, data science, and AI-powered solutions for real-world problems.
                    </p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <h3 class="feature-title">Award Winner</h3>
                    <p class="feature-description">
                        Multiple competition wins including IoT conferences and international sustainability challenges.
                    </p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-code"></i>
                    </div>
                    <h3 class="feature-title">Full Stack Developer</h3>
                    <p class="feature-description">
                        Proficient in modern web technologies, databases, and multimedia design tools.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>