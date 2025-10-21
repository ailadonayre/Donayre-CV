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
        $stmt = $db->prepare("UPDATE users SET fullname = ?, title = ?, email = ?, contact = ?, address = ?, age = ?, profile_summary = ? WHERE id = ?");
        $stmt->execute([
            trim($_POST['fullname']),
            trim($_POST['title']),
            trim($_POST['email']),
            trim($_POST['contact']),
            trim($_POST['address']),
            trim($_POST['age']),
            trim($_POST['summary']),
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
                    
                    // Handle Experience Traits
                    if (isset($_POST['experience_traits'][$index]) && is_array($_POST['experience_traits'][$index])) {
                        foreach ($_POST['experience_traits'][$index] as $trait) {
                            list($icon, $label) = explode('|', $trait);
                            $stmt = $db->prepare("INSERT INTO experience_traits (experience_id, trait_icon, trait_label) VALUES (?, ?, ?)");
                            $stmt->execute([$expId, $icon, $label]);
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
                        $techOrder = 0;
                        foreach ($technologies as $tech) {
                            $stmt = $db->prepare("INSERT INTO technologies (category_id, tech_name, display_order) VALUES (?, ?, ?)");
                            $stmt->execute([$catId, $tech, $techOrder++]);
                        }
                    }
                }
            }
        }
        
        $db->commit();
        $sessionManager->setFlash('success', 'Resume updated successfully!');
        header("Location: public_resume.php?id=$userId");
        exit;
        
    } catch (PDOException $e) {
        $db->rollBack();
        $error = "Failed to save: " . $e->getMessage();
    }
}

// Load existing data for editing
$educationData = $db->prepare("SELECT * FROM education WHERE user_id = ? ORDER BY display_order");
$educationData->execute([$userId]);
$educations = $educationData->fetchAll(PDO::FETCH_ASSOC);

$experienceData = $db->prepare("SELECT e.*, GROUP_CONCAT(CONCAT(et.trait_icon, '|', et.trait_label) SEPARATOR ';;') as traits FROM experience e LEFT JOIN experience_traits et ON e.id = et.experience_id WHERE e.user_id = ? GROUP BY e.id ORDER BY e.display_order");
$experienceData->execute([$userId]);
$experiences = $experienceData->fetchAll(PDO::FETCH_ASSOC);

$achievementData = $db->prepare("SELECT * FROM achievements WHERE user_id = ? ORDER BY display_order");
$achievementData->execute([$userId]);
$achievements = $achievementData->fetchAll(PDO::FETCH_ASSOC);

$techData = $db->prepare("SELECT tc.*, GROUP_CONCAT(t.tech_name ORDER BY t.display_order SEPARATOR ', ') as technologies FROM tech_categories tc LEFT JOIN technologies t ON tc.id = t.category_id WHERE tc.user_id = ? GROUP BY tc.id ORDER BY tc.display_order");
$techData->execute([$userId]);
$techCategories = $techData->fetchAll(PDO::FETCH_ASSOC);

$socialData = $db->prepare("SELECT * FROM social_links WHERE user_id = ? ORDER BY display_order");
$socialData->execute([$userId]);
$socialLinks = $socialData->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Resume</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/resume.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .repeater-item { background: var(--bg-secondary); padding: 20px; border-radius: 12px; margin-bottom: 15px; border: 1px solid var(--border-color); }
        .btn-remove { background: #e74c3c; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn-add { background: var(--accent-color); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; margin-top: 10px; }
        .trait-selector { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
        .trait-option { padding: 8px 16px; border: 2px solid var(--border-color); border-radius: 8px; cursor: pointer; transition: all 0.3s; }
        .trait-option.selected { background: var(--accent-color); color: white; border-color: var(--accent-color); }
        .icon-selector { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
        .icon-option { width: 50px; height: 50px; border: 2px solid var(--border-color); border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 1.5rem; transition: all 0.3s; }
        .icon-option.selected { background: var(--accent-color); color: white; border-color: var(--accent-color); }
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
                        <a href="public_resume.php?id=<?php echo $userId; ?>" class="logout-link">View Resume</a>
                        <a href="logout.php" class="logout-link">Log out</a>
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

                <form method="POST" class="resume-form">
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
                        <div class="form-group">
                            <label class="form-label">Summary</label>
                            <textarea name="summary" class="form-textarea" rows="4"><?php echo htmlspecialchars($userData['profile_summary'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Social Links -->
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-link"></i> Social Links (Max 3)</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">LinkedIn</label>
                                <input type="url" name="linkedin" class="form-input" value="<?php echo htmlspecialchars($socialLinks[0]['url'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">GitHub</label>
                                <input type="url" name="github" class="form-input" value="<?php echo htmlspecialchars($socialLinks[1]['url'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Custom Link (Optional)</label>
                            <input type="url" name="custom_link" class="form-input" value="<?php echo htmlspecialchars($socialLinks[2]['url'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Education (At least 1 required) -->
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-graduation-cap"></i> Education (At least 1)</h3>
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
                        <button type="button" class="btn-add" onclick="addEducation()">+ Add Education</button>
                    </div>

                    <!-- Experience (Optional) -->
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-briefcase"></i> Experience</h3>
                        <div id="experience-container">
                            <?php foreach ($experiences as $exp): 
                                $traits = $exp['traits'] ? explode(';;', $exp['traits']) : [];
                            ?>
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
                                        <label class="form-label">Select Traits (Choose up to 4)</label>
                                        <div class="trait-selector" data-index="<?php echo array_search($exp, $experiences); ?>">
                                            <?php
                                            $availableTraits = [
                                                'fa-code|Academic Projects',
                                                'fa-users|Team Leadership',
                                                'fa-lightbulb|Problem Solving',
                                                'fa-rotate-right|Adaptability',
                                                'fa-rocket|Innovation',
                                                'fa-chart-line|Growth',
                                                'fa-handshake|Collaboration'
                                            ];
                                            foreach ($availableTraits as $trait):
                                                $isSelected = in_array($trait, $traits) ? 'selected' : '';
                                            ?>
                                                <div class="trait-option <?php echo $isSelected; ?>" data-value="<?php echo $trait; ?>" onclick="toggleTrait(this)">
                                                    <i class="fa-solid <?php echo explode('|', $trait)[0]; ?>"></i> <?php echo explode('|', $trait)[1]; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <input type="hidden" name="experience_traits[<?php echo array_search($exp, $experiences); ?>][]" class="trait-input">
                                    </div>
                                    <button type="button" class="btn-remove" onclick="removeItem(this)">Remove</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn-add" onclick="addExperience()">+ Add Experience</button>
                    </div>

                    <!-- Achievements (Optional) -->
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-trophy"></i> Achievements</h3>
                        <div id="achievement-container">
                            <?php foreach ($achievements as $ach): ?>
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
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn-add" onclick="addAchievement()">+ Add Achievement</button>
                    </div>

                    <!-- Technologies -->
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-laptop-code"></i> Technologies</h3>
                        <div id="tech-container">
                            <?php if (empty($techCategories)): ?>
                                <div class="repeater-item">
                                    <div class="form-group">
                                        <label class="form-label">Category Name</label>
                                        <input type="text" name="tech_category[]" class="form-input" placeholder="e.g., Frontend">
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
                                        <input type="text" name="tech_items[]" class="form-input" value="<?php echo htmlspecialchars($cat['technologies']); ?>">
                                    </div>
                                    <button type="button" class="btn-remove" onclick="removeItem(this)">Remove</button>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                        <button type="button" class="btn-add" onclick="addTech()">+ Add Category</button>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Save & View Resume</button>
                        <a href="public_resume.php?id=<?php echo $userId; ?>" class="btn-secondary"><i class="fa-solid fa-eye"></i> Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="js/script.js"></script>
    <script>
        let expCounter = <?php echo count($experiences); ?>;
        let achCounter = <?php echo count($achievements); ?>;
        
        function removeItem(btn) {
            const container = btn.closest('.repeater-item');
            const parent = container.parentElement;
            
            // Don't remove if it's the last education item
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
                            <input type="text" name="experience_start[]" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">End Date</label>
                            <input type="text" name="experience_end[]" class="form-input">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="experience_description[]" class="form-textarea" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Select Traits (Choose up to 4)</label>
                        <div class="trait-selector" data-index="${expCounter}">
                            <div class="trait-option" data-value="fa-code|Academic Projects" onclick="toggleTrait(this)">
                                <i class="fa-solid fa-code"></i> Academic Projects
                            </div>
                            <div class="trait-option" data-value="fa-users|Team Leadership" onclick="toggleTrait(this)">
                                <i class="fa-solid fa-users"></i> Team Leadership
                            </div>
                            <div class="trait-option" data-value="fa-lightbulb|Problem Solving" onclick="toggleTrait(this)">
                                <i class="fa-solid fa-lightbulb"></i> Problem Solving
                            </div>
                            <div class="trait-option" data-value="fa-rotate-right|Adaptability" onclick="toggleTrait(this)">
                                <i class="fa-solid fa-rotate-right"></i> Adaptability
                            </div>
                            <div class="trait-option" data-value="fa-rocket|Innovation" onclick="toggleTrait(this)">
                                <i class="fa-solid fa-rocket"></i> Innovation
                            </div>
                            <div class="trait-option" data-value="fa-chart-line|Growth" onclick="toggleTrait(this)">
                                <i class="fa-solid fa-chart-line"></i> Growth
                            </div>
                            <div class="trait-option" data-value="fa-handshake|Collaboration" onclick="toggleTrait(this)">
                                <i class="fa-solid fa-handshake"></i> Collaboration
                            </div>
                        </div>
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
                        <input type="text" name="achievement_date[]" class="form-input">
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
            achCounter++;
        }
        
        function addTech() {
            const container = document.getElementById('tech-container');
            const html = `
                <div class="repeater-item">
                    <div class="form-group">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="tech_category[]" class="form-input" placeholder="e.g., Frontend">
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
        
        function toggleTrait(element) {
            const container = element.closest('.trait-selector');
            const selected = container.querySelectorAll('.trait-option.selected');
            
            if (element.classList.contains('selected')) {
                element.classList.remove('selected');
            } else {
                if (selected.length >= 4) {
                    alert('You can only select up to 4 traits!');
                    return;
                }
                element.classList.add('selected');
            }
            
            updateTraitInputs(container);
        }
        
        function updateTraitInputs(container) {
            const index = container.dataset.index;
            const selected = container.querySelectorAll('.trait-option.selected');
            const parent = container.closest('.repeater-item');
            
            // Remove old hidden inputs
            parent.querySelectorAll('input[name^="experience_traits"]').forEach(inp => inp.remove());
            
            // Add new hidden inputs for each selected trait
            selected.forEach(trait => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `experience_traits[${index}][]`;
                input.value = trait.dataset.value;
                parent.appendChild(input);
            });
        }
        
        function selectIcon(element) {
            const container = element.closest('.icon-selector');
            container.querySelectorAll('.icon-option').forEach(opt => opt.classList.remove('selected'));
            element.classList.add('selected');
            
            const input = container.nextElementSibling;
            input.value = element.dataset.icon;
        }
        
        // Initialize trait inputs on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.trait-selector').forEach(container => {
                updateTraitInputs(container);
            });
        });
    </script>
</body>
</html>