<?php
// public.php - Using shared template
require_once 'config.php';

// Prevent caching to ensure fresh data
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Parse input: accept ?u=username, ?id=123, or PATH_INFO /public/username
$username = null;
$user_id = null;

// 1) Query string parameters
if (!empty($_GET['u'])) {
    $username = trim($_GET['u']);
} elseif (!empty($_GET['id'])) {
    $user_id = (int) $_GET['id'];
}

// 2) PATH_INFO
if (empty($username) && empty($user_id) && !empty($_SERVER['PATH_INFO'])) {
    $p = trim($_SERVER['PATH_INFO'], '/');
    if (is_numeric($p)) {
        $user_id = (int)$p;
    } else {
        $username = $p;
    }
}

// 3) REQUEST_URI fallback
if (empty($username) && empty($user_id) && !empty($_SERVER['REQUEST_URI'])) {
    $uri = $_SERVER['REQUEST_URI'];
    $uri = strtok($uri, '?');
    $parts = explode('/', trim($uri, '/'));
    $last = end($parts);
    if ($last !== false && $last !== 'public' && $last !== '') {
        if (is_numeric($last)) {
            $user_id = (int)$last;
        } else {
            $username = $last;
        }
    }
}

if (empty($username) && empty($user_id)) {
    http_response_code(400);
    die("Username or user id not specified.");
}

// Initialize variables for template
$socialLinks = [];
$educations = [];
$experiences = [];
$experienceTraitsGlobal = [];
$achievements = [];
$techCategories = [];
$hasResumeData = false;
$isOwner = false; // Public view is never owner
$currentUser = null; // No current user in public view

try {
    if (!empty($user_id)) {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE LOWER(public_slug) = LOWER(?) OR LOWER(username) = LOWER(?)");
        $stmt->execute([$username, $username]);
    }
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        die("Resume not found for: " . e($username ?: $user_id));
    }

    $userId = $user['id'];

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

    // Keywords for each experience
    foreach ($experiences as &$exp) {
        $stmt = $db->prepare("SELECT keyword FROM experience_keywords WHERE experience_id = ? ORDER BY display_order, id");
        $stmt->execute([$exp['id']]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $exp['keywords'] = array_map(function($r){ return $r['keyword']; }, $rows);
    }
    unset($exp);

    // Global experience traits
    $stmt = $db->prepare("SELECT trait_icon, trait_label FROM experience_traits_global WHERE user_id = ? ORDER BY display_order, id");
    $stmt->execute([$userId]);
    $experienceTraitsGlobal = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Achievements
    $stmt = $db->prepare("SELECT * FROM achievements WHERE user_id = ? ORDER BY display_order");
    $stmt->execute([$userId]);
    $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Technology categories
    $stmt = $db->prepare("SELECT * FROM tech_categories WHERE user_id = ? ORDER BY display_order");
    $stmt->execute([$userId]);
    $techCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Technologies per category
    foreach ($techCategories as &$cat) {
        $stmt = $db->prepare("SELECT * FROM technologies WHERE category_id = ? ORDER BY display_order");
        $stmt->execute([$cat['id']]);
        $cat['technologies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($cat);

    $hasResumeData = !empty($user['fullname']) || 
                     !empty($educations) || 
                     !empty($experiences) || 
                     !empty($achievements) || 
                     !empty($techCategories);

} catch (PDOException $e) {
    http_response_code(500);
    die("Database error loading resume: " . e($e->getMessage()));
}

$name = e($user['fullname']) ?: 'User';
$title = e($user['title']) ?: 'Professional';

// Compute base path
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($scriptDir === '/' || $scriptDir === '.') {
    $basePath = '';
} else {
    $basePath = $scriptDir;
}

function a($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $name; ?> - Resume</title>
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
            line-height: 1.6;
        }
    </style>
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
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <?php if (!$hasResumeData): ?>
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
                <?php
                // Include the shared resume template
                include 'resume_template.php';
                ?>
            <?php endif; ?>
        </div>
    </main>

    <script src="<?php echo a($basePath); ?>/js/script.js?v=<?php echo time(); ?>"></script>

    <?php
    // Optional debug info - add ?debug=1 to URL to see
    if (!empty($_GET['debug'])) {
        echo "<pre style='background:#111;color:#bada55;padding:12px;margin:20px;'>";
        echo "DEBUG INFO:\n";
        echo "User ID: " . $userId . "\n";
        echo "Username: " . a($user['username']) . "\n";
        echo "Experiences: " . count($experiences) . "\n";
        echo "Global Traits: " . count($experienceTraitsGlobal) . "\n";
        echo "Last Updated: " . date('Y-m-d H:i:s') . "\n";
        echo "</pre>";
    }
    ?>

</body>
</html>