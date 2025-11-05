<?php
/**
 * Shared Resume Template
 * Used by both index.php and public.php to display resume content
 * 
 * Required variables:
 * - $user (array): User data
 * - $socialLinks (array): Social links
 * - $educations (array): Education entries
 * - $experiences (array): Experience entries with keywords
 * - $experienceTraitsGlobal (array): Global experience traits
 * - $achievements (array): Achievements
 * - $name (string): User's full name
 * - $title (string): User's title/position
 * - $hasResumeData (bool): Whether resume has content
 * - $isOwner (bool): Whether current user is owner (for index.php)
 * - $currentUser (array|null): Current logged-in user data
 * - $basePath (string): Base path for assets
 * - $userTechnologies (array): User's selected technologies grouped by category
 */

// Helper function (if not already defined)
if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Check for has_* flags to show "No available data."
$showEducation = ($user['has_education'] ?? false) || !empty($educations);
$showExperience = ($user['has_experience'] ?? false) || !empty($experiences);
$showAchievements = ($user['has_achievements'] ?? false) || !empty($achievements);
?>

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
                    <h3 class="profile-name-title">
                        <span class="name-highlight"><?php echo strtoupper($name); ?></span>
                    </h3>
                    <?php if ($title): ?>
                    <p class="profile-subtitle"><?php echo $title; ?></p>
                    <?php endif; ?>
                    
                    <div class="profile-details">
                        <?php if (!empty($user['age'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">Age</span>
                            <span class="detail-value"><?php echo e($user['age']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($user['address'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">Address</span>
                            <span class="detail-value"><?php echo e($user['address']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($user['email'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">Email</span>
                            <span class="detail-value"><?php echo e($user['email']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($user['contact'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">Contact</span>
                            <span class="detail-value"><?php echo e($user['contact']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($socialLinks)): ?>
                        <div class="profile-social">
                            <?php foreach ($socialLinks as $link):
                                $icon = trim($link['icon'] ?? '');
                                $platform = strtolower(trim($link['platform'] ?? ''));

                                $brandPlatforms = ['github', 'linkedin', 'twitter', 'facebook', 'instagram', 'youtube', 'gitlab', 'bitbucket'];
                                if (in_array($platform, $brandPlatforms) || strpos($icon, 'fa-brands') !== false) {
                                    $class = (strpos($icon, 'fa-') === 0) ? "fa-brands {$icon}" : "fa-brands fa-{$platform}";
                                } else {
                                    if ($icon && (strpos($icon, 'fa-link') !== false || strpos($icon, 'fa-solid') !== false)) {
                                        $class = (strpos($icon, 'fa-solid') === 0) ? $icon : "fa-solid {$icon}";
                                    } else {
                                        $class = 'fa-solid fa-link';
                                    }
                                }
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

        <!-- Experience Card -->
        <div class="card experience-card">
            <h3 class="card-title">EXPERIENCE</h3>
            
            <?php if ($showExperience && !empty($experiences)): ?>
            <div class="experience-content">
                <div class="experience-section">
                    <?php foreach ($experiences as $exp): ?>
                    <div class="experience-item">
                        <div class="experience-header">
                            <h4 class="experience-title"><?php echo e($exp['job_title']); ?></h4>
                            <?php if (!empty($exp['start_date']) || !empty($exp['end_date'])): ?>
                            <span class="experience-date">
                                <?php echo e($exp['start_date']); ?>
                                <?php echo (!empty($exp['start_date'])