<?php
session_start();
include 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $companyName = trim($_POST['company_name'] ?? '');
    $jobTitle = trim($_POST['job_title'] ?? '');
    $package = trim($_POST['package'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $jobType = $_POST['job_type'] ?? 'full_time';
    $experienceLevel = trim($_POST['experience_level'] ?? '');
    $skillsRequired = $_POST['skills_required'] ?? [];
    $jobDescription = trim($_POST['job_description'] ?? '');

    // Validation
    $errors = [];
    if (empty($companyName)) {
        $errors[] = 'Company name is required.';
    }
    if (empty($jobTitle)) {
        $errors[] = 'Job title is required.';
    }
    if (empty($package)) {
        $errors[] = 'Package is required.';
    }
    if (empty($location)) {
        $errors[] = 'Location is required.';
    }
    if (empty($experienceLevel)) {
        $errors[] = 'Experience level is required.';
    }
    if (empty($jobDescription)) {
        $errors[] = 'Job description is required.';
    }

    if (empty($errors)) {
        try {
            $conn->begin_transaction();

            // Insert new job
            $stmt = $conn->prepare("
                INSERT INTO jobs (company_name, job_title, package, location, job_type, experience_level, skills_required, job_description, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            
            $skillsJson = json_encode($skillsRequired);
            $stmt->bind_param("ssssssss", $companyName, $jobTitle, $package, $location, $jobType, $experienceLevel, $skillsJson, $jobDescription);
            
            if ($stmt->execute()) {
                $conn->commit();
                $message = 'Job created successfully!';
                $messageType = 'success';
                
                // Clear form data
                $_POST = array();
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Error creating job: ' . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = implode('<br>', $errors);
        $messageType = 'error';
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Play Smart - Add New Job</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ===== GLOBAL STYLES ===== */
        :root {
            --primary-color: #6A11CB;
            --primary-light: #8A3FE8;
            --primary-dark: #4A0C8F;
            --secondary-color: #2575FC;
            --secondary-light: #4A94FF;
            --secondary-dark: #1A5AC0;
            --accent-color: #FFC107;
            --accent-light: #FFD54F;
            --accent-dark: #FFA000;
            --success-color: #4CAF50;
            --error-color: #F44336;
            --warning-color: #FF9800;
            --info-color: #2196F3;
            --text-light: #FFFFFF;
            --text-dark: #333333;
            --text-muted: #6C757D;
            --bg-light: #F8F9FA;
            --bg-dark: #212529;
            --border-color: #DEE2E6;
            --shadow-color: rgba(0, 0, 0, 0.1);
            
            --font-family: 'Poppins', sans-serif;
            --font-size-xs: 0.75rem;
            --font-size-sm: 0.875rem;
            --font-size-md: 1rem;
            --font-size-lg: 1.125rem;
            --font-size-xl: 1.25rem;
            --font-size-2xl: 1.5rem;
            --font-size-3xl: 1.875rem;
            --font-size-4xl: 2.25rem;
            
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            
            --border-radius-sm: 0.25rem;
            --border-radius-md: 0.5rem;
            --border-radius-lg: 1rem;
            --border-radius-xl: 1.5rem;
            --border-radius-full: 9999px;
            
            --transition-fast: 0.2s ease;
            --transition-normal: 0.3s ease;
            --transition-slow: 0.5s ease;
            
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1), 0 4px 6px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.1), 0 10px 10px rgba(0, 0, 0, 0.04);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            font-family: var(--font-family);
            font-size: var(--font-size-md);
            line-height: 1.6;
            color: var(--text-dark);
            background-color: var(--bg-light);
            scroll-behavior: smooth;
        }

        .dashboard-body {
            background-color: var(--primary-dark);
            color: var(--text-light);
            min-height: 100vh;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-dark), var(--secondary-dark));
            color: var(--text-light);
            overflow-y: auto;
            z-index: 1000;
            transition: left var(--transition-normal);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar-header {
            padding: var(--spacing-xl) var(--spacing-lg);
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header .logo {
            width: 40px;
            height: 40px;
            background: var(--accent-color);
            color: var(--text-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: var(--font-size-lg);
            border-radius: var(--border-radius-md);
            margin-right: var(--spacing-md);
        }

        .sidebar-header h3 {
            font-size: var(--font-size-xl);
            font-weight: 600;
            background: linear-gradient(to right, var(--accent-color), var(--accent-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-menu {
            flex: 1;
            padding: var(--spacing-lg) 0;
        }

        .sidebar-menu ul li {
            margin-bottom: var(--spacing-sm);
        }

        .sidebar-menu ul li a {
            display: flex;
            align-items: center;
            padding: var(--spacing-md) var(--spacing-xl);
            color: var(--text-light);
            opacity: 0.7;
            transition: all var(--transition-fast);
            border-radius: 0 var(--border-radius-md) var(--border-radius-md) 0;
        }

        .sidebar-menu ul li a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            opacity: 1;
        }

        .sidebar-menu ul li.active a {
            background-color: var(--accent-color);
            color: var(--text-dark);
            opacity: 1;
        }

        .sidebar-menu ul li a i {
            margin-right: var(--spacing-md);
            font-size: var(--font-size-lg);
            width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            padding: var(--spacing-md) var(--spacing-lg);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-footer a {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.7);
        }

        .sidebar-footer a:hover {
            color: var(--accent-color);
        }

        .sidebar-footer a i {
            margin-right: var(--spacing-md);
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, var(--primary-dark), var(--secondary-dark));
            overflow-x: hidden;
            margin-left: 0;
            transition: margin-left var(--transition-normal);
        }

        @media (min-width: 769px) {
            .sidebar {
                left: 0;
            }
            .main-content {
                margin-left: 280px;
            }
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-lg);
            background-color: rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
        }

        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text-light);
            font-size: var(--font-size-xl);
            margin-right: var(--spacing-md);
            cursor: pointer;
        }

        .content-header h1 {
            font-size: var(--font-size-2xl);
            font-weight: 600;
        }

        .content-body {
            padding: var(--spacing-lg);
        }

        .card {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: var(--border-radius-lg);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: var(--spacing-lg);
            overflow: hidden;
        }

        .card-header {
            padding: var(--spacing-lg);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .card-header h2 {
            font-size: var(--font-size-xl);
            margin-bottom: var(--spacing-xs);
        }

        .card-header p {
            color: rgba(255, 255, 255, 0.7);
            font-size: var(--font-size-sm);
        }

        .card-body {
            padding: var(--spacing-lg);
        }

        .form {
            width: 100%;
        }

        .form-row {
            margin-bottom: var(--spacing-lg);
        }

        .form-row.two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-md);
        }

        .form-group {
            position: relative;
            margin-bottom: var(--spacing-md);
        }

        .form-group label {
            display: block;
            margin-bottom: var(--spacing-xs);
            font-weight: 500;
            color: var(--text-light);
        }

        .form-group label i {
            margin-right: var(--spacing-xs);
            color: var(--accent-color);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--border-radius-md);
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
            transition: all var(--transition-fast);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent-color);
            background-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 2px rgba(255, 193, 7, 0.25);
        }

        .form-group input::placeholder,
        .form-group select::placeholder,
        .form-group textarea::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .skills-container {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-sm);
        }

        .skill-tag {
            background-color: var(--accent-color);
            color: var(--text-dark);
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--border-radius-full);
            font-size: var(--font-size-sm);
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .skill-tag .remove-skill {
            cursor: pointer;
            font-weight: bold;
        }

        .add-skill-input {
            display: flex;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-sm);
        }

        .add-skill-input input {
            flex: 1;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: var(--spacing-md);
            margin-top: var(--spacing-lg);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius-md);
            font-weight: 500;
            text-align: center;
            cursor: pointer;
            transition: all var(--transition-normal);
            box-shadow: var(--shadow-sm);
            text-decoration: none;
            color: inherit;
            border: none;
        }

        .btn i {
            margin-right: 0.5rem;
        }

        .primary-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--text-light);
        }

        .primary-btn:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--secondary-dark));
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .secondary-btn {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .secondary-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .alert {
            display: flex;
            align-items: center;
            padding: var(--spacing-md);
            border-radius: var(--border-radius-md);
            margin-bottom: var(--spacing-lg);
            position: relative;
        }

        .alert i {
            margin-right: var(--spacing-sm);
            font-size: var(--font-size-lg);
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.2);
            border-left: 4px solid var(--success-color);
            color: var(--text-light);
        }

        .alert-error {
            background-color: rgba(244, 67, 54, 0.2);
            border-left: 4px solid var(--error-color);
            color: var(--text-light);
        }

        @media (max-width: 768px) {
            .sidebar {
                left: -280px;
                height: 100%;
                z-index: 1000;
            }
            .sidebar.active {
                left: 0;
            }
            .sidebar-toggle {
                display: block;
            }
            .main-content {
                margin-left: 0;
            }
            .form-row.two-columns {
                grid-template-columns: 1fr;
            }
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body class="dashboard-body">
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">PS</div>
                <h3>Play Smart</h3>
            </div>
            <div class="sidebar-menu">
                <ul>
                    <li>
                        <a href="admin_dashboard.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="manage_jobs.php">
                            <i class="fas fa-briefcase"></i>
                            <span>Manage Jobs</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_applications.php">
                            <i class="fas fa-users"></i>
                            <span>Job Applications</span>
                        </a>
                    </li>
                    <li>
                        <a href="create_contest.php">
                            <i class="fas fa-trophy"></i>
                            <span>Create Big Win Contest</span>
                        </a>
                    </li>
                    <li>
                        <a href="edit_contest.php">
                            <i class="fas fa-edit"></i>
                            <span>Create Mini Contest</span>
                        </a>
                    </li>
                    <li>
                        <a href="contest_list.php">
                            <i class="fas fa-list"></i>
                            <span>Contest List</span>
                        </a>
                    </li>
                    <li>
                        <a href="add_questions.php">
                            <i class="fas fa-question-circle"></i>
                            <span>Add Questions</span>
                        </a>
                    </li>
                    <li>
                        <a href="question_list.php">
                            <i class="fas fa-list-ul"></i>
                            <span>Question List</span>
                        </a>
                    </li>
                    <li>
                        <a href="view_score.php">
                            <i class="fas fa-chart-bar"></i>
                            <span>View Score</span>
                        </a>
                    </li>
                    <li>
                        <a href="users_list.php">
                            <i class="fas fa-users"></i>
                            <span>Users List</span>
                        </a>
                    </li>
                    <li>
                        <a href="withdrawal_requests.php">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Withdrawal Request</span>
                        </a>
                    </li>
                    <li>
                        <a href="payment_history.php">
                            <i class="fas fa-history"></i>
                            <span>Payment History</span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="sidebar-footer">
                <a href="index.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <div class="header-left">
                    <button id="sidebar-toggle" class="sidebar-toggle"><i class="fas fa-bars"></i></button>
                    <h1>Add New Job</h1>
                </div>
                <a href="manage_jobs.php" class="btn secondary-btn">
                    <i class="fas fa-arrow-left"></i> Back to Jobs
                </a>
            </header>

            <div class="content-body">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h2>Job Details</h2>
                        <p>Fill in the details to create a new job posting</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="form">
                            <div class="form-row two-columns">
                                <div class="form-group">
                                    <label for="company_name"><i class="fas fa-building"></i> Company Name</label>
                                    <input type="text" id="company_name" name="company_name" placeholder="Enter company name" value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="job_title"><i class="fas fa-briefcase"></i> Job Title</label>
                                    <input type="text" id="job_title" name="job_title" placeholder="Enter job title" value="<?php echo htmlspecialchars($_POST['job_title'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="form-row two-columns">
                                <div class="form-group">
                                    <label for="package"><i class="fas fa-coins"></i> Package</label>
                                    <input type="text" id="package" name="package" placeholder="e.g., 12LPA, $80K" value="<?php echo htmlspecialchars($_POST['package'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="location"><i class="fas fa-map-marker-alt"></i> Location</label>
                                    <input type="text" id="location" name="location" placeholder="Enter job location" value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="form-row two-columns">
                                <div class="form-group">
                                    <label for="job_type"><i class="fas fa-clock"></i> Job Type</label>
                                    <select id="job_type" name="job_type" required>
                                        <option value="full_time" <?php echo ($_POST['job_type'] ?? '') === 'full_time' ? 'selected' : ''; ?>>Full Time</option>
                                        <option value="part_time" <?php echo ($_POST['job_type'] ?? '') === 'part_time' ? 'selected' : ''; ?>>Part Time</option>
                                        <option value="contract" <?php echo ($_POST['job_type'] ?? '') === 'contract' ? 'selected' : ''; ?>>Contract</option>
                                        <option value="internship" <?php echo ($_POST['job_type'] ?? '') === 'internship' ? 'selected' : ''; ?>>Internship</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="experience_level"><i class="fas fa-user-tie"></i> Experience Level</label>
                                    <input type="text" id="experience_level" name="experience_level" placeholder="e.g., 2-5 years, Entry level" value="<?php echo htmlspecialchars($_POST['experience_level'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fas fa-tools"></i> Skills Required</label>
                                    <div class="skills-container" id="skills-container">
                                        <!-- Skills will be added here dynamically -->
                                    </div>
                                    <div class="add-skill-input">
                                        <input type="text" id="skill-input" placeholder="Add a skill and press Enter">
                                        <button type="button" class="btn secondary-btn" onclick="addSkill()">Add</button>
                                    </div>
                                    <input type="hidden" name="skills_required" id="skills-required-input" value="[]">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="job_description"><i class="fas fa-align-left"></i> Job Description</label>
                                    <textarea id="job_description" name="job_description" rows="6" placeholder="Enter detailed job description" required><?php echo htmlspecialchars($_POST['job_description'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="reset" class="btn secondary-btn"><i class="fas fa-undo"></i> Reset</button>
                                <button type="submit" class="btn primary-btn"><i class="fas fa-plus-circle"></i> Create Job</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        let skills = [];

        function addSkill() {
            const skillInput = document.getElementById('skill-input');
            const skill = skillInput.value.trim();
            
            if (skill && !skills.includes(skill)) {
                skills.push(skill);
                updateSkillsDisplay();
                skillInput.value = '';
            }
        }

        function removeSkill(skill) {
            skills = skills.filter(s => s !== skill);
            updateSkillsDisplay();
        }

        function updateSkillsDisplay() {
            const container = document.getElementById('skills-container');
            const input = document.getElementById('skills-required-input');
            
            container.innerHTML = '';
            skills.forEach(skill => {
                const tag = document.createElement('div');
                tag.className = 'skill-tag';
                tag.innerHTML = `
                    ${skill}
                    <span class="remove-skill" onclick="removeSkill('${skill}')">&times;</span>
                `;
                container.appendChild(tag);
            });
            
            input.value = JSON.stringify(skills);
        }

        // Add skill on Enter key
        document.getElementById('skill-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addSkill();
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.sidebar');
            const sidebarToggle = document.querySelector('#sidebar-toggle');
            const mainContent = document.querySelector('.main-content');

            // Toggle sidebar on button click
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                document.body.classList.toggle('sidebar-active');
            });

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', (e) => {
                if (window.innerWidth <= 768 && sidebar.classList.contains('active') && !sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                    document.body.classList.remove('sidebar-active');
                }
            });
        });
    </script>
</body>
</html> 