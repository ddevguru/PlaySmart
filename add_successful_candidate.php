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

// Handle heading update
if (isset($_POST['update_heading'])) {
    $headingText = trim($_POST['heading_text'] ?? '');
    $subHeadingText = trim($_POST['sub_heading_text'] ?? '');
    
    if (empty($headingText)) {
        $message = 'Heading text is required.';
        $messageType = 'error';
    } else {
        try {
            // Check if heading exists, if not create it
            $checkStmt = $conn->prepare("SELECT id FROM content_headings WHERE section_name = 'successful_candidates'");
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing heading
                $stmt = $conn->prepare("UPDATE content_headings SET heading_text = ?, sub_heading_text = ?, updated_at = NOW() WHERE section_name = 'successful_candidates'");
                $stmt->bind_param("ss", $headingText, $subHeadingText);
            } else {
                // Insert new heading
                $stmt = $conn->prepare("INSERT INTO content_headings (section_name, heading_text, sub_heading_text, is_active, created_at) VALUES ('successful_candidates', ?, ?, 1, NOW())");
                $stmt->bind_param("ss", $headingText, $subHeadingText);
            }
            
            if ($stmt->execute()) {
                $message = 'Heading updated successfully!';
                $messageType = 'success';
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            $message = 'Error updating heading: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Handle candidate deletion
if (isset($_POST['delete_candidate'])) {
    $candidateId = intval($_POST['candidate_id']);
    try {
        $stmt = $conn->prepare("UPDATE successful_candidates SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $candidateId);
        if ($stmt->execute()) {
            $message = 'Candidate deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error deleting candidate';
            $messageType = 'error';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['delete_candidate']) && !isset($_POST['update_heading'])) {
    $companyName = trim($_POST['company_name'] ?? '');
    $candidateName = trim($_POST['candidate_name'] ?? '');
    $salary = trim($_POST['salary'] ?? '');
    $jobLocation = trim($_POST['job_location'] ?? '');

    // Validation
    $errors = [];
    if (empty($companyName)) {
        $errors[] = 'Company name is required.';
    }
    if (empty($candidateName)) {
        $errors[] = 'Candidate name is required.';
    }
    if (empty($salary)) {
        $errors[] = 'Salary is required.';
    }
    if (empty($jobLocation)) {
        $errors[] = 'Job location is required.';
    }

    if (empty($errors)) {
        try {
            $conn->begin_transaction();

            // Insert new successful candidate
            $stmt = $conn->prepare("
                INSERT INTO successful_candidates (company_name, candidate_name, salary, job_location, is_active, created_at)
                VALUES (?, ?, ?, ?, 1, NOW())
            ");
            
            $stmt->bind_param("ssss", $companyName, $candidateName, $salary, $jobLocation);
            
            if ($stmt->execute()) {
                $conn->commit();
                $message = 'Successful candidate added successfully!';
                $messageType = 'success';
                
                // Clear form data
                $_POST = array();
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Error adding successful candidate: ' . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = implode('<br>', $errors);
        $messageType = 'error';
    }
}

// Get current heading
$currentHeading = 'Our Successfully Placed';
$currentSubHeading = 'Candidates';
try {
    $stmt = $conn->prepare("SELECT heading_text, sub_heading_text FROM content_headings WHERE section_name = 'successful_candidates' AND is_active = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $currentHeading = $row['heading_text'];
        $currentSubHeading = $row['sub_heading_text'];
    }
} catch (Exception $e) {
    // Keep default values if error occurs
}

// Get all successful candidates
$candidates = [];
try {
    $stmt = $conn->prepare("
        SELECT * FROM successful_candidates 
        WHERE is_active = 1 
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $candidates[] = $row;
    }
} catch (Exception $e) {
    $message = 'Error fetching candidates: ' . $e->getMessage();
    $messageType = 'error';
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Play Smart - Add Successful Candidate</title>
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

        .info-btn {
            background-color: rgba(33, 150, 243, 0.2);
            color: var(--info-color);
            border: 1px solid rgba(33, 150, 243, 0.3);
        }

        .info-btn:hover {
            background-color: rgba(33, 150, 243, 0.3);
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

        /* ===== CANDIDATES LIST STYLES ===== */
        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: var(--spacing-lg);
            margin-top: var(--spacing-lg);
        }

        .candidate-card {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all var(--transition-normal);
        }

        .candidate-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            background-color: rgba(255, 255, 255, 0.15);
        }

        .candidate-header {
            display: flex;
            align-items: center;
            margin-bottom: var(--spacing-md);
        }

        .company-logo {
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

        .candidate-info h3 {
            font-size: var(--font-size-lg);
            margin-bottom: var(--spacing-xs);
            color: var(--text-light);
        }

        .candidate-info p {
            color: rgba(255, 255, 255, 0.7);
            font-size: var(--font-size-sm);
        }

        .candidate-details {
            margin-bottom: var(--spacing-md);
        }

        .candidate-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: var(--spacing-sm);
            padding: var(--spacing-xs) 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .candidate-detail:last-child {
            border-bottom: none;
        }

        .candidate-detail span:first-child {
            color: rgba(255, 255, 255, 0.7);
            font-size: var(--font-size-sm);
        }

        .candidate-detail span:last-child {
            color: var(--text-light);
            font-weight: 500;
        }

        .candidate-actions {
            display: flex;
            gap: var(--spacing-sm);
        }

        .candidate-actions .btn {
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

        /* ===== HEADING PREVIEW STYLES ===== */
        .heading-preview {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: var(--spacing-lg);
            border-radius: var(--border-radius-lg);
            margin-bottom: var(--spacing-lg);
            text-align: center;
        }

        .heading-preview h3 {
            font-size: var(--font-size-xl);
            font-weight: bold;
            color: var(--text-light);
            margin-bottom: var(--spacing-xs);
        }

        .heading-preview h4 {
            font-size: var(--font-size-lg);
            font-weight: bold;
            color: var(--accent-color);
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
            .candidates-grid {
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
                        <a href="add_successful_candidate.php">
                            <i class="fas fa-briefcase"></i>
                            <span>Add Candidate</span>
                        </a>
                    </li>
                      <li>
                        <a href="add_job_new.php">
                            <i class="fas fa-briefcase"></i>
                            <span>Add New Job</span>
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
                    <li class="active">
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
                    <h1>Add Successful Candidate</h1>
                </div>
            </header>

            <div class="content-body">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Heading Management Section -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-heading"></i> Manage Section Heading</h2>
                        <p>Change the heading text that appears above the successful candidates section</p>
                    </div>
                    <div class="card-body">
                        <!-- Current Heading Preview -->
                        <div class="heading-preview">
                            <h3><?php echo htmlspecialchars($currentHeading); ?></h3>
                            <h4><?php echo htmlspecialchars($currentSubHeading); ?></h4>
                        </div>
                        
                        <form method="POST" class="form">
                            <div class="form-row two-columns">
                                <div class="form-group">
                                    <label for="heading_text"><i class="fas fa-heading"></i> Main Heading</label>
                                    <input type="text" id="heading_text" name="heading_text" 
                                           placeholder="e.g., Our Successfully Placed" 
                                           value="<?php echo htmlspecialchars($currentHeading); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="sub_heading_text"><i class="fas fa-text-height"></i> Sub Heading</label>
                                    <input type="text" id="sub_heading_text" name="sub_heading_text" 
                                           placeholder="e.g., Candidates" 
                                           value="<?php echo htmlspecialchars($currentSubHeading); ?>" required>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="update_heading" class="btn info-btn">
                                    <i class="fas fa-save"></i> Update Heading
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Add Candidate Section -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-user-plus"></i> Successful Candidate Details</h2>
                        <p>Fill in the details to add a new successfully placed candidate</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="form">
                            <div class="form-row two-columns">
                                <div class="form-group">
                                    <label for="company_name"><i class="fas fa-building"></i> Company Name</label>
                                    <input type="text" id="company_name" name="company_name" placeholder="Enter company name" value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="candidate_name"><i class="fas fa-user"></i> Candidate Name</label>
                                    <input type="text" id="candidate_name" name="candidate_name" placeholder="Enter candidate name" value="<?php echo htmlspecialchars($_POST['candidate_name'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="form-row two-columns">
                                <div class="form-group">
                                    <label for="salary"><i class="fas fa-coins"></i> Salary</label>
                                    <input type="text" id="salary" name="salary" placeholder="e.g., 25LPA, $80K" value="<?php echo htmlspecialchars($_POST['salary'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="job_location"><i class="fas fa-map-marker-alt"></i> Job Location</label>
                                    <input type="text" id="job_location" name="job_location" placeholder="Enter job location" value="<?php echo htmlspecialchars($_POST['job_location'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="reset" class="btn secondary-btn"><i class="fas fa-undo"></i> Reset</button>
                                <button type="submit" class="btn primary-btn"><i class="fas fa-plus-circle"></i> Add Candidate</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Candidates List Section -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-list"></i> Successful Candidates List</h2>
                        <p>View all successfully placed candidates</p>
                    </div>
                    <div class="card-body">
                        <?php if (empty($candidates)): ?>
                            <div class="empty-state">
                                <i class="fas fa-user-check"></i>
                                <p>No successful candidates added yet</p>
                                <p style="font-size: var(--font-size-sm); margin-top: var(--spacing-sm);">Add your first successful candidate using the form above</p>
                            </div>
                        <?php else: ?>
                            <div class="candidates-grid">
                                <?php foreach ($candidates as $candidate): ?>
                                    <div class="candidate-card">
                                        <div class="candidate-header">
                                            <div class="company-logo">
                                                <?php echo strtoupper(substr($candidate['company_name'], 0, 1)); ?>
                                            </div>
                                            <div class="candidate-info">
                                                <h3><?php echo htmlspecialchars($candidate['candidate_name']); ?></h3>
                                                <p><?php echo htmlspecialchars($candidate['company_name']); ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="candidate-details">
                                            <div class="candidate-detail">
                                                <span>Salary:</span>
                                                <span><?php echo htmlspecialchars($candidate['salary']); ?></span>
                                            </div>
                                            <div class="candidate-detail">
                                                <span>Location:</span>
                                                <span><?php echo htmlspecialchars($candidate['job_location']); ?></span>
                                            </div>
                                            <div class="candidate-detail">
                                                <span>Added:</span>
                                                <span><?php echo date('M d, Y', strtotime($candidate['created_at'])); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="candidate-actions">
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this candidate?')">
                                                <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
                                                <button type="submit" name="delete_candidate" class="btn danger-btn">
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