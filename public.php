<?php
// public.php - robust parsing and diagnostics
require_once 'config.php';

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// --- Parse input robustly: accept ?u=username, ?id=123, or PATH_INFO /public/username
$username = null;
$user_id = null;

// 1) query string parameters
if (!empty($_GET['u'])) {
    $username = trim($_GET['u']);
} elseif (!empty($_GET['id'])) {
    // allow numeric id as fallback
    $user_id = (int) $_GET['id'];
}

// 2) PATH_INFO (if rewrite sends /public/username as PATH_INFO)
if (empty($username) && empty($user_id) && !empty($_SERVER['PATH_INFO'])) {
    $p = trim($_SERVER['PATH_INFO'], '/');
    if (is_numeric($p)) {
        $user_id = (int)$p;
    } else {
        $username = $p;
    }
}

// 3) REQUEST_URI fallback: handle cases like /Donayre-CV/public/username
if (empty($username) && empty($user_id) && !empty($_SERVER['REQUEST_URI'])) {
    $uri = $_SERVER['REQUEST_URI'];
    // remove query string
    $uri = strtok($uri, '?');
    // try to extract last segment
    $parts = explode('/', trim($uri, '/'));
    // last segment might be username
    $last = end($parts);
    if ($last !== false && $last !== 'public' && $last !== '') {
        if (is_numeric($last)) {
            $user_id = (int)$last;
        } else {
            $username = $last;
        }
    }
}

// If nothing found -> clear error
if (empty($username) && empty($user_id)) {
    // Useful debug info: show what was received (safe), don't reveal DB info.
    http_response_code(400);
    die("Username or user id not specified. Received: GET[u]=" . e($_GET['u'] ?? '') . ", GET[id]=" . e($_GET['id'] ?? '') . ", PATH_INFO=" . e($_SERVER['PATH_INFO'] ?? '') . ", REQUEST_URI=" . e($_SERVER['REQUEST_URI'] ?? ''));
}

// --- Load user by id or username/public_slug
try {
    if (!empty($user_id)) {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
    } else {
        // search by public_slug or username (case-insensitive)
        $stmt = $db->prepare("SELECT * FROM users WHERE LOWER(public_slug) = LOWER(?) OR LOWER(username) = LOWER(?)");
        $stmt->execute([$username, $username]);
    }
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        die("Resume not found for: " . e($username ?: $user_id));
    }

    // --- LOAD RELATED DATA (social, education, experience, traits, achievements, tech)
    $userId = $user['id'];

    // Initialize arrays
    $socialLinks = $educations = $experiences = $achievements = $techCategories = [];

    try {
        // Social links
        $stmt = $db->prepare("SELECT * FROM social_links WHERE user_id = ? ORDER BY display_order");
        $stmt->execute([$userId]);
        $socialLinks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Education
        $stmt = $db->prepare("SELECT * FROM education WHERE user_id = ? ORDER BY display_order");
        $stmt->execute([$userId]);
        $educations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Experiences
        $stmt = $db->prepare("SELECT * FROM experience WHERE user_id = ? ORDER BY display_order");
        $stmt->execute([$userId]);
        $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Traits for each experience
        if (!empty($experiences)) {
            foreach ($experiences as &$exp) {
                $stmt = $db->prepare("SELECT * FROM experience_traits WHERE experience_id = ? ORDER BY id");
                $stmt->execute([$exp['id']]);
                $exp['traits'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            unset($exp);
        }

        // Achievements
        $stmt = $db->prepare("SELECT * FROM achievements WHERE user_id = ? ORDER BY display_order");
        $stmt->execute([$userId]);
        $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Technology categories
        $stmt = $db->prepare("SELECT * FROM tech_categories WHERE user_id = ? ORDER BY display_order");
        $stmt->execute([$userId]);
        $techCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Technologies per category
        if (!empty($techCategories)) {
            foreach ($techCategories as &$cat) {
                $stmt = $db->prepare("SELECT * FROM technologies WHERE category_id = ? ORDER BY display_order");
                $stmt->execute([$cat['id']]);
                $cat['technologies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            unset($cat);
        }
    } catch (PDOException $e) {
        // If debug output is enabled, show message; otherwise fail silently
        if (!empty($_GET['debug'])) {
            die("Error loading related resume data: " . e($e->getMessage()));
        }
        // otherwise just keep arrays empty
    }

} catch (PDOException $e) {
    http_response_code(500);
    die("Database error loading resume: " . e($e->getMessage()));
}

// Set defaults if fields are empty
$name = e($user['fullname']) ?: 'User';
$title = e($user['title']) ?: 'Professional';

// compute basePath so asset links work in a subfolder (e.g. /Donayre-CV)
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // "/Donayre-CV" or "/" or "."
if ($scriptDir === '/' || $scriptDir === '.') {
    $basePath = '';
} else {
    $basePath = $scriptDir;
}
// helper to escape output
function a($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $name; ?> - Resume</title>
    <link rel="stylesheet" href="<?php echo a($basePath); ?>/css/style.css">
    <link rel="stylesheet" href="<?php echo a($basePath); ?>/css/resume.css">
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
                <!-- REMOVED: Print button -->
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
                                <h3 class="profile-name-title">
                                    <span class="name-highlight"><?php echo strtoupper($name); ?></span>
                                </h3>
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

                    <!-- REMOVED: About Me card -->

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
                                    
                                    <!-- Per-experience traits -->
                                    <?php if (!empty($exp['traits'])): ?>
                                    <div class="experience-highlights">
                                        <?php foreach ($exp['traits'] as $trait): ?>
                                        <div class="highlight-item">
                                            <i class="fa-solid <?php echo e($trait['trait_icon']); ?>"></i>
                                            <span><?php echo e($trait['trait_label']); ?></span>
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
                                <?php 
                                // Remove duplicates
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
        </div>
    </main>

    <script src="<?php echo a($basePath); ?>/js/script.js"></script>

    <?php
    // Optional debug info (append ?debug=1 to the URL to view)
    if (!empty($_GET['debug'])) {
        echo "<pre style='background:#111;color:#bada55;padding:12px;'>";
        echo "DEBUG: basePath=" . a($basePath) . "\n";
        echo "Requested CSS: " . a($basePath) . "/css/style.css\n";
        echo "Requested JS:  " . a($basePath) . "/js/script.js\n";
        echo "REQUEST_URI: " . a($_SERVER['REQUEST_URI'] ?? '') . "\n";
        echo "</pre>";
    }
    ?>

</body>
</html>