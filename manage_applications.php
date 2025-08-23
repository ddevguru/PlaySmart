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

// Handle status updates
if (isset($_POST['update_status'])) {
    $applicationId = intval($_POST['application_id']);
    $newStatus = $_POST['new_status'];
    
    try {
        $stmt = $conn->prepare("UPDATE job_applications SET application_status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $applicationId);
        if ($stmt->execute()) {
            $message = 'Application status updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error updating application status';
            $messageType = 'error';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Handle form submission for adding new applicant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_applicant'])) {
    $job_id = $_POST['job_id'];
    $company_name = $_POST['company_name'];
    $company_logo = $_POST['company_logo'];
    $student_name = $_POST['student_name'];
    $district = $_POST['district'];
    $package = $_POST['package'];
    $profile = $_POST['profile'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $experience = $_POST['experience'];
    $skills = $_POST['skills'];
    $application_status = $_POST['application_status'];
    
    // Handle photo upload
    $photo_path = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photo_name = time() . '_' . $_FILES['photo']['name'];
        $photo_path = 'uploads/photos/' . $photo_name;
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
            $error = "Failed to upload photo";
        }
    }
    
    // Handle resume upload
    $resume_path = '';
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $resume_name = time() . '_' . $_FILES['resume']['name'];
        $resume_path = 'uploads/resumes/' . $resume_name;
        if (!move_uploaded_file($_FILES['resume']['tmp_name'], $resume_path)) {
            $error = "Failed to upload resume";
        }
    }
    
    if (!isset($error)) {
        // Generate a dummy payment ID for manually added applicants
        $payment_id = 'manual_' . time();
        
        $sql = "INSERT INTO job_applications (job_id, company_name, company_logo, student_name, district, package, profile, photo_path, resume_path, email, phone, experience, skills, payment_id, application_status, applied_date, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssssssssss", $job_id, $company_name, $company_logo, $student_name, $district, $package, $profile, $photo_path, $resume_path, $email, $phone, $experience, $skills, $payment_id, $application_status);
        
        if ($stmt->execute()) {
            $success = "Applicant added successfully!";
        } else {
            $error = "Failed to add applicant: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch all job applications
$applications = [];
try {
    $query = "
        SELECT ja.*, j.job_title, j.package, j.location
        FROM job_applications ja
        LEFT JOIN jobs j ON ja.job_id = j.id
        ORDER BY ja.applied_date DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $applications[] = $row;
    }
    
    // Debug: Log the count
    error_log("Fetched " . count($applications) . " applications");
    
} catch (Exception $e) {
    error_log('Error fetching applications: ' . $e->getMessage());
    $error = 'Error fetching applications: ' . $e->getMessage();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Play Smart - Manage Job Applications</title>
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

        .filters {
            display: flex;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-xs);
        }

        .filter-group label {
            font-size: var(--font-size-sm);
            color: rgba(255, 255, 255, 0.8);
        }

        .filter-group select,
        .filter-group input {
            padding: var(--spacing-sm) var(--spacing-md);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--border-radius-md);
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
            min-width: 150px;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        .applications-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: var(--spacing-lg);
        }

        .application-card {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform var(--transition-normal);
        }

        .application-card:hover {
            transform: translateY(-5px);
            background-color: rgba(255, 255, 255, 0.15);
        }

        .application-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: var(--spacing-md);
        }

        .applicant-info h3 {
            font-size: var(--font-size-lg);
            margin-bottom: var(--spacing-xs);
            color: var(--accent-color);
        }

        .applicant-info p {
            color: rgba(255, 255, 255, 0.7);
            font-size: var(--font-size-sm);
        }

        .status-badge {
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--border-radius-full);
            font-size: var(--font-size-xs);
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-pending {
            background-color: rgba(255, 193, 7, 0.2);
            color: var(--accent-color);
        }

        .status-shortlisted {
            background-color: rgba(33, 150, 243, 0.2);
            color: var(--info-color);
        }

        .status-approved {
            background-color: rgba(76, 175, 80, 0.2);
            color: var(--success-color);
        }

        .status-rejected {
            background-color: rgba(244, 67, 54, 0.2);
            color: var(--error-color);
        }

        .job-details {
            margin-bottom: var(--spacing-md);
        }

        .job-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: var(--spacing-xs);
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
            font-weight: 500;
            font-size: var(--font-size-sm);
        }

        .application-actions {
            display: flex;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-md);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-md);
            font-weight: 500;
            text-align: center;
            cursor: pointer;
            transition: all var(--transition-normal);
            box-shadow: var(--shadow-sm);
            text-decoration: none;
            color: inherit;
            border: none;
            font-size: var(--font-size-sm);
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

        .success-btn {
            background-color: var(--success-color);
            color: var(--text-light);
        }

        .success-btn:hover {
            background-color: #388e3c;
            transform: translateY(-2px);
        }

        .warning-btn {
            background-color: var(--warning-color);
            color: var(--text-light);
        }

        .warning-btn:hover {
            background-color: #f57c00;
            transform: translateY(-2px);
        }

        .danger-btn {
            background-color: var(--error-color);
            color: var(--text-light);
        }

        .danger-btn:hover {
            background-color: #d32f2f;
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

        .no-applications {
            text-align: center;
            padding: var(--spacing-xl);
            color: rgba(255, 255, 255, 0.7);
        }

        .no-applications i {
            font-size: 3rem;
            margin-bottom: var(--spacing-md);
            opacity: 0.3;
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
            .applications-grid {
                grid-template-columns: 1fr;
            }
            .filters {
                flex-direction: column;
            }
            .filter-group {
                width: 100%;
            }
            .filter-group select,
            .filter-group input {
                min-width: auto;
                width: 100%;
            }
            .application-actions {
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
                    <li>
                        <a href="manage_jobs.php">
                            <i class="fas fa-briefcase"></i>
                            <span>Manage Jobs</span>
                        </a>
                    </li>
                    <li class="active">
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
                    <h1>Manage Job Applications</h1>
                </div>
                <a href="admin_dashboard.php" class="btn secondary-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
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
                        <h2>Job Applications</h2>
                        <button class="btn primary-btn" onclick="openAddApplicantModal()">
                            <i class="fas fa-plus"></i> Add Applicant
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Applications Count -->
                        <div class="alert alert-info" style="margin-bottom: 20px;">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Database Status:</strong> Found <?php echo count($applications); ?> job applications in the database.
                        </div>

                        <?php if (isset($success)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($applications)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                <strong>No Applications Found:</strong> The database appears to be empty or there was an error fetching data.
                            </div>
                        <?php else: ?>
                            <!-- Table View for All Applications -->
                            <div class="table-container" style="margin-bottom: 30px;">
                                <table class="applications-table" style="width: 100%; border-collapse: collapse; background: white; color: #333;">
                                    <thead>
                                        <tr style="background-color: #6A0DAD; color: white;">
                                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">ID</th>
                                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Company</th>
                                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Student</th>
                                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">District</th>
                                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Package</th>
                                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Profile</th>
                                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Status</th>
                                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Applied Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($applications as $app): ?>
                                            <tr style="border-bottom: 1px solid #ddd;">
                                                <td style="padding: 12px; border: 1px solid #ddd;"><?php echo htmlspecialchars($app['id']); ?></td>
                                                <td style="padding: 12px; border: 1px solid #ddd;"><?php echo htmlspecialchars($app['company_name']); ?></td>
                                                <td style="padding: 12px; border: 1px solid #ddd;"><?php echo htmlspecialchars($app['student_name']); ?></td>
                                                <td style="padding: 12px; border: 1px solid #ddd;"><?php echo htmlspecialchars($app['district']); ?></td>
                                                <td style="padding: 12px; border: 1px solid #ddd;"><?php echo htmlspecialchars($app['package']); ?></td>
                                                <td style="padding: 12px; border: 1px solid #ddd;"><?php echo htmlspecialchars($app['profile']); ?></td>
                                                <td style="padding: 12px; border: 1px solid #ddd;">
                                                    <span class="status-badge status-<?php echo $app['application_status']; ?>" style="padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; text-transform: uppercase;">
                                                        <?php echo ucfirst($app['application_status']); ?>
                                                    </span>
                                                </td>
                                                <td style="padding: 12px; border: 1px solid #ddd;"><?php echo date('M d, Y', strtotime($app['applied_date'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <h3 style="color: #333; margin-bottom: 20px;">Detailed View (Cards)</h3>
                            <div class="applications-grid">
                                <?php foreach ($applications as $app): ?>
                                    <div class="application-card">
                                        <div class="application-header">
                                            <div class="applicant-info">
                                                <h3><?php echo htmlspecialchars($app['student_name']); ?></h3>
                                                <p><?php echo htmlspecialchars($app['company_name']); ?></p>
                                            </div>
                                            <span class="status-badge status-<?php echo $app['application_status']; ?>">
                                                <?php echo ucfirst($app['application_status']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="job-details">
                                            <div class="job-detail">
                                                <span>Job Title:</span>
                                                <span><?php echo htmlspecialchars($app['job_title']); ?></span>
                                            </div>
                                            <div class="job-detail">
                                                <span>Package:</span>
                                                <span><?php echo htmlspecialchars($app['package']); ?></span>
                                            </div>
                                            <div class="job-detail">
                                                <span>Location:</span>
                                                <span><?php echo htmlspecialchars($app['location']); ?></span>
                                            </div>
                                            <div class="job-detail">
                                                <span>District:</span>
                                                <span><?php echo htmlspecialchars($app['district']); ?></span>
                                            </div>
                                            <div class="job-detail">
                                                <span>Email:</span>
                                                <span><?php echo htmlspecialchars($app['email'] ?? 'N/A'); ?></span>
                                            </div>
                                            <div class="job-detail">
                                                <span>Phone:</span>
                                                <span><?php echo htmlspecialchars($app['phone'] ?? 'N/A'); ?></span>
                                            </div>
                                            <div class="job-detail">
                                                <span>Experience:</span>
                                                <span><?php echo htmlspecialchars($app['experience'] ?? 'N/A'); ?></span>
                                            </div>
                                            <div class="job-detail">
                                                <span>Applied:</span>
                                                <span><?php echo date('M d, Y', strtotime($app['applied_date'])); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="application-actions">
                                            <div style="margin-bottom: 10px;">
                                                <?php if (!empty($app['photo_path'])): ?>
                                                    <a href="<?php echo htmlspecialchars($app['photo_path']); ?>" target="_blank" class="btn secondary-btn" style="margin-right: 8px;">
                                                        <i class="fas fa-image"></i> View Photo
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!empty($app['resume_path'])): ?>
                                                    <a href="<?php echo htmlspecialchars($app['resume_path']); ?>" target="_blank" class="btn secondary-btn">
                                                        <i class="fas fa-file-pdf"></i> View Resume
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                                <select name="new_status" style="padding: 0.5rem; margin-right: 0.5rem; background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); border-radius: 4px;">
                                                    <option value="pending" <?php echo $app['application_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="shortlisted" <?php echo $app['application_status'] === 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                                                    <option value="approved" <?php echo $app['application_status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                    <option value="rejected" <?php echo $app['application_status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                </select>
                                                <button type="submit" name="update_status" class="btn primary-btn">
                                                    <i class="fas fa-save"></i> Update
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

    <!-- Add Applicant Modal -->
    <div id="addApplicantModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Applicant</h2>
                <span class="close" onclick="closeAddApplicantModal()">&times;</span>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="job_id">Select Job *</label>
                        <select name="job_id" id="job_id" required>
                            <option value="">Choose a job...</option>
                            <?php foreach ($jobs as $job): ?>
                                <option value="<?php echo $job['id']; ?>">
                                    <?php echo htmlspecialchars($job['company_name'] . ' - ' . $job['profile']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="company_name">Company Name *</label>
                        <input type="text" name="company_name" id="company_name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="company_logo">Company Logo URL</label>
                        <input type="text" name="company_logo" id="company_logo" placeholder="e.g., google_logo.png">
                    </div>
                    
                    <div class="form-group">
                        <label for="student_name">Student Name *</label>
                        <input type="text" name="student_name" id="student_name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="district">District *</label>
                        <input type="text" name="district" id="district" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="package">Package *</label>
                        <input type="text" name="package" id="package" placeholder="e.g., 12LPA" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="profile">Profile/Position *</label>
                    <input type="text" name="profile" id="profile" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" name="email" id="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone *</label>
                        <input type="tel" name="phone" id="phone" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="experience">Experience *</label>
                        <input type="text" name="experience" id="experience" placeholder="e.g., 5 years" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="application_status">Application Status *</label>
                        <select name="application_status" id="application_status" required>
                            <option value="pending">Pending</option>
                            <option value="shortlisted">Shortlisted</option>
                            <option value="accepted">Accepted</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="skills">Skills</label>
                    <textarea name="skills" id="skills" placeholder="Enter skills separated by commas"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Photo Upload</label>
                        <div class="file-upload" onclick="document.getElementById('photo').click()">
                            <input type="file" name="photo" id="photo" accept="image/*">
                            <i class="fas fa-camera"></i>
                            <p>Click to upload photo</p>
                            <small>Supports: JPG, PNG, GIF</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Resume Upload</label>
                        <div class="file-upload" onclick="document.getElementById('resume').click()">
                            <input type="file" name="resume" id="resume" accept=".pdf,.doc,.docx">
                            <i class="fas fa-file-alt"></i>
                            <p>Click to upload resume</p>
                            <small>Supports: PDF, DOC, DOCX</small>
                        </div>
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="button" class="btn secondary-btn" onclick="closeAddApplicantModal()">Cancel</button>
                    <button type="submit" name="add_applicant" class="btn primary-btn">Add Applicant</button>
                </div>
            </form>
        </div>
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

        // Modal functions
        function openAddApplicantModal() {
            document.getElementById('addApplicantModal').style.display = 'block';
        }

        function closeAddApplicantModal() {
            document.getElementById('addApplicantModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addApplicantModal');
            if (event.target === modal) {
                closeAddApplicantModal();
            }
        }

        // Application action functions
        function viewApplication(id) {
            // Implement view functionality
            alert('View application ' + id);
        }

        function editApplication(id) {
            // Implement edit functionality
            alert('Edit application ' + id);
        }

        function deleteApplication(id) {
            if (confirm('Are you sure you want to delete this application?')) {
                // Implement delete functionality
                alert('Delete application ' + id);
            }
        }

        // Auto-fill company details when job is selected
        document.getElementById('job_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const jobText = selectedOption.text;
                const parts = jobText.split(' - ');
                if (parts.length === 2) {
                    document.getElementById('company_name').value = parts[0];
                    document.getElementById('profile').value = parts[1];
                }
            }
        });
    </script>
</body>
</html> 