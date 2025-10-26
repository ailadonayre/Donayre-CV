<?php
require_once 'config.php';
require_once 'session_manager.php';

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
    $currentUser = $sessionManager->getCurrentUser();
    $viewingUserId = $currentUser['id'];
    $isOwner = true;
} else {
    header('Location: login.php');
    exit;
}

if ($viewingUserId <= 0) {
    die("Invalid user ID");
}

$successMessage = $isOwner ? $sessionManager->getFlash('success') : null;

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
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$viewingUserId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        die("User not found");
    }
    
    $hasResumeData = !empty($userData['fullname']) || !empty($userData['title']);
    
    // Fetch social links
    $stmt = $db->prepare("SELECT * FROM social_links WHERE user_id = ? ORDER BY display_order");
    $stmt->execute([$viewingUserId]);
    $socialLinks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch education
    $stmt = $db->prepare("SELECT * FROM education WHERE user_id = ? ORDER BY display_order");
    $stmt->execute([$viewingUserId]);
    $educations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch experiences
    $stmt = $db->prepare("SELECT * FROM experience WHERE user_id = ? ORDER BY display_order");
    $stmt->execute([$viewingUserId]);
    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // For each experience fetch keywords (comma-independent rows)
    foreach ($experiences as &$exp) {
        $stmt = $db->prepare("SELECT keyword FROM experience_keywords WHERE experience_id = ? ORDER BY display_order, id");
        $stmt->execute([$exp['id']]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $exp['keywords'] = array_map(function($r){ return $r['keyword']; }, $rows);
    }
    unset($exp);

    // Fetch card-level experience traits (apply to the whole Experience card)
    $stmt = $db->prepare("SELECT trait_icon, trait_label FROM experience_traits_global WHERE user_id = ? ORDER BY display_order, id");
    $stmt->execute([$viewingUserId]);
    $experienceTraitsGlobal = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch achievements
    $stmt = $db->prepare("SELECT * FROM achievements WHERE user_id = ? ORDER BY display_order");
    $stmt->execute([$viewingUserId]);
    $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch technologies - FIXED: Remove duplicates
    $stmt = $db->prepare("SELECT * FROM tech_categories WHERE user_id = ? ORDER BY display_order");
    $stmt->execute([$viewingUserId]);
    $techCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch technologies for each category
    foreach ($techCategories as &$cat) {
        $stmt = $db->prepare("SELECT * FROM technologies WHERE category_id = ? ORDER BY display_order");
        $stmt->execute([$cat['id']]);
        $cat['technologies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($cat); // IMPORTANT
    
    $hasResumeData = !empty($userData['fullname']) || 
                     !empty($educations) || 
                     !empty($experiences) || 
                     !empty($achievements) || 
                     !empty($techCategories);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    if (ini_get('display_errors')) {
        die("Error loading resume data: " . htmlspecialchars($e->getMessage()));
    }
    die("Error loading resume data. Please contact support.");
}

$name = e($userData['fullname']) ?: 'User';
$title = e($userData['title']) ?: 'Professional';
$currentUser = $isOwner ? $sessionManager->getCurrentUser() : null;

// --- compute base path so links and assets work in a subfolder (e.g. /Donayre-CV)
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // gives "/Donayre-CV" when in that folder
if ($scriptDir === '/' || $scriptDir === '.') {
    $basePath = '';
} else {
    $basePath = $scriptDir;
}

// helper to escape values for output in href/src
function a($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $name; ?> - Resume</title>
    <link rel="stylesheet" href="<?php echo a($basePath); ?>/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
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
                        <a href="<?php echo a($basePath); ?>/edit_resume.php" class="logout-link">
                            <?php echo $hasResumeData ? 'Edit Resume' : 'Create Resume'; ?>
                        </a>
                        <a href="<?php echo a($basePath); ?>/public/<?php echo urlencode($userData['public_slug'] ?? $userData['username']); ?>" class="logout-link">Public View</a>
                        <a href="logout.php" class="logout-link">Log out</a>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <?php if (!$hasResumeData && $isOwner): ?>
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
                <div class="content-grid">
                    <!-- Left Column -->
                    <div class="left-column">
                        <!-- Profile Card - FIXED: Added spacing between name and "PROFILE" -->
                        <div class="card profile-card">
                            <div class="card-header">
                                <span class="header-label">Profile</span>
                            </div>
                            <div class="profile-section">
                                <div class="profile-info">
                                    <h3 class="profile-name-title">
                                        <span class="name-highlight"><?php echo strtoupper($name); ?></span>
                                    </h3>
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
                                            <?php foreach ($socialLinks as $link):
                                                $icon = trim($link['icon'] ?? '');
                                                $platform = strtolower(trim($link['platform'] ?? ''));

                                                // decide which prefix to use
                                                $brandPlatforms = ['github', 'linkedin', 'twitter', 'facebook', 'instagram', 'youtube', 'gitlab', 'bitbucket'];
                                                if (in_array($platform, $brandPlatforms) || strpos($icon, 'fa-brands') !== false) {
                                                    // if stored icon already contains fa-brands or platform is a brand => use brands
                                                    // if the DB stores just 'fa-github', keep it; otherwise ensure safe value
                                                    $class = (strpos($icon, 'fa-') === 0) ? "fa-brands {$icon}" : "fa-brands fa-{$platform}";
                                                } else {
                                                    // custom/site -> solid link icon
                                                    // If DB explicitly stored a solid icon use it, else fall back to fa-solid fa-link
                                                    if ($icon && (strpos($icon, 'fa-link') !== false || strpos($icon, 'fa-solid') !== false)) {
                                                        $class = (strpos($icon, 'fa-solid') === 0) ? $icon : "fa-solid {$icon}";
                                                    } else {
                                                        $class = 'fa-solid fa-link';
                                                    }
                                                }

                                                // sanitize class (allow only expected characters)
                                                $class = preg_replace('/[^a-z0-9_\-\s]/i', '', $class);
                                            ?>
                                                <a href="<?php echo e($link['url']); ?>" target="_blank" class="social-icon" rel="noopener noreferrer" title="<?php echo e($link['platform']); ?>">
                                                    <i class="<?php echo e($class); ?>"></i>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- REMOVED: About Me card entirely -->

                        <?php if (!empty($experiences)): ?>
                        <!-- Experience Card - FIXED: Per-experience traits -->
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
                                        <?php if (!empty($exp['keywords'])): ?>
                                        <div class="experience-skills">
                                            <?php foreach ($exp['keywords'] as $kw): ?>
                                                <span class="skill-tag"><?php echo e($kw); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                        <!-- FIXED: Show traits per experience -->
                                        <?php if (!empty($experienceTraitsGlobal)): ?>
                                        <div class="experience-traits-global">
                                            <?php foreach ($experienceTraitsGlobal as $gtrait): ?>
                                            <div class="highlight-item global">
                                                <i class="fa-solid <?php echo e($gtrait['trait_icon']); ?>"></i>
                                                <span><?php echo e($gtrait['trait_label']); ?></span>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Right Column -->
                    <div class="right-column">
                        <?php if (!empty($educations)): ?>
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
                <!-- FIXED: Technologies Card - Remove duplicates, ensure Multimedia is present -->
                <div class="card tech-card">
                    <h3 class="card-title">TECHNOLOGIES</h3>
                    <div class="tech-content">
                        <div class="tech-grid">
                            <?php foreach ($techCategories as $cat): ?>
                            <div class="tech-category">
                                <h4><?php echo e($cat['category_name']); ?></h4>
                                <div class="tech-tags">
                                    <?php 
                                    // FIXED: Remove duplicates
                                    $uniqueTechs = [];
                                    foreach ($cat['technologies'] as $tech) {
                                        $techName = $tech['tech_name'];
                                        if (!in_array($techName, $uniqueTechs)) {
                                            $uniqueTechs[] = $techName;
                                            ?>
                                            <span class="tech-tag"><?php echo e($techName); ?></span>
                                            <?php
                                        }
                                    }
                                    ?>
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

    <script src="<?php echo a($basePath); ?>/js/script.js"></script>
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