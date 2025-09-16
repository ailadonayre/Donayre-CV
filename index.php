<?php
// index.php - Protected resume page
require_once 'auth.php';

// Require login to access this page
requireLogin();

$name = "Aila Roshiele Donayre";
$title = "Computer Science Student";
$email = "ailaroshieledonayre@gmail.com";
$linkedin = "https://www.linkedin.com/in/aila-roshiele-donayre/";
$github = "https://github.com/ailadonayre";
$address = "Batangas City, Batangas, Philippines 4200";
$age = "20";
$profile_image = "assets/img/arcd.jpeg";

require_once 'config.php';

try {
    $stmt = $db->query("SELECT version()");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Query failed: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $name; ?> - Resume</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <button class="dark-mode-toggle" id="darkModeToggle" aria-label="Toggle dark mode">
        <i class="fas fa-moon"></i>
    </button>

    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <h1 class="name"><?php echo $name; ?></h1>
                    <h2 class="title"><?php echo $title; ?></h2>
                </div>
                <div class="header-right">
                    <button onclick="downloadPDF()" class="btn-print">
                    <i class="fas fa-download"></i>
                    <span>Download CV</span>
                </button>

                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="content-grid">
                
                <div class="left-column">
                    
                    <div class="card profile-card">
                        <div class="card-header">
                            <span class="header-label">Welcome</span>
                        </div>
                        <div class="profile-section">
                            <div class="profile-image-container">
                                <img src="<?php echo $profile_image; ?>" alt="<?php echo $name; ?>" class="profile-image">
                            </div>
                            <div class="profile-info">
                                <h3>I'm <span class="name-highlight"><?php echo strtoupper($name); ?></span></h3>
                                <p class="profile-subtitle"><?php echo $title; ?></p>
                                <div class="profile-details">
                                    <div class="detail-item">
                                        <span class="detail-label">Age</span>
                                        <span class="detail-value"><?php echo $age; ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Address</span>
                                        <span class="detail-value"><?php echo $address; ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Email</span>
                                        <span class="detail-value"><?php echo $email; ?></span>
                                    </div>
                                </div>
                                <div class="profile-social">
                                    <a href="<?php echo $linkedin; ?>" target="_blank" class="social-icon">
                                        <i class="fab fa-linkedin"></i>
                                    </a>
                                    <a href="<?php echo $github; ?>" target="_blank" class="social-icon">
                                        <i class="fab fa-github"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card experience-card">
                        <h3 class="card-title">EXPERIENCE</h3>
                        <div class="experience-content">
                            <div class="experience-section">
                                <div class="experience-item">
                                    <div class="experience-header">
                                        <h4 class="experience-title">AI Engineer</h4>
                                        <span class="experience-date">2025 - Present</span>
                                    </div>
                                    <p class="experience-company">CIVILIAN</p>
                                    <p class="experience-description">Designed AI models for risk forecasting and prediction, applying machine learning techniques to analyze data, identify patterns, and anticipate potential issues.</p>
                                    <div class="experience-skills">
                                        <span class="skill-tag">Machine Learning</span>
                                        <span class="skill-tag">Data Visualization</span>
                                        <span class="skill-tag">Data Science and Analytics</span>
                                    </div>
                                </div>

                                <div class="experience-item">
                                    <div class="experience-header">
                                        <h4 class="experience-title">Student Organization Officer</h4>
                                        <span class="experience-date">2023 - Present</span>
                                    </div>
                                    <p class="experience-company">Various campus-wide organizations</p>
                                    <p class="experience-description">Produced publication materials, hosted events, and organized competitions for fellow students. Coordinated events with 100+ participants.</p>
                                    <div class="experience-skills">
                                        <span class="skill-tag">Social Media Management</span>
                                        <span class="skill-tag">Graphic Design</span>
                                        <span class="skill-tag">Hosting</span>
                                    </div>
                                </div>

                                <div class="experience-highlights">
                                    <div class="highlight-item">
                                        <i class="fas fa-code"></i>
                                        <span>Academic Projects</span>
                                    </div>
                                    <div class="highlight-item">
                                        <i class="fas fa-users"></i>
                                        <span>Team Leadership</span>
                                    </div>
                                    <div class="highlight-item">
                                        <i class="fas fa-lightbulb"></i>
                                        <span>Problem Solving</span>
                                    </div>
                                    <div class="highlight-item">
                                        <i class="fas fa-sync-alt"></i>
                                        <span>Adaptability</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>                    
                </div>
                
                <div class="right-column">

                    <div class="card education-card">
                        <h3 class="card-title">EDUCATION</h3>
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4 class="timeline-title">BS in Computer Science</h4>
                                    <p class="timeline-subtitle">BATANGAS STATE UNIVERSITY - TNEU ALANGILAN</p>
                                    <span class="timeline-date">2023 - Present</span>
                                    <p class="timeline-description">Currently pursuing a Bachelor's degree with focus on software engineering, data structures, algorithms, and web development.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card achievements-card">
                        <h3 class="card-title">ACHIEVEMENTS</h3>
                        <div class="achievements-list">
                            <div class="achievement-item">
                                <div class="achievement-icon">
                                    <i class="fas fa-code"></i>
                                </div>
                                <div class="achievement-content">
                                    <h4 class="achievement-title">IoT Conference Philippines 2025</h4>
                                    <p class="achievement-description">Emerged as the third runner-up with CIVILIAN: An AI-Powered Disaster Resilience Platform using an IoT Mesh with ESP32 Sensors and LoRa Connectivity.</p>
                                    <span class="achievement-date">2025</span>
                                </div>
                            </div>
                            <div class="achievement-item">
                                <div class="achievement-icon">
                                    <i class="fas fa-certificate"></i>
                                </div>
                                <div class="achievement-content">
                                    <h4 class="achievement-title">Joint Humanitarian Entrepreneurship Summer Academy 2025</h4>
                                    <p class="achievement-description">Proposed a rainwater harvesting system in Tingloy, Batangas during a two-week service-learning program in collaboration with Lingnan University of Hong Kong students.</p>
                                    <span class="achievement-date">2025</span>
                                </div>
                            </div>
                            <div class="achievement-item">
                                <div class="achievement-icon">
                                    <i class="fas fa-trophy"></i>
                                </div>
                                <div class="achievement-content">
                                    <h4 class="achievement-title">Sustainability Expo 2024 Hackathon x Circular Innovation Challenge</h4>
                                    <p class="achievement-description">Competed as a Top 7 finalist in Southeast Asia's biggest sustainability expo in Bangkok, Thailand. Presented APULA: An IoT-Based Early Warning and Response System for Forest Fires using Fish Scales-Based Foam Extinguishers.</p>
                                    <span class="achievement-date">2024</span>
                                </div>
                            </div>
                            <div class="achievement-item">
                                <div class="achievement-icon">
                                    <i class="fas fa-medal"></i>
                                </div>
                                <div class="achievement-content">
                                    <h4 class="achievement-title">Philippine Robotics Olympiad 2022</h4>
                                    <p class="achievement-description">Finished second place with Project ISkan: A Deep Learning-Based Robotic Solution for Skin Test Diagnosis through Image Analysis.</p>
                                    <span class="achievement-date">2022</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>               
            </div>

            <div class="card tech-card">
                <h3 class="card-title">TECHNOLOGIES</h3>
                <div class="tech-content">
                    <div class="tech-grid">
                        <div class="tech-category">
                            <h4>Frontend</h4>
                            <div class="tech-tags">
                                <span class="tech-tag">HTML5</span>
                                <span class="tech-tag">CSS3</span>
                                <span class="tech-tag">JavaScript</span>
                            </div>
                        </div>
                        <div class="tech-category">
                            <h4>Backend</h4>
                            <div class="tech-tags">
                                <span class="tech-tag">Python</span>
                                <span class="tech-tag">C++</span>
                                <span class="tech-tag">C#</span>
                                <span class="tech-tag">Java</span>
                            </div>
                        </div>
                        <div class="tech-category">
                            <h4>Tools</h4>
                            <div class="tech-tags">
                                <span class="tech-tag">MySQL</span>
                                <span class="tech-tag">Git</span>
                                <span class="tech-tag">VS Code</span>
                            </div>
                        </div>
                        <div class="tech-category">
                            <h4>Multimedia</h4>
                            <div class="tech-tags">
                                <span class="tech-tag">Adobe Photoshop</span>
                                <span class="tech-tag">Adobe Premiere Pro</span>
                                <span class="tech-tag">Figma</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="js/script.js"></script>
</body>
</html>