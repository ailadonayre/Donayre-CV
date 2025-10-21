<?php
require_once 'config.php';

// Get user ID from URL
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($userId <= 0) {
    die("Invalid user ID");
}

// Fetch resume data using parameterized query
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("Resume not found");
    }
} catch (PDOException $e) {
    die("Error loading resume: " . htmlspecialchars($e->getMessage()));
}

// Helper function for safe output
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Set defaults if fields are empty
$name = e($user['fullname']) ?: 'User';
$title = e($user['title']) ?: 'Professional';
$email = e($user['email']);
$contact = e($user['contact']);
$address = e($user['address']);
$age = e($user['age']);
$summary = e($user['summary']);
$skills = e($user['skills']);
$education = e($user['education']);
$experience = e($user['experience']);
$linkedin = e($user['linkedin']);
$github = e($user['github']);
$profile_image = e($user['profile_image']) ?: 'assets/img/default-avatar.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $name; ?> - Resume</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/resume.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <button class="dark-mode-toggle" id="darkModeToggle" aria-label="Toggle dark mode">
        <i class="fa-solid fa-moon"></i>
    </button>

    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <h1 class="name"><?php echo $name; ?></h1>
                    <h2 class="title"><?php echo $title; ?></h2>
                </div>
                <div class="header-right">
                    <button onclick="window.print()" class="btn-print">
                        <i class="fa-solid fa-print"></i>
                        <span>Print Resume</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="content-grid">
                <!-- Left Column -->
                <div class="left-column">
                    <!-- Profile Card -->
                    <div class="card profile-card">
                        <div class="card-header">
                            <span class="header-label">Profile</span>
                        </div>
                        <div class="profile-section">
                            <?php if ($profile_image): ?>
                            <div class="profile-image-container">
                                <img src="<?php echo $profile_image; ?>" alt="<?php echo $name; ?>" class="profile-image" onerror="this.src='assets/img/default-avatar.png'">
                            </div>
                            <?php endif; ?>
                            
                            <div class="profile-info">
                                <h3><span class="name-highlight"><?php echo strtoupper($name); ?></span></h3>
                                <?php if ($title): ?>
                                <p class="profile-subtitle"><?php echo $title; ?></p>
                                <?php endif; ?>
                                
                                <div class="profile-details">
                                    <?php if ($age): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Age</span>
                                        <span class="detail-value"><?php echo $age; ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($address): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Address</span>
                                        <span class="detail-value"><?php echo $address; ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($email): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Email</span>
                                        <span class="detail-value"><?php echo $email; ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($contact): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Contact</span>
                                        <span class="detail-value"><?php echo $contact; ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($linkedin || $github): ?>
                                <div class="profile-social">
                                    <?php if ($linkedin): ?>
                                    <a href="<?php echo $linkedin; ?>" target="_blank" class="social-icon" rel="noopener noreferrer">
                                        <i class="fa-brands fa-linkedin"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($github): ?>
                                    <a href="<?php echo $github; ?>" target="_blank" class="social-icon" rel="noopener noreferrer">
                                        <i class="fa-brands fa-github"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($summary): ?>
                    <!-- Summary Card -->
                    <div class="card">
                        <h3 class="card-title">ABOUT ME</h3>
                        <div class="profile-section">
                            <p class="public-text"><?php echo nl2br($summary); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($skills): ?>
                    <!-- Skills Card -->
                    <div class="card">
                        <h3 class="card-title">SKILLS</h3>
                        <div class="tech-content">
                            <div class="tech-tags">
                                <?php
                                $skillsArray = array_filter(array_map('trim', explode(',', $skills)));
                                foreach ($skillsArray as $skill):
                                ?>
                                <span class="tech-tag"><?php echo e($skill); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column -->
                <div class="right-column">
                    <?php if ($education): ?>
                    <!-- Education Card -->
                    <div class="card education-card">
                        <h3 class="card-title">EDUCATION</h3>
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <p class="public-text"><?php echo nl2br($education); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($experience): ?>
                    <!-- Experience Card -->
                    <div class="card experience-card">
                        <h3 class="card-title">EXPERIENCE</h3>
                        <div class="experience-content">
                            <div class="experience-section">
                                <div class="experience-item">
                                    <p class="public-text"><?php echo nl2br($experience); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="js/script.js"></script>
</body>
</html>