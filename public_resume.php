<?php
require_once 'config.php';

// Get user ID from URL
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($userId <= 0) {
    die("Invalid user ID");
}

// Helper function for safe output
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Fetch user data
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("Resume not found");
    }
    
    // Fetch social links
    $stmt = $db->prepare("SELECT * FROM social_links WHERE user_id = ? ORDER BY display_order");
    $stmt->execute([$userId]);
    $socialLinks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch education
    $stmt = $db->prepare("SELECT * FROM education WHERE user_id = ? ORDER BY display_order");
    $stmt->execute([$userId]);
    $educations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch experience with traits
    $stmt = $db->prepare("SELECT * FROM experience WHERE user_id = ? ORDER BY display_order");
    $stmt->execute([$userId]);
    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch traits for each experience
    foreach ($experiences as &$exp) {
        $stmt = $db->prepare("SELECT * FROM experience_traits WHERE experience_id = ?");
        $stmt->execute([$exp['id']]);
        $exp['traits'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Fetch achievements
    $stmt = $db->prepare("SELECT * FROM achievements WHERE user_id = ? ORDER BY display_order");
    $stmt->execute([$userId]);
    $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch technologies
    $stmt = $db->prepare("SELECT * FROM tech_categories WHERE user_id = ? ORDER BY display_order");
    $stmt->execute([$userId]);
    $techCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch technologies for each category
    foreach ($techCategories as &$cat) {
        $stmt = $db->prepare("SELECT * FROM technologies WHERE category_id = ? ORDER BY display_order");
        $stmt->execute([$cat['id']]);
        $cat['technologies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    die("Error loading resume: " . htmlspecialchars($e->getMessage()));
}

// Set defaults if fields are empty
$name = e($user['fullname']) ?: 'User';
$title = e($user['title']) ?: 'Professional';
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
                            <div class="profile-info">
                                <h3><span class="name-highlight"><?php echo strtoupper($name); ?></span></h3>
                                <?php if ($title): ?>
                                <p class="profile-subtitle"><?php echo $title; ?></p>
                                <?php endif; ?>
                                
                                <div class="profile-details">
                                    <?php if ($user['age']): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Age</span>
                                        <span class="detail-value"><?php echo e($user['age']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($user['address']): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Address</span>
                                        <span class="detail-value"><?php echo e($user['address']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($user['email']): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Email</span>
                                        <span class="detail-value"><?php echo e($user['email']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($user['contact']): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Contact</span>
                                        <span class="detail-value"><?php echo e($user['contact']); ?></span>
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

                    <?php if ($user['profile_summary']): ?>
                    <!-- Summary Card -->
                    <div class="card">
                        <h3 class="card-title">ABOUT ME</h3>
                        <div class="profile-section">
                            <p class="public-text"><?php echo nl2br(e($user['profile_summary'])); ?></p>
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
                                        <span class="experience-date"><?php echo e($exp['start_date']); ?><?php echo ($exp['start_date'] && $exp['end_date']) ? ' - ' : ''; ?><?php echo e($exp['end_date']); ?></span>
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
                                // Collect all unique traits from all experiences
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
                                    <span class="timeline-date"><?php echo e($edu['start_date']); ?><?php echo ($edu['start_date'] && $edu['end_date']) ? ' - ' : ''; ?><?php echo e($edu['end_date']); ?></span>
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
        </div>
    </main>

    <script src="js/script.js"></script>
</body>
</html>