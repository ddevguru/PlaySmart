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

// Get statistics
$jobCount = 0;
$applicationCount = 0;
$pendingApplications = 0;

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM jobs WHERE is_active = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $jobCount = $result->fetch_assoc()['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM job_applications WHERE is_active = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $applicationCount = $result->fetch_assoc()['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM job_applications WHERE application_status = 'pending' AND is_active = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $pendingApplications = $result->fetch_assoc()['count'];
} catch (Exception $e) {
    $message = 'Error fetching statistics: ' . $e->getMessage();
    $messageType = 'error';
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Play Smart - Admin Dashboard</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }

        .stat-card {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: var(--border-radius-lg);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: var(--spacing-lg);
            text-align: center;
            transition: transform var(--transition-normal);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: var(--spacing-md);
            color: var(--accent-color);
        }

        .stat-card h3 {
            font-size: var(--font-size-2xl);
            margin-bottom: var(--spacing-sm);
        }

        .stat-card p {
            color: rgba(255, 255, 255, 0.7);
            font-size: var(--font-size-sm);
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }

        .action-card {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: var(--border-radius-lg);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: var(--spacing-lg);
            text-align: center;
            transition: all var(--transition-normal);
            cursor: pointer;
        }

        .action-card:hover {
            transform: translateY(-5px);
            background-color: rgba(0, 0, 0, 0.3);
        }

        .action-card i {
            font-size: 2rem;
            margin-bottom: var(--spacing-md);
            color: var(--accent-color);
        }

        .action-card h3 {
            font-size: var(--font-size-lg);
            margin-bottom: var(--spacing-sm);
        }

        .action-card p {
            color: rgba(255, 255, 255, 0.7);
            font-size: var(--font-size-sm);
        }

        .recent-section {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: var(--border-radius-lg);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: var(--spacing-lg);
        }

        .recent-section h2 {
            font-size: var(--font-size-xl);
            margin-bottom: var(--spacing-lg);
            color: var(--accent-color);
        }

        .recent-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-md);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .recent-item:last-child {
            border-bottom: none;
        }

        .recent-item .item-info h4 {
            font-size: var(--font-size-md);
            margin-bottom: var(--spacing-xs);
        }

        .recent-item .item-info p {
            color: rgba(255, 255, 255, 0.7);
            font-size: var(--font-size-sm);
        }

        .recent-item .item-status {
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--border-radius-sm);
            font-size: var(--font-size-xs);
            font-weight: 500;
        }

        .status-pending {
            background-color: rgba(255, 193, 7, 0.2);
            color: var(--accent-color);
        }

        .status-approved {
            background-color: rgba(76, 175, 80, 0.2);
            color: var(--success-color);
        }

        .status-rejected {
            background-color: rgba(244, 67, 54, 0.2);
            color: var(--error-color);
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
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .quick-actions {
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
                    <li class="active">
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
                    <h1>Admin Dashboard</h1>
                </div>
            </header>

            <div class="content-body">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-briefcase"></i>
                        <h3><?php echo $jobCount; ?></h3>
                        <p>Active Jobs</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-users"></i>
                        <h3><?php echo $applicationCount; ?></h3>
                        <p>Total Applications</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-clock"></i>
                        <h3><?php echo $pendingApplications; ?></h3>
                        <p>Pending Applications</p>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <div class="action-card" onclick="window.location.href='add_job.php'">
                        <i class="fas fa-plus-circle"></i>
                        <h3>Add New Job</h3>
                        <p>Create a new job posting</p>
                    </div>
                    <div class="action-card" onclick="window.location.href='manage_applications.php'">
                        <i class="fas fa-user-check"></i>
                        <h3>Review Applications</h3>
                        <p>Manage job applications</p>
                    </div>
                    <div class="action-card" onclick="window.location.href='manage_jobs.php'">
                        <i class="fas fa-edit"></i>
                        <h3>Edit Jobs</h3>
                        <p>Update existing jobs</p>
                    </div>
                    <div class="action-card" onclick="window.location.href='job_analytics.php'">
                        <i class="fas fa-chart-line"></i>
                        <h3>Job Analytics</h3>
                        <p>View job performance</p>
                    </div>
                </div>

                <!-- Recent Applications -->
                <div class="recent-section">
                    <h2><i class="fas fa-clock"></i> Recent Job Applications</h2>
                    <?php
                    try {
                        $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
                        if ($conn->connect_error) {
                            throw new Exception("Connection failed: " . $conn->connect_error);
                        }
                        
                        $stmt = $conn->prepare("
                            SELECT ja.*, j.job_title 
                            FROM job_applications ja 
                            JOIN jobs j ON ja.job_id = j.id 
                            WHERE ja.is_active = 1 
                            ORDER BY ja.applied_date DESC 
                            LIMIT 5
                        ");
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $statusClass = 'status-' . $row['application_status'];
                                echo '<div class="recent-item">';
                                echo '<div class="item-info">';
                                echo '<h4>' . htmlspecialchars($row['student_name']) . '</h4>';
                                echo '<p>' . htmlspecialchars($row['job_title']) . ' at ' . htmlspecialchars($row['company_name']) . '</p>';
                                echo '</div>';
                                echo '<span class="item-status ' . $statusClass . '">' . ucfirst($row['application_status']) . '</span>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p style="color: rgba(255, 255, 255, 0.7); text-align: center;">No applications yet</p>';
                        }
                        
                        $conn->close();
                    } catch (Exception $e) {
                        echo '<p style="color: var(--error-color); text-align: center;">Error loading applications: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    }
                    ?>
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