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
 * - $techCategories (array): Technology categories with technologies
 * - $name (string): User's full name
 * - $title (string): User's title/position
 * - $hasResumeData (bool): Whether resume has content
 * - $isOwner (bool): Whether current user is owner (for index.php)
 * - $currentUser (array|null): Current logged-in user data
 * - $basePath (string): Base path for assets
 */

// Helper function (if not already defined)
if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}
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
                            <?php if (!empty($exp['start_date']) || !empty($exp['end_date'])): ?>
                            <span class="experience-date">
                                <?php echo e($exp['start_date']); ?>
                                <?php echo (!empty($exp['start_date']) && !empty($exp['end_date'])) ? ' - ' : ''; ?>
                                <?php echo e($exp['end_date']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($exp['company'])): ?>
                        <p class="experience-company"><?php echo e($exp['company']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($exp['description'])): ?>
                        <p class="experience-description"><?php echo nl2br(e($exp['description'])); ?></p>
                        <?php endif; ?>
                        
                        <!-- Per-experience keywords -->
                        <?php if (!empty($exp['keywords'])): ?>
                        <div class="experience-skills">
                            <?php foreach ($exp['keywords'] as $kw): ?>
                                <span class="skill-tag"><?php echo e($kw); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Global Experience Traits (shown once at bottom) -->
                <?php if (!empty($experienceTraitsGlobal)): ?>
                <div class="experience-traits-footer">
                    <div class="trait-boxes">
                        <?php foreach ($experienceTraitsGlobal as $gtrait): ?>
                        <div class="trait-box" title="<?php echo e($gtrait['trait_label']); ?>">
                            <i class="fa-solid <?php echo e($gtrait['trait_icon']); ?>"></i>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
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
                        <?php if (!empty($edu['institution'])): ?>
                        <p class="timeline-subtitle"><?php echo e($edu['institution']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($edu['start_date']) || !empty($edu['end_date'])): ?>
                        <span class="timeline-date">
                            <?php echo e($edu['start_date']); ?>
                            <?php echo (!empty($edu['start_date']) && !empty($edu['end_date'])) ? ' - ' : ''; ?>
                            <?php echo e($edu['end_date']); ?>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($edu['description'])): ?>
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
                        <?php if (!empty($ach['description'])): ?>
                        <p class="achievement-description"><?php echo nl2br(e($ach['description'])); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($ach['achievement_date'])): ?>
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
                    <?php 
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