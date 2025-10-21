<?php
require_once 'config.php';
require_once 'session_manager.php';

// Check database connection
if (!isset($db) || !$db) {
    die("Database connection failed. Please check config.php");
}

$sessionManager = new SessionManager();

// Determine which user's resume to display
$viewingUserId = null;
$isOwner = false;

// Check if viewing a specific user's resume via URL parameter
if (isset($_GET['id'])) {
    $viewingUserId = (int)$_GET['id'];
} elseif ($sessionManager->isLoggedIn()) {
    // Logged-in user viewing their own dashboard
    $currentUser = $sessionManager->getCurrentUser();
    $viewingUserId = $currentUser['id'];
    $isOwner = true;
} else {
    // Not logged in and no ID specified - redirect to login
    header('Location: login.php');
    exit;
}

// Security: Validate user ID
if ($viewingUserId <= 0) {
    die("Invalid user ID");
}

// Get success message for owner only
$successMessage = $isOwner ? $sessionManager->getFlash('success') : null;

// Helper function for safe output
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Fetch user data
$hasResumeData = false;
$userData = [];
$socialLinks = [];
$educations = [];
$experiences = [];
$achievements = [];
$techCategories = [];

try {
    // Fetch basic user info
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$viewingUserId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        die("User not found");
    }
    
    // Check if user has ANY resume data
    $hasResumeData = !empty($userData['fullname']) || !empty($userData['title']);
    
    // Fetch social links
    $stmt = $db->prepare("SELECT * FROM social_links WHERE user_id = ? ORDER BY display_order");
    $stmt->execute([$viewingUserId]);
    $socialLinks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch education
    $stmt = $db->prepare("SELECT * FROM education WHERE user_id = ? ORDER BY display_order");
    $stmt->execute([$viewingUserId]);
    $educations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch experience with traits
    $stmt = $db->prepare("SELECT * FROM experience WHERE user_id = ? ORDER BY display_order");
    $stmt->execute([$viewingUserId]);
    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch traits for each experience
    foreach ($experiences as &$exp) {
        $stmt = $db->prepare("SELECT * FROM experience_traits WHERE experience_id = ?");
        $stmt->execute([$exp['id']]);
        $exp['traits'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Fetch achievements
    $stmt = $db->prepare("SELECT * FROM achievements WHERE user_id = ? ORDER BY display_order");
    $stmt->execute([$viewingUserId]);
    $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch technologies
    $stmt = $db->prepare("SELECT * FROM tech_categories WHERE user_id = ? ORDER BY display_order");
    $stmt->execute([$viewingUserId]);
    $techCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch technologies for each category
    foreach ($techCategories as &$cat) {
        $stmt = $db->prepare("SELECT * FROM technologies WHERE category_id = ? ORDER BY display_order");
        $stmt->execute([$cat['id']]);
        $cat['technologies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Update hasResumeData based on comprehensive check
    $hasResumeData = !empty($userData['fullname']) || 
                     !empty($educations) || 
                     !empty($experiences) || 
                     !empty($achievements) || 
                     !empty($techCategories);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    // More helpful error for debugging
    if (ini_get('display_errors')) {
        die("Error loading resume data: " . htmlspecialchars($e->getMessage()));
    }
    die("Error loading resume data. Please contact support.");
}

// Set display values with fallbacks
$name = e($userData['fullname']) ?: 'User';
$title = e($userData['title']) ?: 'Professional';
$currentUser = $isOwner ? $sessionManager->getCurrentUser() : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $name; ?> - Resume</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Empty state styles */
        .empty-state {
            background: var(--bg-primary);
            border-radius: 16px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 4px 20px var(--shadow-light);
            border: 2px dashed var(--border-color);
            margin: 40px auto;
            max-width: 600px;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            color: var(--accent-color);
            margin-bottom: 20px;
        }
        
        .empty-state-title {
            color: var(--text-primary);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .empty-state-description {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .btn-create-resume {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--accent-color);
            color: var(--white);
            padding: 15px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-create-resume:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 35, 60, 0.3);
        }
    </style>
</head>
<body>
    <button class="dark-mode-toggle" id="darkModeToggle" aria-label="Toggle dark mode">
        <i class="fa-solid fa-moon"></i>
    </button>

    <?php if ($successMessage): ?>
    <div class="success-notification" id="successNotification">
        <div class="notification-content">
            <i class="fa-solid fa-circle-check"></i>
            <span><?php echo e($successMessage); ?></span>
            <button class="notification-close" onclick="closeNotification()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <h1 class="name"><?php echo $name; ?></h1>
                    <h2 class="title"><?php echo $title; ?></h2>
                    <?php if ($isOwner && $currentUser): ?>
                    <p class="user-welcome">
                        Welcome, <strong><?php echo e($currentUser['username']); ?></strong>! 
                        <a href="edit_resume.php" class="logout-link">
                            <?php echo $hasResumeData ? 'Edit Resume' : 'Create Resume'; ?>
                        </a>
                        <a href="logout.php" class="logout-link">Log out</a>
                    </p>
                    <?php endif; ?>
                </div>
                <?php if ($hasResumeData): ?>
                <div class="header-right">
                    <button onclick="window.print()" class="btn-print">
                        <i class="fa-solid fa-print"></i>
                        <span>Print Resume</span>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <?php if (!$hasResumeData && $isOwner): ?>
                <!-- Empty State for Owner -->
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fa-solid fa-file-circle-plus"></i>
                    </div>
                    <h2 class="empty-state-title">No Resume Yet</h2>
                    <p class="empty-state-description">
                        You haven't created your resume yet. Get started by adding your education, 
                        experience, skills, and achievements to showcase your professional profile.
                    </p>
                    <a href="edit_resume.php" class="btn-create-resume">
                        <i class="fa-solid fa-plus"></i>
                        Create Your Resume
                    </a>
                </div>
            <?php elseif (!$hasResumeData): ?>
                <!-- Empty State for Public View -->
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fa-solid fa-file-slash"></i>
                    </div>
                    <h2 class="empty-state-title">Resume Not Available</h2>
                    <p class="empty-state-description">
                        This user hasn't created their resume yet.
                    </p>
                </div>
            <?php else: ?>
                <!-- Resume Content -->
                <div class="content-grid">
                    <!-- Left Column -->
                    <div class="left-column">
                        <!-- Profile Card -->
                        <div class="card profile-card">
                            <div class="card-header">
                                <span class="header-label">Profile</span>
                            </div>
                            <div class="profile-section">
                                <div class="profile-info">
                                    <h3><span class="name-highlight"><?php echo strtoupper($name); ?></span></h3>
                                    <?php if ($title): ?>
                                    <p class="profile-subtitle"><?php echo $title; ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="profile-details">
                                        <?php if ($userData['age']): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Age</span>
                                            <span class="detail-value"><?php echo e($userData['age']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($userData['address']): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Address</span>
                                            <span class="detail-value"><?php echo e($userData['address']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($userData['email']): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Email</span>
                                            <span class="detail-value"><?php echo e($userData['email']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($userData['contact']): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Contact</span>
                                            <span class="detail-value"><?php echo e($userData['contact']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($socialLinks)): ?>
                                    <div class="profile-social">
                                        <?php foreach ($socialLinks as $link): ?>
                                        <a href="<?php echo e($link['url']); ?>" target="_blank" class="social-icon" rel="noopener noreferrer" title="<?php echo e($link['platform']); ?>">
                                            <i class="fa-brands <?php echo e($link['icon']); ?>"></i>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($userData['profile_summary']): ?>
                        <!-- Summary Card -->
                        <div class="card">
                            <h3 class="card-title">ABOUT ME</h3>
                            <div class="profile-section">
                                <p class="public-text"><?php echo nl2br(e($userData['profile_summary'])); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($experiences)): ?>
                        <!-- Experience Card -->
                        <div class="card experience-card">
                            <h3 class="card-title">EXPERIENCE</h3>
                            <div class="experience-content">
                                <div class="experience-section">
                                    <?php foreach ($experiences as $exp): ?>
                                    <div class="experience-item">
                                        <div class="experience-header">
                                            <h4 class="experience-title"><?php echo e($exp['job_title']); ?></h4>
                                            <?php if ($exp['start_date'] || $exp['end_date']): ?>
                                            <span class="experience-date">
                                                <?php echo e($exp['start_date']); ?>
                                                <?php echo ($exp['start_date'] && $exp['end_date']) ? ' - ' : ''; ?>
                                                <?php echo e($exp['end_date']); ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($exp['company']): ?>
                                        <p class="experience-company"><?php echo e($exp['company']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($exp['description']): ?>
                                        <p class="experience-description"><?php echo nl2br(e($exp['description'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <?php 
                                    // Collect all unique traits
                                    $allTraits = [];
                                    foreach ($experiences as $exp) {
                                        if (!empty($exp['traits'])) {
                                            foreach ($exp['traits'] as $trait) {
                                                $key = $trait['trait_icon'] . '|' . $trait['trait_label'];
                                                $allTraits[$key] = $trait;
                                            }
                                        }
                                    }
                                    ?>
                                    
                                    <?php if (!empty($allTraits)): ?>
                                    <div class="experience-highlights">
                                        <?php foreach ($allTraits as $trait): ?>
                                        <div class="highlight-item">
                                            <i class="fa-solid <?php echo e($trait['trait_icon']); ?>"></i>
                                            <span><?php echo e($trait['trait_label']); ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Right Column -->
                    <div class="right-column">
                        <?php if (!empty($educations)): ?>
                        <!-- Education Card -->
                        <div class="card education-card">
                            <h3 class="card-title">EDUCATION</h3>
                            <div class="timeline">
                                <?php foreach ($educations as $edu): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h4 class="timeline-title"><?php echo e($edu['degree']); ?></h4>
                                        <?php if ($edu['institution']): ?>
                                        <p class="timeline-subtitle"><?php echo e($edu['institution']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($edu['start_date'] || $edu['end_date']): ?>
                                        <span class="timeline-date">
                                            <?php echo e($edu['start_date']); ?>
                                            <?php echo ($edu['start_date'] && $edu['end_date']) ? ' - ' : ''; ?>
                                            <?php echo e($edu['end_date']); ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if ($edu['description']): ?>
                                        <p class="timeline-description"><?php echo nl2br(e($edu['description'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($achievements)): ?>
                        <!-- Achievements Card -->
                        <div class="card achievements-card">
                            <h3 class="card-title">ACHIEVEMENTS</h3>
                            <div class="achievements-list">
                                <?php foreach ($achievements as $ach): ?>
                                <div class="achievement-item">
                                    <div class="achievement-icon">
                                        <i class="fa-solid <?php echo e($ach['icon']); ?>"></i>
                                    </div>
                                    <div class="achievement-content">
                                        <h4 class="achievement-title"><?php echo e($ach['title']); ?></h4>
                                        <?php if ($ach['description']): ?>
                                        <p class="achievement-description"><?php echo nl2br(e($ach['description'])); ?></p>
                                        <?php endif; ?>
                                        <?php if ($ach['achievement_date']): ?>
                                        <span class="achievement-date"><?php echo e($ach['achievement_date']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($techCategories)): ?>
                <!-- Technologies Card (Full Width) -->
                <div class="card tech-card">
                    <h3 class="card-title">TECHNOLOGIES</h3>
                    <div class="tech-content">
                        <div class="tech-grid">
                            <?php foreach ($techCategories as $cat): ?>
                            <div class="tech-category">
                                <h4><?php echo e($cat['category_name']); ?></h4>
                                <div class="tech-tags">
                                    <?php foreach ($cat['technologies'] as $tech): ?>
                                    <span class="tech-tag"><?php echo e($tech['tech_name']); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <script src="js/script.js"></script>
    <script>
        function closeNotification() {
            const notification = document.getElementById('successNotification');
            if (notification) {
                notification.style.animation = 'slideOut 0.3s ease forwards';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }
        }

        <?php if ($successMessage): ?>
        setTimeout(() => {
            closeNotification();
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>