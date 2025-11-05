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
            ['platform' => 'LinkedIn', 'url' => trim($_POST['linkedin'] ?? ''), 'icon' => 'fa-brands fa-linkedin'],
            ['platform' => 'GitHub', 'url' => trim($_POST['github'] ?? ''), 'icon' => 'fa-brands fa-github'],
            ['platform' => 'Custom', 'url' => trim($_POST['custom_link'] ?? ''), 'icon' => 'fa-solid fa-link']
        ];
        
        $order = 0;
        foreach ($socialLinks as $link) {
            if (!empty($link['url'])) {
                $stmt = $db->prepare("INSERT INTO social_links (user_id, platform, url, icon, display_order) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $link['platform'], $link['url'], $link['icon'], $order++]);
            }
        }
        
        // Handle Education section flag
        $hasEducation = (isset($_POST['has_education']) && $_POST['has_education'] === 'yes');
        $stmt = $db->prepare("UPDATE users SET has_education = ? WHERE id = ?");
        $stmt->execute([$hasEducation, $userId]); // Pass as PHP boolean

        $db->prepare("DELETE FROM education WHERE user_id = ?")->execute([$userId]);

        if ($hasEducation && isset($_POST['education_degree'])) {
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

        // Handle Experience section flag
        $hasExperience = (isset($_POST['has_experience']) && $_POST['has_experience'] === 'yes');
        $stmt = $db->prepare("UPDATE users SET has_experience = ? WHERE id = ?");
        $stmt->execute([$hasExperience, $userId]); // Pass as PHP boolean

        $db->prepare("DELETE FROM experience WHERE user_id = ?")->execute([$userId]);

        if ($hasExperience && isset($_POST['experience_title'])) {
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

        // Handle Achievements section flag
        $hasAchievements = (isset($_POST['has_achievements']) && $_POST['has_achievements'] === 'yes');
        $stmt = $db->prepare("UPDATE users SET has_achievements = ? WHERE id = ?");
        $stmt->execute([$hasAchievements, $userId]); // Pass as PHP boolean

        $db->prepare("DELETE FROM achievements WHERE user_id = ?")->execute([$userId]);

        if ($hasAchievements && isset($_POST['achievement_title'])) {
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

        // Handle Technologies - NEW STRUCTURE
        $db->prepare("DELETE FROM user_technologies WHERE user_id = ?")->execute([$userId]);

        // Get available categories
        $categories = ['Frontend', 'Backend', 'Databases', 'DevOps', 'Multimedia', 'Mobile', 'Testing'];

        foreach ($categories as $category) {
            $fieldName = 'tech_' . strtolower(str_replace(' ', '_', $category));
            
            if (!empty($_POST[$fieldName]) && is_array($_POST[$fieldName])) {
                $displayOrder = 0;
                $hasOtherChecked = false;
                $customValue = '';
                
                // First pass: check if "Other" is selected and get custom value
                foreach ($_POST[$fieldName] as $tech) {
                    if (trim($tech) === 'Other') {
                        $hasOtherChecked = true;
                    } elseif (strpos($tech, 'custom:') === 0) {
                        $customValue = trim(substr($tech, 7));
                    }
                }
                
                // Second pass: insert technologies
                foreach ($_POST[$fieldName] as $tech) {
                    $tech = trim($tech);
                    if (empty($tech)) continue;
                    
                    // Skip the "custom:" entry - we'll handle it separately
                    if (strpos($tech, 'custom:') === 0) continue;
                    
                    $isCustom = false;
                    
                    // If this is "Other" and we have a custom value, use the custom value instead
                    if ($tech === 'Other' && !empty($customValue)) {
                        $tech = htmlspecialchars($customValue, ENT_QUOTES, 'UTF-8');
                        if (strlen($tech) > 100) {
                            $tech = substr($tech, 0, 100);
                        }
                        $isCustom = true;
                    } elseif ($tech === 'Other') {
                        // "Other" is checked but no custom value provided, skip it
                        continue;
                    }
                    
                    // Sanitize preset options too
                    $tech = htmlspecialchars($tech, ENT_QUOTES, 'UTF-8');
                    
                    $stmt = $db->prepare("INSERT INTO user_technologies (user_id, category, technology_name, is_custom, display_order) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$userId, $category, $tech, $isCustom, $displayOrder++]);
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

// Fetch user's selected technologies from NEW table
$userTechData = $db->prepare("SELECT category, technology_name, is_custom FROM user_technologies WHERE user_id = ? ORDER BY category, display_order");
$userTechData->execute([$userId]);
$userTechnologies = [];
foreach ($userTechData->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $userTechnologies[$row['category']][] = $row;
}

// Fetch preset technology options
$techOptionsData = $db->query("SELECT category, name FROM technology_options WHERE is_preset = TRUE ORDER BY category, display_order");
$techOptions = [];
foreach ($techOptionsData->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $techOptions[$row['category']][] = $row['name'];
}

$socialData = $db->prepare("SELECT * FROM social_links WHERE user_id = ? ORDER BY display_order");
$socialData->execute([$userId]);
$socialLinks = $socialData->fetchAll(PDO::FETCH_ASSOC);

// **FIX: Proper initialization of dropdowns based on flags AND existing data**
$hasEducation = ($userData['has_education'] ?? false) ? true : (count($educations) > 0);
$hasExperience = ($userData['has_experience'] ?? false) ? true : (count($experiences) > 0);
$hasAchievements = ($userData['has_achievements'] ?? false) ? true : (count($achievements) > 0);

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
        .btn-remove { background: #5db8b1; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .btn-remove:hover { background: #4d9b94; }
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
        .section-toggle { margin-bottom: 20px; padding: 15px; background: var(--bg-secondary); border-radius: 8px; border: 2px solid var(--border-color); }
        .section-toggle label { font-weight: 700; color: var(--text-primary); margin-right: 10px; }
        .section-toggle select { padding: 8px 12px; border-radius: 6px; border: 2px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-weight: 600; cursor: pointer; }
        .section-content { display: none; }
        .section-content.active { display: block; }
        .no-data-message { padding: 15px; background: rgba(239, 35, 60, 0.1); border: 2px solid rgba(239, 35, 60, 0.3); border-radius: 8px; color: var(--accent-color); font-weight: 600; text-align: center; }
        
        .tech-category-section { margin-bottom: 25px; padding: 20px; background: var(--bg-secondary); border-radius: 12px; border: 1px solid var(--border-color); }
        .tech-category-title { font-weight: 700; color: var(--accent-color); margin-bottom: 15px; font-size: 1.1rem; }
        .tech-checkboxes { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; }
        .tech-checkbox-item { display: flex; align-items: center; gap: 8px; padding: 8px; background: var(--bg-primary); border-radius: 6px; border: 1px solid var(--border-color); transition: all 0.3s; }
        .tech-checkbox-item:hover { background: var(--bg-secondary); border-color: var(--accent-color); }
        .tech-checkbox-item input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
        .tech-checkbox-item label { cursor: pointer; font-weight: 500; color: var(--text-primary); flex: 1; }
        .custom-tech-input { margin-top: 10px; display: none; }
        .custom-tech-input.active { display: block; }
        .custom-tech-input input { width: 100%; padding: 10px; border-radius: 6px; border: 2px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); }
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
                        <a href="<?php echo a($basePath); ?>/logout.php" class="logout-link">Log Out</a>
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

                    <!-- Education Section with Yes/No Toggle -->
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-graduation-cap"></i> Education</h3>
                        
                        <div class="section-toggle">
                            <label for="has_education">Do you have any Education?</label>
                            <select name="has_education" id="has_education" onchange="toggleSection('education', this.value)">
                                <option value="no" <?php echo !$hasEducation ? 'selected' : ''; ?>>No</option>
                                <option value="yes" <?php echo $hasEducation ? 'selected' : ''; ?>>Yes</option>
                            </select>
                        </div>
                        
                        <div id="education-section-content" class="section-content <?php echo $hasEducation ? 'active' : ''; ?>">
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
                        
                        <div id="education-no-data" class="no-data-message" style="display: <?php echo !$hasEducation ? 'block' : 'none'; ?>;">
                            No education entries will be displayed on your resume.
                        </div>
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

                    <!-- Experience Section with Yes/No Toggle -->
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-briefcase"></i> Experience</h3>
                        
                        <div class="section-toggle">
                            <label for="has_experience">Do you have any Experience?</label>
                            <select name="has_experience" id="has_experience" onchange="toggleSection('experience', this.value)">
                                <option value="no" <?php echo !$hasExperience ? 'selected' : ''; ?>>No</option>
                                <option value="yes" <?php echo $hasExperience ? 'selected' : ''; ?>>Yes</option>
                            </select>
                        </div>
                        
                        <div id="experience-section-content" class="section-content <?php echo $hasExperience ? 'active' : ''; ?>">
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
                        
                        <div id="experience-no-data" class="no-data-message" style="display: <?php echo !$hasExperience ? 'block' : 'none'; ?>;">
                            No experience entries will be displayed on your resume.
                        </div>
                    </div>

                    <!-- Achievements Section with Yes/No Toggle -->
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-trophy"></i> Achievements</h3>
                        
                        <div class="section-toggle">
                            <label for="has_achievements">Do you have any Achievements?</label>
                            <select name="has_achievements" id="has_achievements" onchange="toggleSection('achievements', this.value)">
                                <option value="no" <?php echo !$hasAchievements ? 'selected' : ''; ?>>No</option>
                                <option value="yes" <?php echo $hasAchievements ? 'selected' : ''; ?>>Yes</option>
                            </select>
                        </div>
                        
                        <div id="achievements-section-content" class="section-content <?php echo $hasAchievements ? 'active' : ''; ?>">
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
                        
                        <div id="achievements-no-data" class="no-data-message" style="display: <?php echo !$hasAchievements ? 'block' : 'none'; ?>;">
                            No achievement entries will be displayed on your resume.
                        </div>
                    </div>

                    <!-- Technologies Section with Multi-Select -->
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-laptop-code"></i> Technologies</h3>
                        <p style="color: var(--text-secondary); margin-bottom: 15px; font-size: 0.9rem;">
                            <i class="fa-solid fa-info-circle"></i> Select technologies from predefined categories or add custom ones.
                        </p>
                        
                        <?php
                        $categories = ['Frontend', 'Backend', 'Databases', 'DevOps', 'Multimedia', 'Mobile', 'Testing'];
                        
                        foreach ($categories as $category):
                            $fieldName = 'tech_' . strtolower(str_replace(' ', '_', $category));
                            $userSelectedInCategory = $userTechnologies[$category] ?? [];
                            
                            // Find if user has a custom "Other" value for this category
                            $hasCustomOther = false;
                            $customOtherValue = '';
                            foreach ($userSelectedInCategory as $selected) {
                                if ($selected['is_custom']) {
                                    $hasCustomOther = true;
                                    $customOtherValue = $selected['technology_name'];
                                    break;
                                }
                            }
                        ?>
                        <div class="tech-category-section">
                            <h4 class="tech-category-title"><?php echo htmlspecialchars($category); ?></h4>
                            <div class="tech-checkboxes">
                                <?php
                                $options = $techOptions[$category] ?? [];
                                foreach ($options as $techName):
                                    $isChecked = false;
                                    foreach ($userSelectedInCategory as $selected) {
                                        if ($selected['technology_name'] === $techName && !$selected['is_custom']) {
                                            $isChecked = true;
                                            break;
                                        }
                                    }
                                    
                                    // If this is "Other" and user has custom value, check it
                                    if ($techName === 'Other' && $hasCustomOther) {
                                        $isChecked = true;
                                    }
                                    
                                    $inputId = 'tech_' . md5($category . $techName);
                                    $isOther = ($techName === 'Other');
                                ?>
                                    <div class="tech-checkbox-item">
                                        <input 
                                            type="checkbox" 
                                            id="<?php echo $inputId; ?>" 
                                            name="<?php echo $fieldName; ?>[]" 
                                            value="<?php echo htmlspecialchars($techName); ?>"
                                            <?php echo $isChecked ? 'checked' : ''; ?>
                                            <?php echo $isOther ? 'onchange="toggleCustomInput(this, \'' . $category . '\')"' : ''; ?>
                                        >
                                        <label for="<?php echo $inputId; ?>"><?php echo htmlspecialchars($techName); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Custom input field for "Other" -->
                            <div class="custom-tech-input <?php echo $hasCustomOther ? 'active' : ''; ?>" id="custom-<?php echo strtolower($category); ?>">
                                <label class="form-label">Custom <?php echo htmlspecialchars($category); ?> Technology</label>
                                <input 
                                    type="text" 
                                    name="<?php echo $fieldName; ?>[]" 
                                    class="form-input" 
                                    placeholder="Enter custom technology name"
                                    value="<?php echo $hasCustomOther ? htmlspecialchars('custom:' . $customOtherValue) : ''; ?>"
                                >
                                <small class="form-note">This will only be saved if "Other" is checked above.</small>
                            </div>
                        </div>
                        <?php endforeach; ?>
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
        // Section toggle logic
        function toggleSection(sectionName, value) {
            const content = document.getElementById(sectionName + '-section-content');
            const noData = document.getElementById(sectionName + '-no-data');
            
            if (value === 'yes') {
                content.classList.add('active');
                if (noData) noData.style.display = 'none';
            } else {
                content.classList.remove('active');
                if (noData) noData.style.display = 'block';
            }
        }
        
        // Custom tech input toggle
        function toggleCustomInput(checkbox, category) {
            const customInput = document.getElementById('custom-' + category.toLowerCase());
            if (checkbox.checked) {
                customInput.classList.add('active');
            } else {
                customInput.classList.remove('active');
                // Clear the custom input value
                const input = customInput.querySelector('input');
                if (input) input.value = '';
            }
        }
        
        // Initialize custom inputs on page load
        document.addEventListener('DOMContentLoaded', function() {
            const categories = ['Frontend', 'Backend', 'Databases', 'DevOps', 'Multimedia', 'Mobile', 'Testing'];
            categories.forEach(category => {
                const otherCheckbox = document.querySelector('input[value="Other"][name*="' + category.toLowerCase() + '"]');
                if (otherCheckbox && otherCheckbox.checked) {
                    const customInput = document.getElementById('custom-' + category.toLowerCase());
                    if (customInput) {
                        customInput.classList.add('active');
                    }
                }
            });
        });
        
        function removeItem(btn) {
            const container = btn.closest('.repeater-item');
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