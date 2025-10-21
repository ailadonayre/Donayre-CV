<?php
require_once 'config.php';
require_once 'session_manager.php';
require_once 'validator.php';

$sessionManager = new SessionManager();
$validator = new Validator();

// Require login
$sessionManager->requireLogin('login.php');

$currentUser = $sessionManager->getCurrentUser();
$userId = $currentUser['id'];

$error = '';
$success = '';

// Fetch current resume data
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        $error = "User data not found";
    }
} catch (PDOException $e) {
    $error = "Failed to load resume data: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    // Sanitize and validate inputs
    $fullname = trim($_POST['fullname'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $education = trim($_POST['education'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $linkedin = trim($_POST['linkedin'] ?? '');
    $github = trim($_POST['github'] ?? '');
    
    // Server-side validation
    $validationErrors = [];
    
    if (empty($fullname)) {
        $validationErrors[] = "Full name is required";
    } elseif (strlen($fullname) > 100) {
        $validationErrors[] = "Full name must be less than 100 characters";
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $validationErrors[] = "Please enter a valid email address";
    }
    
    if (!empty($age) && (!is_numeric($age) || $age < 1 || $age > 150)) {
        $validationErrors[] = "Please enter a valid age";
    }
    
    if (!empty($linkedin) && !filter_var($linkedin, FILTER_VALIDATE_URL)) {
        $validationErrors[] = "Please enter a valid LinkedIn URL";
    }
    
    if (!empty($github) && !filter_var($github, FILTER_VALIDATE_URL)) {
        $validationErrors[] = "Please enter a valid GitHub URL";
    }
    
    if (!empty($validationErrors)) {
        $error = implode('. ', $validationErrors);
    } else {
        // Update database using parameterized query
        try {
            $query = "UPDATE users SET 
                fullname = ?, 
                contact = ?, 
                email = ?, 
                address = ?, 
                age = ?, 
                title = ?, 
                summary = ?, 
                skills = ?, 
                education = ?, 
                experience = ?,
                linkedin = ?,
                github = ?
                WHERE id = ?";
            
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                $fullname,
                $contact,
                $email,
                $address,
                $age,
                $title,
                $summary,
                $skills,
                $education,
                $experience,
                $linkedin,
                $github,
                $userId
            ]);
            
            if ($result) {
                // Refresh user data
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $sessionManager->setFlash('success', 'Resume updated successfully!');
                header('Location: edit_resume.php');
                exit;
            } else {
                $error = "Failed to update resume. Please try again.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

$success = $sessionManager->getFlash('success');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Resume - <?php echo htmlspecialchars($currentUser['username']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/resume.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <button class="dark-mode-toggle" id="darkModeToggle" aria-label="Toggle dark mode">
        <i class="fa-solid fa-moon"></i>
    </button>

    <?php if ($success): ?>
    <div class="success-notification" id="successNotification">
        <div class="notification-content">
            <i class="fa-solid fa-circle-check"></i>
            <span><?php echo htmlspecialchars($success); ?></span>
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
                    <h1 class="name">Edit Resume</h1>
                    <h2 class="title">Update Your Information</h2>
                    <p class="user-welcome">
                        Editing as <strong><?php echo htmlspecialchars($currentUser['username']); ?></strong>
                        <a href="index.php" class="logout-link">View Resume</a>
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
                    <div class="alert alert-error">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="resume-form" id="resumeForm">
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fa-solid fa-user"></i>
                            Personal Information
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="fullname" class="form-label">Full Name *</label>
                                <input 
                                    type="text" 
                                    id="fullname" 
                                    name="fullname" 
                                    class="form-input"
                                    value="<?php echo htmlspecialchars($userData['fullname'] ?? ''); ?>"
                                    placeholder="Enter your full name"
                                    required
                                    maxlength="100"
                                >
                            </div>

                            <div class="form-group">
                                <label for="title" class="form-label">Professional Title</label>
                                <input 
                                    type="text" 
                                    id="title" 
                                    name="title" 
                                    class="form-input"
                                    value="<?php echo htmlspecialchars($userData['title'] ?? ''); ?>"
                                    placeholder="e.g., Computer Science Student"
                                    maxlength="100"
                                >
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address</label>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    class="form-input"
                                    value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>"
                                    placeholder="your.email@example.com"
                                    maxlength="100"
                                >
                            </div>

                            <div class="form-group">
                                <label for="contact" class="form-label">Contact Number</label>
                                <input 
                                    type="text" 
                                    id="contact" 
                                    name="contact" 
                                    class="form-input"
                                    value="<?php echo htmlspecialchars($userData['contact'] ?? ''); ?>"
                                    placeholder="+63 123 456 7890"
                                    maxlength="50"
                                >
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="age" class="form-label">Age</label>
                                <input 
                                    type="number" 
                                    id="age" 
                                    name="age" 
                                    class="form-input"
                                    value="<?php echo htmlspecialchars($userData['age'] ?? ''); ?>"
                                    placeholder="Your age"
                                    min="1"
                                    max="150"
                                >
                            </div>

                            <div class="form-group">
                                <label for="address" class="form-label">Address</label>
                                <input 
                                    type="text" 
                                    id="address" 
                                    name="address" 
                                    class="form-input"
                                    value="<?php echo htmlspecialchars($userData['address'] ?? ''); ?>"
                                    placeholder="City, Province, Country"
                                    maxlength="200"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fa-solid fa-link"></i>
                            Social Links
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="linkedin" class="form-label">LinkedIn Profile</label>
                                <input 
                                    type="url" 
                                    id="linkedin" 
                                    name="linkedin" 
                                    class="form-input"
                                    value="<?php echo htmlspecialchars($userData['linkedin'] ?? ''); ?>"
                                    placeholder="https://linkedin.com/in/yourprofile"
                                    maxlength="200"
                                >
                            </div>

                            <div class="form-group">
                                <label for="github" class="form-label">GitHub Profile</label>
                                <input 
                                    type="url" 
                                    id="github" 
                                    name="github" 
                                    class="form-input"
                                    value="<?php echo htmlspecialchars($userData['github'] ?? ''); ?>"
                                    placeholder="https://github.com/yourusername"
                                    maxlength="200"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fa-solid fa-file-lines"></i>
                            Professional Summary
                        </h3>
                        
                        <div class="form-group">
                            <label for="summary" class="form-label">About You</label>
                            <textarea 
                                id="summary" 
                                name="summary" 
                                class="form-textarea"
                                rows="4"
                                placeholder="Write a brief summary about yourself, your goals, and what makes you unique..."
                                maxlength="1000"
                            ><?php echo htmlspecialchars($userData['summary'] ?? ''); ?></textarea>
                            <span class="char-count" id="summaryCount">0 / 1000</span>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fa-solid fa-code"></i>
                            Skills & Technologies
                        </h3>
                        
                        <div class="form-group">
                            <label for="skills" class="form-label">Your Skills</label>
                            <textarea 
                                id="skills" 
                                name="skills" 
                                class="form-textarea"
                                rows="4"
                                placeholder="List your skills (e.g., Python, JavaScript, PostgreSQL, Machine Learning...)"
                                maxlength="1000"
                            ><?php echo htmlspecialchars($userData['skills'] ?? ''); ?></textarea>
                            <span class="char-count" id="skillsCount">0 / 1000</span>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fa-solid fa-graduation-cap"></i>
                            Education
                        </h3>
                        
                        <div class="form-group">
                            <label for="education" class="form-label">Educational Background</label>
                            <textarea 
                                id="education" 
                                name="education" 
                                class="form-textarea"
                                rows="5"
                                placeholder="List your educational qualifications, degrees, certifications..."
                                maxlength="2000"
                            ><?php echo htmlspecialchars($userData['education'] ?? ''); ?></textarea>
                            <span class="char-count" id="educationCount">0 / 2000</span>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fa-solid fa-briefcase"></i>
                            Experience
                        </h3>
                        
                        <div class="form-group">
                            <label for="experience" class="form-label">Work Experience & Projects</label>
                            <textarea 
                                id="experience" 
                                name="experience" 
                                class="form-textarea"
                                rows="6"
                                placeholder="Describe your work experience, projects, achievements..."
                                maxlength="3000"
                            ><?php echo htmlspecialchars($userData['experience'] ?? ''); ?></textarea>
                            <span class="char-count" id="experienceCount">0 / 3000</span>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fa-solid fa-save"></i>
                            Save Changes
                        </button>
                        <a href="index.php" class="btn-secondary">
                            <i class="fa-solid fa-eye"></i>
                            Preview Resume
                        </a>
                        <a href="public_resume.php?id=<?php echo $userId; ?>" class="btn-secondary" target="_blank">
                            <i class="fa-solid fa-share-nodes"></i>
                            Public View
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="js/script.js"></script>
    <script>
        // Character counters
        function updateCharCount(textareaId, countId, maxLength) {
            const textarea = document.getElementById(textareaId);
            const counter = document.getElementById(countId);
            
            if (textarea && counter) {
                const updateCount = () => {
                    const length = textarea.value.length;
                    counter.textContent = `${length} / ${maxLength}`;
                    counter.style.color = length > maxLength * 0.9 ? 'var(--accent-color)' : 'var(--text-muted)';
                };
                
                updateCount();
                textarea.addEventListener('input', updateCount);
            }
        }

        updateCharCount('summary', 'summaryCount', 1000);
        updateCharCount('skills', 'skillsCount', 1000);
        updateCharCount('education', 'educationCount', 2000);
        updateCharCount('experience', 'experienceCount', 3000);

        // Form validation
        document.getElementById('resumeForm').addEventListener('submit', function(e) {
            const fullname = document.getElementById('fullname').value.trim();
            
            if (!fullname) {
                e.preventDefault();
                alert('Please enter your full name');
                document.getElementById('fullname').focus();
                return false;
            }
        });

        // Success notification auto-close
        function closeNotification() {
            const notification = document.getElementById('successNotification');
            if (notification) {
                notification.style.animation = 'slideOut 0.3s ease forwards';
                setTimeout(() => notification.remove(), 300);
            }
        }

        <?php if ($success): ?>
        setTimeout(closeNotification, 5000);
        <?php endif; ?>
    </script>
</body>
</html>