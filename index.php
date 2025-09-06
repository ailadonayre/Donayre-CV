<?php
// Personal Information - Easy to customize
$name = "Aila Roshiele Donayre";
$title = "Computer Science Student";
$summary = "Passionate computer science student with strong foundation in programming, web development, and emerging technologies. Eager to apply academic knowledge to real-world projects and contribute to innovative solutions.";
$email = "ailaroshieledonayre@gmail.com";
$phone = "+63 945 297 1404";
$linkedin = "https://www.linkedin.com/in/aila-roshiele-donayre/";
$github = "https://github.com/ailadonayre";
$website = "https://johndoe.dev";
$address = "Batangas City, Batangas, Philippines 4200";
$age = "20";
$profile_image = "assets/img/arcd.jpeg";
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
    <!-- Dark Mode Toggle -->
    <button class="dark-mode-toggle" id="darkModeToggle" aria-label="Toggle dark mode">
        <i class="fas fa-moon"></i>
    </button>

    <!-- Header Section -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <h1 class="name"><?php echo $name; ?></h1>
                    <h2 class="title"><?php echo $title; ?></h2>
                </div>
                <div class="header-right">
                    <button onclick="printPDF()" class="btn-print">
                        <i class="fas fa-print"></i>
                        <span>Print CV</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div class="content-grid">
                
                <!-- Left Column -->
                <div class="left-column">
                    
                    <!-- Profile Card -->
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

                    <!-- Education Card -->
                    <div class="card education-card">
                        <h3 class="card-title">EDUCATION</h3>
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4 class="timeline-title">BS in Computer Science</h4>
                                    <p class="timeline-subtitle">BATANGAS STATE UNIVERSITY - TNEU ALANGILAN</p>
                                    <span class="timeline-date">2020 - Present</span>
                                    <p class="timeline-description">Currently pursuing Bachelor's degree with focus on software engineering, data structures, algorithms, and web development.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Technologies Card -->
                    <div class="card technologies-card">
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
                                        <span class="tech-tag">Pythom</span>
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

                <!-- Right Column -->
                <div class="right-column">
                    
                    <!-- Experience Card -->
                    <div class="card experience-card">
                        <h3 class="card-title">EXPERIENCE</h3>
                        <div class="experience-content">
                            <div class="experience-section">
                                <div class="experience-item">
                                    <div class="experience-header">
                                        <h4 class="experience-title">Project Team Lead</h4>
                                        <span class="experience-date">2023</span>
                                    </div>
                                    <p class="experience-company">Campus Management System Project</p>
                                    <p class="experience-description">Led a team of 5 students in developing a comprehensive web application for campus management, implementing user authentication, database design, and responsive UI/UX.</p>
                                    <div class="experience-skills">
                                        <span class="skill-tag">Leadership</span>
                                        <span class="skill-tag">Project Management</span>
                                        <span class="skill-tag">Full-Stack Development</span>
                                    </div>
                                </div>

                                <div class="experience-item">
                                    <div class="experience-header">
                                        <h4 class="experience-title">Student Organization Officer</h4>
                                        <span class="experience-date">2022 - 2023</span>
                                    </div>
                                    <p class="experience-company">Computer Science Society</p>
                                    <p class="experience-description">Organized technical workshops, coding competitions, and mentorship programs for fellow students. Coordinated events with 100+ participants.</p>
                                    <div class="experience-skills">
                                        <span class="skill-tag">Event Management</span>
                                        <span class="skill-tag">Public Speaking</span>
                                        <span class="skill-tag">Mentoring</span>
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
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Achievements Card -->
                    <div class="card achievements-card">
                        <h3 class="card-title">ACHIEVEMENTS</h3>
                        <div class="achievements-list">
                            <div class="achievement-item">
                                <div class="achievement-icon">
                                    <i class="fas fa-trophy"></i>
                                </div>
                                <div class="achievement-content">
                                    <h4 class="achievement-title">Dean's List</h4>
                                    <p class="achievement-description">Consistently maintained high academic performance throughout university studies.</p>
                                    <span class="achievement-date">2022 - 2023</span>
                                </div>
                            </div>
                            <div class="achievement-item">
                                <div class="achievement-icon">
                                    <i class="fas fa-code"></i>
                                </div>
                                <div class="achievement-content">
                                    <h4 class="achievement-title">Coding Competition Winner</h4>
                                    <p class="achievement-description">First place in university's annual programming contest for algorithm design.</p>
                                    <span class="achievement-date">2023</span>
                                </div>
                            </div>
                            <div class="achievement-item">
                                <div class="achievement-icon">
                                    <i class="fas fa-certificate"></i>
                                </div>
                                <div class="achievement-content">
                                    <h4 class="achievement-title">Web Development Certification</h4>
                                    <p class="achievement-description">Completed comprehensive full-stack web development certification program.</p>
                                    <span class="achievement-date">2023</span>
                                </div>
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