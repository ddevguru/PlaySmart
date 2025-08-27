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
$job = null;

// Get job ID from URL
$jobId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($jobId <= 0) {
    header('Location: manage_jobs.php');
    exit();
}

// Fetch job data
try {
    $stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $jobId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Location: manage_jobs.php');
        exit();
    }
    
    $job = $result->fetch_assoc();
} catch (Exception $e) {
    $message = 'Error fetching job: ' . $e->getMessage();
    $messageType = 'error';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $job) {
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

            // Update job
            $stmt = $conn->prepare("
                UPDATE jobs 
                SET company_name = ?, job_title = ?, package = ?, location = ?, 
                    job_type = ?, experience_level = ?, skills_required = ?, 
                    job_description = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $skillsJson = json_encode($skillsRequired);
            $stmt->bind_param("ssssssssi", $companyName, $jobTitle, $package, $location, $jobType, $experienceLevel, $skillsJson, $jobDescription, $jobId);
            
            if ($stmt->execute()) {
                $conn->commit();
                $message = 'Job updated successfully!';
                $messageType = 'success';
                
                // Refresh job data
                $stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ?");
                $stmt->bind_param("i", $jobId);
                $stmt->execute();
                $result = $stmt->get_result();
                $job = $result->fetch_assoc();
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Error updating job: ' . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = implode('<br>', $errors);
        $messageType = 'error';
    }
}

$conn->close();

// Parse skills from JSON if they exist
$currentSkills = [];
if ($job && !empty($job['skills_required'])) {
    $currentSkills = json_decode($job['skills_required'], true) ?: [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Play Smart - Edit Job</title>
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
            
            --border-radius: 8px;
            --border-radius-lg: 12px;
            --border-radius-xl: 16px;
            --shadow: 0 4px 6px var(--shadow-color);
            --shadow-lg: 0 10px 15px var(--shadow-color);
            --shadow-xl: 0 20px 25px var(--shadow-color);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-family);
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* ===== LAYOUT ===== */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--spacing-lg);
        }

        .header {
            text-align: center;
            margin-bottom: var(--spacing-2xl);
            color: var(--text-light);
        }

        .header h1 {
            font-size: var(--font-size-4xl);
            font-weight: 700;
            margin-bottom: var(--spacing-sm);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .header p {
            font-size: var(--font-size-lg);
            opacity: 0.9;
        }

        /* ===== FORM STYLES ===== */
        .form-container {
            background: var(--text-light);
            border-radius: var(--border-radius-xl);
            padding: var(--spacing-2xl);
            box-shadow: var(--shadow-xl);
            max-width: 800px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: var(--spacing-lg);
        }

        .form-group label {
            display: block;
            margin-bottom: var(--spacing-sm);
            font-weight: 600;
            color: var(--text-dark);
            font-size: var(--font-size-md);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: var(--spacing-md);
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: var(--font-size-md);
            font-family: var(--font-family);
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(106, 17, 203, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-lg);
        }

        .skills-container {
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--spacing-md);
            min-height: 60px;
        }

        .skill-tag {
            display: inline-block;
            background: var(--primary-light);
            color: var(--text-light);
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--border-radius);
            margin: var(--spacing-xs);
            font-size: var(--font-size-sm);
        }

        .skill-tag .remove-skill {
            margin-left: var(--spacing-xs);
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

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-md) var(--spacing-xl);
            border: none;
            border-radius: var(--border-radius);
            font-size: var(--font-size-md);
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: var(--font-family);
        }

        .btn-primary {
            background: var(--primary-color);
            color: var(--text-light);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-secondary {
            background: var(--secondary-color);
            color: var(--text-light);
        }

        .btn-secondary:hover {
            background: var(--secondary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: var(--text-light);
        }

        .form-actions {
            display: flex;
            gap: var(--spacing-md);
            justify-content: center;
            margin-top: var(--spacing-xl);
        }

        /* ===== ALERTS ===== */
        .alert {
            padding: var(--spacing-md);
            border-radius: var(--border-radius);
            margin-bottom: var(--spacing-lg);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(76, 175, 80, 0.3);
        }

        .alert-error {
            background: rgba(244, 67, 54, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(244, 67, 54, 0.3);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .container {
                padding: var(--spacing-md);
            }
            
            .form-container {
                padding: var(--spacing-lg);
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: var(--spacing-md);
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-edit"></i> Edit Job</h1>
            <p>Update job information and requirements</p>
        </div>

        <div class="form-container">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($job): ?>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="company_name">Company Name *</label>
                            <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($job['company_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="job_title">Job Title *</label>
                            <input type="text" id="job_title" name="job_title" value="<?php echo htmlspecialchars($job['job_title']); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="package">Package *</label>
                            <input type="text" id="package" name="package" value="<?php echo htmlspecialchars($job['package']); ?>" placeholder="e.g., 12LPA" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="location">Location *</label>
                            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($job['location']); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="job_type">Job Type *</label>
                            <select id="job_type" name="job_type" required>
                                <option value="full_time" <?php echo $job['job_type'] === 'full_time' ? 'selected' : ''; ?>>Full Time</option>
                                <option value="part_time" <?php echo $job['job_type'] === 'part_time' ? 'selected' : ''; ?>>Part Time</option>
                                <option value="contract" <?php echo $job['job_type'] === 'contract' ? 'selected' : ''; ?>>Contract</option>
                                <option value="internship" <?php echo $job['job_type'] === 'internship' ? 'selected' : ''; ?>>Internship</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="experience_level">Experience Level *</label>
                            <input type="text" id="experience_level" name="experience_level" value="<?php echo htmlspecialchars($job['experience_level']); ?>" placeholder="e.g., 2-5 years" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Skills Required *</label>
                        <div class="skills-container" id="skillsContainer">
                            <?php foreach ($currentSkills as $skill): ?>
                                <span class="skill-tag">
                                    <?php echo htmlspecialchars($skill); ?>
                                    <span class="remove-skill" onclick="removeSkill(this)">&times;</span>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <div class="add-skill-input">
                            <input type="text" id="newSkill" placeholder="Add a skill and press Enter">
                            <button type="button" class="btn btn-secondary" onclick="addSkill()">Add</button>
                        </div>
                        <input type="hidden" name="skills_required" id="skillsInput" value="<?php echo htmlspecialchars(json_encode($currentSkills)); ?>">
                    </div>

                    <div class="form-group">
                        <label for="job_description">Job Description *</label>
                        <textarea id="job_description" name="job_description" required><?php echo htmlspecialchars($job['job_description']); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <a href="manage_jobs.php" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Back to Jobs
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Job
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    Job not found or you don't have permission to edit it.
                </div>
                <div class="form-actions">
                    <a href="manage_jobs.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Jobs
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function addSkill() {
            const input = document.getElementById('newSkill');
            const skill = input.value.trim();
            
            if (skill) {
                const skillsContainer = document.getElementById('skillsContainer');
                const skillTag = document.createElement('span');
                skillTag.className = 'skill-tag';
                skillTag.innerHTML = skill + ' <span class="remove-skill" onclick="removeSkill(this)">&times;</span>';
                skillsContainer.appendChild(skillTag);
                
                input.value = '';
                updateSkillsInput();
            }
        }

        function removeSkill(element) {
            element.parentElement.remove();
            updateSkillsInput();
        }

        function updateSkillsInput() {
            const skills = [];
            const skillTags = document.querySelectorAll('.skill-tag');
            
            skillTags.forEach(tag => {
                const skillText = tag.textContent.replace('×', '').trim();
                if (skillText) {
                    skills.push(skillText);
                }
            });
            
            document.getElementById('skillsInput').value = JSON.stringify(skills);
        }

        // Allow Enter key to add skills
        document.getElementById('newSkill').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addSkill();
            }
        });

        // Initialize skills input on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateSkillsInput();
        });
    </script>
</body>
</html> 