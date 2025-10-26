<?php
require_once 'config.php';
require_once 'session_manager.php';

$sessionManager = new SessionManager();
$sessionManager->requireLogin('login.php');

$currentUser = $sessionManager->getCurrentUser();
$userId = $currentUser['id'];

$error = '';
$success = $sessionManager->getFlash('success');

// Fetch current user data
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Failed to load data: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    try {
        $db->beginTransaction();
        
        // Update basic personal information
        $stmt = $db->prepare("UPDATE users SET fullname = ?, title = ?, email = ?, contact = ?, address = ?, age = ? WHERE id = ?");
        $stmt->execute([
            trim($_POST['fullname']),
            trim($_POST['title']),
            trim($_POST['email']),
            trim($_POST['contact']),
            trim($_POST['address']),
            trim($_POST['age']),
            $userId
        ]);
        
        // Handle Social Links
        $db->prepare("DELETE FROM social_links WHERE user_id = ?")->execute([$userId]);
        
        $socialLinks = [
            ['platform' => 'LinkedIn', 'url' => trim($_POST['linkedin'] ?? ''), 'icon' => 'fa-linkedin'],
            ['platform' => 'GitHub', 'url' => trim($_POST['github'] ?? ''), 'icon' => 'fa-github'],
            ['platform' => 'Custom', 'url' => trim($_POST['custom_link'] ?? ''), 'icon' => 'fa-link']
        ];
        
        $order = 0;
        foreach ($socialLinks as $link) {
            if (!empty($link['url'])) {
                $stmt = $db->prepare("INSERT INTO social_links (user_id, platform, url, icon, display_order) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $link['platform'], $link['url'], $link['icon'], $order++]);
            }
        }
        
        // Handle Education
        $db->prepare("DELETE FROM education WHERE user_id = ?")->execute([$userId]);
        
        if (isset($_POST['education_degree'])) {
            foreach ($_POST['education_degree'] as $index => $degree) {
                if (!empty(trim($degree))) {
                    $stmt = $db->prepare("INSERT INTO education (user_id, degree, institution, start_date, end_date, description, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $userId,
                        trim($degree),
                        trim($_POST['education_institution'][$index] ?? ''),
                        trim($_POST['education_start'][$index] ?? ''),
                        trim($_POST['education_end'][$index] ?? ''),
                        trim($_POST['education_description'][$index] ?? ''),
                        $index
                    ]);
                }
            }
        }
        
        // Handle Experience
        $db->prepare("DELETE FROM experience WHERE user_id = ?")->execute([$userId]);

        if (isset($_POST['experience_title'])) {
            foreach ($_POST['experience_title'] as $index => $title) {
                if (!empty(trim($title))) {
                    $stmt = $db->prepare("INSERT INTO experience (user_id, job_title, company, start_date, end_date, description, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $userId,
                        trim($title),
                        trim($_POST['experience_company'][$index] ?? ''),
                        trim($_POST['experience_start'][$index] ?? ''),
                        trim($_POST['experience_end'][$index] ?? ''),
                        trim($_POST['experience_description'][$index] ?? ''),
                        $index
                    ]);
                    
                    $expId = $db->lastInsertId();
                    
                    // Save per-experience keywords
                    if (isset($_POST['experience_keywords'][$index])) {
                        $raw = trim($_POST['experience_keywords'][$index]);
                        if ($raw !== '') {
                            $parts = array_filter(array_map('trim', explode(',', $raw)));
                            $kOrder = 0;
                            $insertKw = $db->prepare("INSERT INTO experience_keywords (experience_id, keyword, display_order) VALUES (?, ?, ?)");
                            foreach ($parts as $kw) {
                                if ($kw === '') continue;
                                $insertKw->execute([$expId, $kw, $kOrder++]);
                            }
                        }
                    }
                }
            }
        }
        
        // Handle Achievements
        $db->prepare("DELETE FROM achievements WHERE user_id = ?")->execute([$userId]);
        
        if (isset($_POST['achievement_title'])) {
            foreach ($_POST['achievement_title'] as $index => $title) {
                if (!empty(trim($title))) {
                    $stmt = $db->prepare("INSERT INTO achievements (user_id, title, achievement_date, description, icon, display_order) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $userId,
                        trim($title),
                        trim($_POST['achievement_date'][$index] ?? ''),
                        trim($_POST['achievement_description'][$index] ?? ''),
                        trim($_POST['achievement_icon'][$index] ?? 'fa-trophy'),
                        $index
                    ]);
                }
            }
        }
        
        // Handle Technologies
        $db->prepare("DELETE FROM tech_categories WHERE user_id = ?")->execute([$userId]);
        
        if (isset($_POST['tech_category'])) {
            foreach ($_POST['tech_category'] as $index => $category) {
                if (!empty(trim($category))) {
                    $stmt = $db->prepare("INSERT INTO tech_categories (user_id, category_name, display_order) VALUES (?, ?, ?)");
                    $stmt->execute([$userId, trim($category), $index]);
                    
                    $catId = $db->lastInsertId();
                    
                    if (isset($_POST['tech_items'][$index])) {
                        $technologies = array_filter(array_map('trim', explode(',', $_POST['tech_items'][$index])));
                        $technologies = array_unique($technologies);
                        $techOrder = 0;
                        foreach ($technologies as $tech) {
                            if (!empty($tech)) {
                                $stmt = $db->prepare("INSERT INTO technologies (category_id, tech_name, display_order) VALUES (?, ?, ?)");
                                $stmt->execute([$catId, $tech, $techOrder++]);
                            }
                        }
                    }
                }
            }
        }

        // Handle Global Experience Traits
        $db->prepare("DELETE FROM experience_traits_global WHERE user_id = ?")->execute([$userId]);

        if (!empty($_POST['experience_traits_global']) && is_array($_POST['experience_traits_global'])) {
            $insertG = $db->prepare("INSERT INTO experience_traits_global (user_id, trait_icon, trait_label, display_order) VALUES (?, ?, ?, ?)");
            $gOrder = 0;
            foreach ($_POST['experience_traits_global'] as $g) {
                if (trim($g) === '') continue;
                $parts = explode('|', $g, 2);
                $icon = trim($parts[0] ?? '');
                $label = trim($parts[1] ?? '');
                if ($label === '') continue;
                $insertG->execute([$userId, $icon, $label, $gOrder++]);
            }
        }
        
        $db->commit();
        
        $sessionManager->setFlash('success', 'Resume updated successfully!');
        header("Location: index.php");
        exit;
        
    } catch (PDOException $e) {
        $db->rollBack();
        $error = "Failed to save: " . $e->getMessage();
    }
}

// Fetch existing data for form
$educationData = $db->prepare("SELECT * FROM education WHERE user_id = ? ORDER BY display_order");
$educationData->execute([$userId]);
$educations = $educationData->fetchAll(PDO::FETCH_ASSOC);

$experienceData = $db->prepare("SELECT * FROM experience WHERE user_id = ? ORDER BY display_order");
$experienceData->execute([$userId]);
$experiences = $experienceData->fetchAll(PDO::FETCH_ASSOC);

foreach ($experiences as &$exp) {
    $stmt = $db->prepare("SELECT keyword FROM experience_keywords WHERE experience_id = ? ORDER BY display_order, id");
    $stmt->execute([$exp['id']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $exp['keywords'] = array_map(function($r){ return $r['keyword']; }, $rows);
}
unset($exp);

$gStmt = $db->prepare("SELECT trait_icon, trait_label FROM experience_traits_global WHERE user_id = ? ORDER BY display_order, id");
$gStmt->execute([$userId]);
$existingGlobalTraits = $gStmt->fetchAll(PDO::FETCH_ASSOC);

$achievementData = $db->prepare("SELECT * FROM achievements WHERE user_id = ? ORDER BY display_order");
$achievementData->execute([$userId]);
$achievements = $achievementData->fetchAll(PDO::FETCH_ASSOC);

$techData = $db->prepare("SELECT * FROM tech_categories WHERE user_id = ? ORDER BY display_order");
$techData->execute([$userId]);
$techCategories = $techData->fetchAll(PDO::FETCH_ASSOC);

foreach ($techCategories as &$cat) {
    $stmt = $db->prepare("SELECT * FROM technologies WHERE category_id = ? ORDER BY display_order");
    $stmt->execute([$cat['id']]);
    $cat['technologies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($cat);

$socialData = $db->prepare("SELECT * FROM social_links WHERE user_id = ? ORDER BY display_order");
$socialData->execute([$userId]);
$socialLinks = $socialData->fetchAll(PDO::FETCH_ASSOC);

// Compute base path
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$basePath = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
function a($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Resume</title>
    <link rel="stylesheet" href="<?php echo a($basePath); ?>/css/style.css">
    <link rel="stylesheet" href="<?php echo a($basePath); ?>/css/resume.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .repeater-item { background: var(--bg-secondary); padding: 20px; border-radius: 12px; margin-bottom: 15px; border: 1px solid var(--border-color); }
        .btn-remove { background: #e74c3c; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .btn-remove:hover { background: #c0392b; }
        .btn-add { background: var(--accent-color); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; margin-top: 10px; transition: all 0.3s; }
        .btn-add:hover { background: var(--accent-hover); }
        .trait-selector { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
        .trait-option { padding: 8px 16px; border: 2px solid var(--border-color); border-radius: 8px; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 8px; background: var(--bg-primary); }
        .trait-option:hover { border-color: var(--accent-color); background: var(--bg-secondary); transform: translateY(-2px); }
        .trait-option.selected { background: var(--accent-color); color: white; border-color: var(--accent-color); }
        .trait-option.selected i { color: white; }
        .trait-option i { color: var(--accent-color); transition: color 0.3s; }
        .icon-selector { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
        .icon-option { width: 50px; height: 50px; border: 2px solid var(--border-color); border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 1.5rem; transition: all 0.3s; background: var(--bg-primary); }
        .icon-option:hover { border-color: var(--accent-color); transform: scale(1.1); }
        .icon-option.selected { background: var(--accent-color); color: white; border-color: var(--accent-color); }
        .form-note { display: block; margin-top: 5px; font-size: 0.85rem; color: var(--text-muted); font-style: italic; }
    </style>
</head>
<body>
    <button class="dark-mode-toggle" id="darkModeToggle"><i class="fa-solid fa-moon"></i></button>

    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <h1 class="name">Edit Resume</h1>
                    <h2 class="title">Update Your Information</h2>
                    <p class="user-welcome">
                        Editing as <strong><?php echo htmlspecialchars($currentUser['username']); ?></strong>
                        <a href="<?php echo a($basePath); ?>/public/<?php echo urlencode($userData['public_slug'] ?? $userData['username']); ?>" class="logout-link">Public View</a>
                        <a href="<?php echo a($basePath); ?>/index.php" class="logout-link">Home</a>
                        <a href="<?php echo a($basePath); ?>/logout.php" class="logout-link">Log out</a>
                    </p>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="resume-editor">
                <?php if ($error): ?>
                    <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" class="resume-form" id="resumeForm">
                    <!-- Personal Information -->
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-user"></i> Personal Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="fullname" class="form-input" value="<?php echo htmlspecialchars($userData['fullname'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-input" value="<?php echo htmlspecialchars($userData['title'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Contact</label>
                                <input type="text" name="contact" class="form-input" value="<?php echo htmlspecialchars($userData['contact'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Age</label>
                                <input type="number" name="age" class="form-input" value="<?php echo htmlspecialchars($userData['age'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-input" value="<?php echo htmlspecialchars($userData['address'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Social Links -->
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-link"></i> Social Links</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">LinkedIn</label>
                                <input type="url" name="linkedin" class="form-input" value="<?php echo htmlspecialchars($socialLinks[0]['url'] ?? ''); ?>" placeholder="https://linkedin.com/in/yourprofile">
                            </div>
                            <div class="form-group">
                                <label class="form-label">GitHub</label>
                                <input type="url" name="github" class="form-input" value="<?php echo htmlspecialchars($socialLinks[1]['url'] ?? ''); ?>" placeholder="https://github.com/yourusername">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Custom Link</label>
                            <input type="url" name="custom_link" class="form-input" value="<?php echo htmlspecialchars($socialLinks[2]['url'] ?? ''); ?>" placeholder="https://yourwebsite.com">
                        </div>
                    </div>

                    <!-- Education -->
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-graduation-cap"></i> Education</h3>
                        <div id="education-container">
                            <?php if (empty($educations)): ?>
                                <div class="repeater-item">
                                    <div class="form-group">
                                        <label class="form-label">Degree Program *</label>
                                        <input type="text" name="education_degree[]" class="form-input" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Institution *</label>
                                        <input type="text" name="education_institution[]" class="form-input" required>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">Start Date</label>
                                            <input type="text" name="education_start[]" class="form-input" placeholder="2023">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">End Date</label>
                                            <input type="text" name="education_end[]" class="form-input" placeholder="Present">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Description</label>
                                        <textarea name="education_description[]" class="form-textarea" rows="3"></textarea>
                                    </div>
                                </div>
                            <?php else: foreach ($educations as $edu): ?>
                                <div class="repeater-item">
                                    <div class="form-group">
                                        <label class="form-label">Degree Program *</label>
                                        <input type="text" name="education_degree[]" class="form-input" value="<?php echo htmlspecialchars($edu['degree']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Institution *</label>
                                        <input type="text" name="education_institution[]" class="form-input" value="<?php echo htmlspecialchars($edu['institution']); ?>" required>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">Start Date</label>
                                            <input type="text" name="education_start[]" class="form-input" value="<?php echo htmlspecialchars($edu['start_date']); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">End Date</label>
                                            <input type="text" name="education_end[]" class="form-input" value="<?php echo htmlspecialchars($edu['end_date']); ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Description</label>
                                        <textarea name="education_description[]" class="form-textarea" rows="3"><?php echo htmlspecialchars($edu['description']); ?></textarea>
                                    </div>
                                    <button type="button" class="btn-remove" onclick="removeItem(this)">Remove</button>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                        <button type="button" class="btn-add" onclick="addEducation()"><i class="fa-solid fa-plus"></i> Add Education</button>
                    </div>

                    <!-- Global Experience Traits -->
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-star"></i> Experience Traits</h3>
                        <p style="color: var(--text-secondary); margin-bottom: 15px; font-size: 0.95rem;">
                            <i class="fa-solid fa-info-circle"></i> Select traits that describe your overall experience. These will be displayed once at the top of the Experience section.
                        </p>

                        <div id="experience-traits-global" class="trait-selector">
                            <?php
                            $globalTraitOptions = [
                                'fa-code|Technical Expertise',
                                'fa-users|Team Leadership',
                                'fa-lightbulb|Problem Solving',
                                'fa-rotate-right|Adaptability',
                                'fa-rocket|Innovation',
                                'fa-chart-line|Growth',
                                'fa-handshake|Collaboration'
                            ];

                            $existingSet = [];
                            foreach ($existingGlobalTraits as $eg) {
                                $existingSet[] = trim($eg['trait_icon'] . '|' . $eg['trait_label']);
                            }

                            foreach ($globalTraitOptions as $opt):
                                $isSelected = in_array($opt, $existingSet) ? 'selected' : '';
                                $parts = explode('|', $opt);
                                $icon = $parts[0];
                                $label = $parts[1];
                            ?>
                                <div class="trait-option <?php echo $isSelected; ?>" data-value="<?php echo htmlspecialchars($opt); ?>" onclick="toggleGlobalTrait(this)">
                                    <i class="fa-solid <?php echo htmlspecialchars($icon); ?>"></i>
                                    <span><?php echo htmlspecialchars($label); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div id="experience-traits-global-inputs">
                            <?php foreach ($existingGlobalTraits as $eg): 
                                $val = $eg['trait_icon'] . '|' . $eg['trait_label'];
                            ?>
                                <input type="hidden" name="experience_traits_global[]" value="<?php echo htmlspecialchars($val); ?>">
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Experience -->
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-briefcase"></i> Experience</h3>
                        <div id="experience-container">
                            <?php if (empty($experiences)): ?>
                                <div class="repeater-item">
                                    <div class="form-group">
                                        <label class="form-label">Job Title/Position</label>
                                        <input type="text" name="experience_title[]" class="form-input">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Company/Project</label>
                                        <input type="text" name="experience_company[]" class="form-input">
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">Start Date</label>
                                            <input type="text" name="experience_start[]" class="form-input" placeholder="Jan 2023">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">End Date</label>
                                            <input type="text" name="experience_end[]" class="form-input" placeholder="Present">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Description</label>
                                        <textarea name="experience_description[]" class="form-textarea" rows="3"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Keywords (comma-separated)</label>
                                        <input type="text" name="experience_keywords[]" class="form-input" placeholder="e.g., Machine Learning, Data Visualization, Analytics">
                                        <small class="form-note">Enter keywords separated by commas. They will display as tags under the description.</small>
                                    </div>
                                </div>
                            <?php else: foreach ($experiences as $expIndex => $exp): ?>
                                <div class="repeater-item">
                                    <div class="form-group">
                                        <label class="form-label">Job Title/Position</label>
                                        <input type="text" name="experience_title[]" class="form-input" value="<?php echo htmlspecialchars($exp['job_title']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Company/Project</label>
                                        <input type="text" name="experience_company[]" class="form-input" value="<?php echo htmlspecialchars($exp['company']); ?>">
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">Start Date</label>
                                            <input type="text" name="experience_start[]" class="form-input" value="<?php echo htmlspecialchars($exp['start_date']); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">End Date</label>
                                            <input type="text" name="experience_end[]" class="form-input" value="<?php echo htmlspecialchars($exp['end_date']); ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Description</label>
                                        <textarea name="experience_description[]" class="form-textarea" rows="3"><?php echo htmlspecialchars($exp['description']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Keywords (comma-separated)</label>
                                        <input type="text" name="experience_keywords[<?php echo $expIndex; ?>]" class="form-input" value="<?php echo htmlspecialchars(implode(', ', $exp['keywords'])); ?>" placeholder="e.g., Machine Learning, Data Visualization, Analytics">
                                        <small class="form-note">Enter keywords separated by commas. They will display as tags under the description.</small>
                                    </div>
                                    <button type="button" class="btn-remove" onclick="removeItem(this)">Remove</button>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                        <button type="button" class="btn-add" onclick="addExperience()"><i class="fa-solid fa-plus"></i> Add Experience</button>
                    </div>

                    <!-- Achievements -->
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-trophy"></i> Achievements</h3>
                        <div id="achievement-container">
                            <?php if (empty($achievements)): ?>
                                <div class="repeater-item">
                                    <div class="form-group">
                                        <label class="form-label">Achievement Title</label>
                                        <input type="text" name="achievement_title[]" class="form-input">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Date</label>
                                        <input type="text" name="achievement_date[]" class="form-input" placeholder="2024">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Description</label>
                                        <textarea name="achievement_description[]" class="form-textarea" rows="3"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Select Icon</label>
                                        <div class="icon-selector">
                                            <?php
                                            $icons = ['fa-trophy', 'fa-medal', 'fa-certificate', 'fa-award', 'fa-star', 'fa-code'];
                                            foreach ($icons as $idx => $icon):
                                            ?>
                                                <div class="icon-option <?php echo $idx === 0 ? 'selected' : ''; ?>" data-icon="<?php echo $icon; ?>" onclick="selectIcon(this)">
                                                    <i class="fa-solid <?php echo $icon; ?>"></i>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <input type="hidden" name="achievement_icon[]" value="fa-trophy" class="icon-input">
                                    </div>
                                </div>
                            <?php else: foreach ($achievements as $ach): ?>
                                <div class="repeater-item">
                                    <div class="form-group">
                                        <label class="form-label">Achievement Title</label>
                                        <input type="text" name="achievement_title[]" class="form-input" value="<?php echo htmlspecialchars($ach['title']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Date</label>
                                        <input type="text" name="achievement_date[]" class="form-input" value="<?php echo htmlspecialchars($ach['achievement_date']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Description</label>
                                        <textarea name="achievement_description[]" class="form-textarea" rows="3"><?php echo htmlspecialchars($ach['description']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Select Icon</label>
                                        <div class="icon-selector">
                                            <?php
                                            $icons = ['fa-trophy', 'fa-medal', 'fa-certificate', 'fa-award', 'fa-star', 'fa-code'];
                                            foreach ($icons as $icon):
                                                $isSelected = ($icon == $ach['icon']) ? 'selected' : '';
                                            ?>
                                                <div class="icon-option <?php echo $isSelected; ?>" data-icon="<?php echo $icon; ?>" onclick="selectIcon(this)">
                                                    <i class="fa-solid <?php echo $icon; ?>"></i>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <input type="hidden" name="achievement_icon[]" value="<?php echo htmlspecialchars($ach['icon']); ?>" class="icon-input">
                                    </div>
                                    <button type="button" class="btn-remove" onclick="removeItem(this)">Remove</button>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                        <button type="button" class="btn-add" onclick="addAchievement()"><i class="fa-solid fa-plus"></i> Add Achievement</button>
                    </div>

                    <!-- Technologies -->
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-laptop-code"></i> Technologies</h3>
                        <p style="color: var(--text-secondary); margin-bottom: 15px; font-size: 0.9rem;">
                            <i class="fa-solid fa-info-circle"></i> Add categories like Frontend, Backend, Database, Multimedia, etc.
                        </p>
                        <div id="tech-container">
                            <?php if (empty($techCategories)): ?>
                                <div class="repeater-item">
                                    <div class="form-group">
                                        <label class="form-label">Category Name</label>
                                        <input type="text" name="tech_category[]" class="form-input" placeholder="e.g., Frontend, Backend, Multimedia">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Technologies (comma-separated)</label>
                                        <input type="text" name="tech_items[]" class="form-input" placeholder="HTML5, CSS3, JavaScript">
                                    </div>
                                </div>
                            <?php else: foreach ($techCategories as $cat): ?>
                                <div class="repeater-item">
                                    <div class="form-group">
                                        <label class="form-label">Category Name</label>
                                        <input type="text" name="tech_category[]" class="form-input" value="<?php echo htmlspecialchars($cat['category_name']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Technologies (comma-separated)</label>
                                        <input type="text" name="tech_items[]" class="form-input" value="<?php echo htmlspecialchars(implode(', ', array_column($cat['technologies'], 'tech_name'))); ?>">
                                    </div>
                                    <button type="button" class="btn-remove" onclick="removeItem(this)">Remove</button>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                        <button type="button" class="btn-add" onclick="addTech()"><i class="fa-solid fa-plus"></i> Add Category</button>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Save Resume</button>
                        <a href="<?php echo a($basePath); ?>/index.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="<?php echo a($basePath); ?>/js/script.js"></script>
    <script>
        let expCounter = <?php echo count($experiences); ?>;
        
        function removeItem(btn) {
            const container = btn.closest('.repeater-item');
            const parent = container.parentElement;
            
            if (parent.id === 'education-container' && parent.children.length === 1) {
                alert('At least one education entry is required!');
                return;
            }
            
            container.remove();
        }
        
        function addEducation() {
            const container = document.getElementById('education-container');
            const html = `
                <div class="repeater-item">
                    <div class="form-group">
                        <label class="form-label">Degree Program *</label>
                        <input type="text" name="education_degree[]" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Institution *</label>
                        <input type="text" name="education_institution[]" class="form-input" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Start Date</label>
                            <input type="text" name="education_start[]" class="form-input" placeholder="2023">
                        </div>
                        <div class="form-group">
                            <label class="form-label">End Date</label>
                            <input type="text" name="education_end[]" class="form-input" placeholder="Present">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="education_description[]" class="form-textarea" rows="3"></textarea>
                    </div>
                    <button type="button" class="btn-remove" onclick="removeItem(this)">Remove</button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }
        
        function addExperience() {
            const container = document.getElementById('experience-container');
            const html = `
                <div class="repeater-item">
                    <div class="form-group">
                        <label class="form-label">Job Title/Position</label>
                        <input type="text" name="experience_title[]" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Company/Project</label>
                        <input type="text" name="experience_company[]" class="form-input">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Start Date</label>
                            <input type="text" name="experience_start[]" class="form-input" placeholder="Jan 2023">
                        </div>
                        <div class="form-group">
                            <label class="form-label">End Date</label>
                            <input type="text" name="experience_end[]" class="form-input" placeholder="Present">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="experience_description[]" class="form-textarea" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Keywords (comma-separated)</label>
                        <input type="text" name="experience_keywords[]" class="form-input" placeholder="e.g., Machine Learning, Data Visualization, Analytics">
                        <small class="form-note">Enter keywords separated by commas. They will display as tags under the description.</small>
                    </div>
                    <button type="button" class="btn-remove" onclick="removeItem(this)">Remove</button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
            expCounter++;
        }
        
        function addAchievement() {
            const container = document.getElementById('achievement-container');
            const html = `
                <div class="repeater-item">
                    <div class="form-group">
                        <label class="form-label">Achievement Title</label>
                        <input type="text" name="achievement_title[]" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input type="text" name="achievement_date[]" class="form-input" placeholder="2024">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="achievement_description[]" class="form-textarea" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Select Icon</label>
                        <div class="icon-selector">
                            <div class="icon-option selected" data-icon="fa-trophy" onclick="selectIcon(this)">
                                <i class="fa-solid fa-trophy"></i>
                            </div>
                            <div class="icon-option" data-icon="fa-medal" onclick="selectIcon(this)">
                                <i class="fa-solid fa-medal"></i>
                            </div>
                            <div class="icon-option" data-icon="fa-certificate" onclick="selectIcon(this)">
                                <i class="fa-solid fa-certificate"></i>
                            </div>
                            <div class="icon-option" data-icon="fa-award" onclick="selectIcon(this)">
                                <i class="fa-solid fa-award"></i>
                            </div>
                            <div class="icon-option" data-icon="fa-star" onclick="selectIcon(this)">
                                <i class="fa-solid fa-star"></i>
                            </div>
                            <div class="icon-option" data-icon="fa-code" onclick="selectIcon(this)">
                                <i class="fa-solid fa-code"></i>
                            </div>
                        </div>
                        <input type="hidden" name="achievement_icon[]" value="fa-trophy" class="icon-input">
                    </div>
                    <button type="button" class="btn-remove" onclick="removeItem(this)">Remove</button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }
        
        function addTech() {
            const container = document.getElementById('tech-container');
            const html = `
                <div class="repeater-item">
                    <div class="form-group">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="tech_category[]" class="form-input" placeholder="e.g., Frontend, Backend, Multimedia">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Technologies (comma-separated)</label>
                        <input type="text" name="tech_items[]" class="form-input" placeholder="HTML5, CSS3, JavaScript">
                    </div>
                    <button type="button" class="btn-remove" onclick="removeItem(this)">Remove</button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }
        
        function selectIcon(element) {
            const container = element.closest('.icon-selector');
            container.querySelectorAll('.icon-option').forEach(opt => opt.classList.remove('selected'));
            element.classList.add('selected');
            
            const input = container.nextElementSibling;
            input.value = element.dataset.icon;
        }
        
        function toggleGlobalTrait(el) {
            if (!el) return;
            el.classList.toggle('selected');
            updateGlobalTraitInputs();
        }

        function updateGlobalTraitInputs() {
            const container = document.getElementById('experience-traits-global-inputs');
            if (!container) return;
            container.innerHTML = '';
            document.querySelectorAll('#experience-traits-global .trait-option.selected').forEach(opt => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'experience_traits_global[]';
                input.value = opt.dataset.value;
                container.appendChild(input);
            });
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateGlobalTraitInputs();
        });
    </script>
</body>
</html>