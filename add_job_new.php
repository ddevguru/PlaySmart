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

        // Handle job deletion
        if (isset($_POST['delete_job'])) {
            $jobId = intval($_POST['job_id']);
            try {
                $stmt = $conn->prepare("UPDATE new_jobs SET is_active = 0 WHERE id = ?");
                $stmt->bind_param("i", $jobId);
        if ($stmt->execute()) {
            $message = 'Job deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error deleting job';
            $messageType = 'error';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['delete_job'])) {
    $jobPost = trim($_POST['job_post'] ?? '');
    $salary = trim($_POST['salary'] ?? '');
    $education = trim($_POST['education'] ?? '');
    $jobType = $_POST['job_type'] ?? 'higher_job';

    // Validation
    $errors = [];
    if (empty($jobPost)) {
        $errors[] = 'Job post is required.';
    }
    if (empty($salary)) {
        $errors[] = 'Salary is required.';
    }
    if (empty($education)) {
        $errors[] = 'Education is required.';
    }

    if (empty($errors)) {
        try {
            $conn->begin_transaction();

            // Insert new job
            $stmt = $conn->prepare("
                INSERT INTO new_jobs (job_post, salary, education, job_type, is_active, created_at)
                VALUES (?, ?, ?, ?, 1, NOW())
            ");
            
            $stmt->bind_param("ssss", $jobPost, $salary, $education, $jobType);
            
            if ($stmt->execute()) {
                $conn->commit();
                $message = 'Job added successfully!';
                $messageType = 'success';
                
                // Clear form data
                $_POST = array();
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Error adding job: ' . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = implode('<br>', $errors);
        $messageType = 'error';
    }
}

// Get all jobs
$jobs = [];
try {
    $stmt = $conn->prepare("
        SELECT * FROM new_jobs 
        WHERE is_active = 1 
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
} catch (Exception $e) {
    $message = 'Error fetching jobs: ' . $e->getMessage();
    $messageType = 'error';
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

        .form-group select option {
            color: var(--text-dark);
            background-color: var(--text-light);
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

        .danger-btn {
            background-color: rgba(244, 67, 54, 0.2);
            color: var(--error-color);
            border: 1px solid rgba(244, 67, 54, 0.3);
        }

        .danger-btn:hover {
            background-color: rgba(244, 67, 54, 0.3);
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

        /* ===== JOBS LIST STYLES ===== */
        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: var(--spacing-lg);
            margin-top: var(--spacing-lg);
        }

        .job-card {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all var(--transition-normal);
        }

        .job-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            background-color: rgba(255, 255, 255, 0.15);
        }

        .job-header {
            display: flex;
            align-items: center;
            margin-bottom: var(--spacing-md);
        }

        .job-icon {
            width: 50px;
            height: 50px;
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

        .job-info h3 {
            font-size: var(--font-size-lg);
            margin-bottom: var(--spacing-xs);
            color: var(--text-light);
        }

        .job-info p {
            color: rgba(255, 255, 255, 0.7);
            font-size: var(--font-size-sm);
        }

        .job-details {
            margin-bottom: var(--spacing-md);
        }

        .job-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: var(--spacing-sm);
            padding: var(--spacing-xs) 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .job-detail:last-child {
            border-bottom: none;
        }

        .job-detail span:first-child {
            color: rgba(255, 255, 255, 0.7);
            font-size: var(--font-size-sm);
        }

        .job-detail span:last-child {
            color: var(--text-light);
            font-weight: 500;
        }

        .job-actions {
            display: flex;
            gap: var(--spacing-sm);
        }

        .job-actions .btn {
            flex: 1;
            padding: var(--spacing-sm) var(--spacing-md);
            font-size: var(--font-size-sm);
        }

        .empty-state {
            text-align: center;
            padding: var(--spacing-2xl);
            color: rgba(255, 255, 255, 0.7);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: var(--spacing-md);
            opacity: 0.3;
        }

        .job-type-badge {
            display: inline-block;
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--border-radius-full);
            font-size: var(--font-size-xs);
            font-weight: 600;
            text-transform: uppercase;
        }

        .job-type-higher {
            background-color: var(--primary-light);
            color: var(--text-light);
        }

        .job-type-local {
            background-color: var(--accent-color);
            color: var(--text-dark);
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
            .jobs-grid {
                grid-template-columns: 1fr;
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
                        <a href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="add_job_new.php">
                            <i class="fas fa-briefcase"></i>
                            <span>Add New Job</span>
                        </a>
                    </li>
                    <li>
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
                    <li>
                        <a href="add_successful_candidate.php">
                            <i class="fas fa-user-check"></i>
                            <span>Add Successful Candidate</span>
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
                        <p>Fill in the details to add a new job posting</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="job_post"><i class="fas fa-briefcase"></i> Job Post</label>
                                    <input type="text" id="job_post" name="job_post" placeholder="Enter job post/title" value="<?php echo htmlspecialchars($_POST['job_post'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="form-row two-columns">
                                <div class="form-group">
                                    <label for="salary"><i class="fas fa-coins"></i> Salary</label>
                                    <input type="text" id="salary" name="salary" placeholder="e.g., 25LPA, $80K, 50000" value="<?php echo htmlspecialchars($_POST['salary'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="education"><i class="fas fa-graduation-cap"></i> Education</label>
                                    <input type="text" id="education" name="education" placeholder="e.g., B.Tech, MBA, 12th Pass" value="<?php echo htmlspecialchars($_POST['education'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="job_type"><i class="fas fa-filter"></i> Job Type</label>
                                    <select id="job_type" name="job_type" required>
                                        <option value="higher_job" <?php echo ($_POST['job_type'] ?? '') === 'higher_job' ? 'selected' : ''; ?>>Higher Job</option>
                                        <option value="local_job" <?php echo ($_POST['job_type'] ?? '') === 'local_job' ? 'selected' : ''; ?>>Local Job</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="reset" class="btn secondary-btn"><i class="fas fa-undo"></i> Reset</button>
                                <button type="submit" class="btn primary-btn"><i class="fas fa-plus-circle"></i> Add Job</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Jobs List Section -->
                <div class="card">
                    <div class="card-header">
                        <h2>Jobs List</h2>
                        <p>View all added jobs</p>
                    </div>
                    <div class="card-body">
                        <?php if (empty($jobs)): ?>
                            <div class="empty-state">
                                <i class="fas fa-briefcase"></i>
                                <p>No jobs added yet</p>
                                <p style="font-size: var(--font-size-sm); margin-top: var(--spacing-sm);">Add your first job using the form above</p>
                            </div>
                        <?php else: ?>
                            <div class="jobs-grid">
                                <?php foreach ($jobs as $job): ?>
                                    <div class="job-card">
                                        <div class="job-header">
                                            <div class="job-icon">
                                                <i class="fas fa-briefcase"></i>
                                            </div>
                                            <div class="job-info">
                                                <h3><?php echo htmlspecialchars($job['job_post'] ?? 'N/A'); ?></h3>
                                                <span class="job-type-badge job-type-<?php echo ($job['job_type'] === 'higher_job') ? 'higher' : 'local'; ?>">
                                                    <?php echo ($job['job_type'] === 'higher_job') ? 'Higher Job' : 'Local Job'; ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="job-details">
                                            <div class="job-detail">
                                                <span>Salary:</span>
                                                <span><?php echo htmlspecialchars($job['salary'] ?? 'N/A'); ?></span>
                                            </div>
                                            <div class="job-detail">
                                                <span>Education:</span>
                                                <span><?php echo htmlspecialchars($job['education'] ?? 'N/A'); ?></span>
                                            </div>
                                            <div class="job-detail">
                                                <span>Added:</span>
                                                <span><?php echo date('M d, Y', strtotime($job['created_at'])); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="job-actions">
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this job?')">
                                                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                <button type="submit" name="delete_job" class="btn danger-btn">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
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