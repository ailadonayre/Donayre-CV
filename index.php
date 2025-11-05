<?php
require_once 'config.php';
require_once 'session_manager.php';

// Clear PHP cache (temporary - remove after testing)
if (function_exists('opcache_reset')) {
    opcache_reset();
}
clearstatcache();

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
$experienceTraitsGlobal = [];
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

    // For each experience fetch keywords
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
    unset($cat);
    
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

// Compute base path
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($scriptDir === '/' || $scriptDir === '.') {
    $basePath = '';
} else {
    $basePath = $scriptDir;
}

function a($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Set variable for template
$user = $userData;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $name; ?> - Resume</title>
    <!-- FIXED: Added cache busting to CSS -->
    <link rel="stylesheet" href="<?php echo a($basePath); ?>/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo a($basePath); ?>/css/resume.css?v=<?php echo time(); ?>">
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
            box-shadow: 0 4px 12px rgba(93, 184, 177, 0.2);
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
                        <a href="<?php echo a($basePath); ?>/logout.php" class="logout-link">Log Out</a>
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
                    <a href="<?php echo a($basePath); ?>/edit_resume.php" class="btn-create-resume">
                        <i class="fa-solid fa-plus"></i>
                        Create Your Resume
                    </a>
                </div>
            <?php elseif (!$hasResumeData): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fa-solid fa-file-excel"></i>
                    </div>
                    <h2 class="empty-state-title">Resume Not Available</h2>
                    <p class="empty-state-description">
                        This user hasn't created their resume yet.
                    </p>
                </div>
            <?php else: ?>
                <?php
                // DEBUG: Show template info (remove after testing)
                echo "<!-- Template: " . __DIR__ . "/resume_template.php -->";
                echo "<!-- Exists: " . (file_exists('resume_template.php') ? 'YES' : 'NO') . " -->";
                echo "<!-- Modified: " . date('Y-m-d H:i:s', filemtime('resume_template.php')) . " -->";
                echo "<!-- Traits: " . count($experienceTraitsGlobal) . " -->";
                
                // Include the shared resume template
                include 'resume_template.php';
                ?>
            <?php endif; ?>
        </div>
    </main>

    <script src="<?php echo a($basePath); ?>/js/script.js?v=<?php echo time(); ?>"></script>
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