<?php
include 'config.php';

// Pagination setup
$items_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Search setup
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_query = '';
$search_params = [];
$param_types = '';

// Build search query if search term is provided
if (!empty($search)) {
    $search = "%$search%";
    $search_query = " WHERE username LIKE ? OR email LIKE ? OR phone LIKE ?";
    $search_params = [$search, $search, $search];
    $param_types = "sss";
}

// Fetch total users count (with search filter)
$total_sql = "SELECT COUNT(*) FROM users" . $search_query;
$stmt = $conn->prepare($total_sql);
if (!empty($search)) {
    $stmt->bind_param($param_types, ...$search_params);
}
$stmt->execute();
$total_result = $stmt->get_result();
$total_users = $total_result->fetch_row()[0];
$total_pages = ceil($total_users / $items_per_page);
$stmt->close();

// Fetch users list with pagination and search
$sql = "SELECT id, username, email, phone, referral_code, wallet_balance, created_at, last_login, last_active 
        FROM users" . $search_query . " LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if (!empty($search)) {
    $stmt->bind_param($param_types . "ii", ...array_merge($search_params, [$items_per_page, $offset]));
} else {
    $stmt->bind_param("ii", $items_per_page, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
$users = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
$stmt->close();

// Handle wallet balance update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_wallet'])) {
    $user_id = $_POST['user_id'];
    $new_balance = $_POST['wallet_balance'];
    
    // Validate input
    if (is_numeric($new_balance) && $new_balance >= 0) {
        $update_sql = "UPDATE users SET wallet_balance = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("di", $new_balance, $user_id);
        
        if ($stmt->execute()) {
            // Refresh the page to show updated balance on current page
            $redirect_url = "users_list.php?page=$page";
            if (!empty($search)) {
                $redirect_url .= "&search=" . urlencode($search);
            }
            header("Location: $redirect_url&success=Wallet balance updated successfully");
            exit();
        } else {
            $error = "Failed to update wallet balance";
        }
        $stmt->close();
    } else {
        $error = "Invalid wallet balance amount";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Play Smart - Users List</title>
    <link rel="stylesheet" href="css/users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #4B0082;
            color: #FFFFFF;
            font-family: 'Poppins', sans-serif;
        }
        .dashboard-container {
            display: flex;
        }
        .sidebar {
            width: 250px;
            background-color: #2A004D;
            color: #FFFFFF;
            height: 100vh;
            position: fixed;
        }
        .sidebar-header .logo {
            background-color: #FFFFFF;
            color: #4B0082;
        }
        .sidebar-menu ul li a {
            color: #FFFFFF;
        }
        .sidebar-menu ul li.active a {
            background-color: #6A0DAD;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        }
        .content-header {
            background-color: #4B0082;
            padding: 15px;
            border-radius: 8px 8px 0 0;
        }
        .content-header h1 {
            color: #FFFFFF;
        }
        .card {
            background-color: #FFFFFF;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #6A0DAD;
            padding: 15px;
            color: #FFFFFF;
        }
        .card-body {
            padding: 20px;
        }
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
            scroll-behavior: smooth;
        }
        .users-table {
            width: 100%;
            border-collapse: collapse;
            background: #FFFFFF;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .users-table th, .users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #DDD;
            color: #333333;
        }
        .users-table th {
            background-color: #6A0DAD;
            color: #FFFFFF;
            font-weight: 600;
        }
        .users-table tr:hover {
            background-color: #F1F1F1;
        }
        .update-balance-btn {
            background-color: #007BFF;
            color: #FFFFFF;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .update-balance-btn:hover {
            background-color: #0056B3;
        }
        .search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            max-width: 400px;
            position: relative;
        }
        .search-container input {
            flex: 1;
            padding: 8px;
            border: 1px solid #DDD;
            border-radius: 4px;
            color: #333333;
        }
        .search-container button {
            padding: 8px 16px;
            background-color: #007BFF;
            color: #FFFFFF;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .search-container button:hover {
            background-color: #0056B3;
        }
        .clear-search-btn {
            padding: 8px 16px;
            background-color: #F44336;
            color: #FFFFFF;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .clear-search-btn:hover {
            background-color: #D32F2F;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal-content {
            background-color: #FFFFFF;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
            color: #333333;
        }
        .modal-content h3 {
            margin-top: 0;
        }
        .modal-content form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .modal-content input[type="number"] {
            padding: 8px;
            border: 1px solid #DDD;
            border-radius: 4px;
        }
        .modal-content .button-group {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .modal-content button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .modal-content .save-btn {
            background-color: #4CAF50;
            color: #FFFFFF;
        }
        .modal-content .cancel-btn {
            background-color: #F44336;
            color: #FFFFFF;
        }
        .alert {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .alert-success {
            background-color: #D4EDDA;
            color: #155724;
        }
        .alert-danger {
            background-color: #F8D7DA;
            color: #721C24;
        }
        .alert-info {
            background-color: #CCE5FF;
            color: #004085;
        }
        .pagination {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-start;
            overflow-x: auto;
            padding: 10px 0;
            scrollbar-width: thin;
            scrollbar-color: #6A0DAD #f0f0f0;
            max-width: 100%;
            scroll-behavior: smooth;
            position: relative;
        }
        .pagination::-webkit-scrollbar {
            height: 8px;
        }
        .pagination::-webkit-scrollbar-track {
            background: #f0f0f0;
            border-radius: 4px;
        }
        .pagination::-webkit-scrollbar-thumb {
            background: #6A0DAD;
            border-radius: 4px;
        }
        .pagination::-webkit-scrollbar-thumb:hover {
            background: #4B0082;
        }
        .pagination a {
            padding: 8px 12px;
            background-color: #007BFF;
            color: #FFFFFF;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            white-space: nowrap;
            min-width: 40px;
            flex-shrink: 0;
        }
        .pagination a.disabled {
            background-color: #6C757D;
            cursor: not-allowed;
        }
        .pagination a.active {
            background-color: #6A0DAD;
        }
        .pagination a:hover:not(.disabled) {
            background-color: #0056B3;
        }
        .pagination-nav {
            background-color: #6A0DAD !important;
            font-weight: 600;
        }
        .pagination-nav:hover {
            background-color: #4B0082 !important;
        }
        .pagination-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }
        .scroll-nav {
            background-color: #6A0DAD;
            color: #FFFFFF;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: background-color 0.3s ease;
        }
        .scroll-nav:hover {
            background-color: #4B0082;
        }
        .scroll-nav:disabled {
            background-color: #6C757D;
            cursor: not-allowed;
        }
        .scroll-nav i {
            font-size: 14px;
        }
        @media screen and (max-width: 768px) {
            .users-table {
                display: block;
                overflow-x: auto;
            }
            .users-table thead {
                display: none;
            }
            .users-table tbody, .users-table tr {
                display: block;
            }
            .users-table tr {
                margin-bottom: 15px;
                border: 1px solid #DDD;
                border-radius: 4px;
                padding: 10px;
            }
            .users-table td {
                display: flex;
                justify-content: space-between;
                padding: 8px;
                border-bottom: none;
                color: #333333;
            }
            .users-table td:before {
                content: attr(data-label);
                font-weight: 600;
                width: 40%;
                min-width: 100px;
                color: #333333;
            }
            .search-container {
                flex-direction: column;
                max-width: 100%;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            .sidebar {
                transform: translateX(-250px);
                transition: transform 0.3s ease;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            body.sidebar-active {
                overflow: hidden;
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
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
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
                    <li><a href="create_contest.php"><i class="fas fa-trophy"></i><span>Create Mega Contest</span></a></li>
                    <li><a href="edit_contest.php"><i class="fas fa-edit"></i><span>Create Link</span></a></li>
                    <li><a href="contest_list.php"><i class="fas fa-list"></i><span>Contest List</span></a></li>
                    <li><a href="add_questions.php"><i class="fas fa-question-circle"></i><span>Add Questions</span></a></li>
                    <li><a href="question_list.php"><i class="fas fa-list-ul"></i><span>Question List</span></a></li>
                    <li><a href="view_score.php"><i class="fas fa-chart-bar"></i><span>View Score</span></a></li>
                    <li class="active"><a href="users_list.php"><i class="fas fa-users"></i><span>Users List</span></a></li>
                    <li><a href="withdrawal_requests.php"><i class="fas fa-money-bill-wave"></i><span>Withdrawal Request</span></a></li>
                    <li><a href="payment_history.php"><i class="fas fa-history"></i><span>Payment History</span></a></li>
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
                    <h1>Users List</h1>
                </div>
            </header>
            <div class="content-body">
                <div class="card">
                    <div class="card-header">
                        <h2>Users</h2>
                        <p>View all registered users</p>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <?php echo htmlspecialchars($_GET['success']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($search)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Found <?php echo $total_users; ?> user<?php echo $total_users !== 1 ? 's' : ''; ?> matching '<?php echo htmlspecialchars($search); ?>'.
                            </div>
                        <?php endif; ?>
                        <div class="search-container">
                            <form method="GET" action="users_list.php" id="searchForm">
                                <input type="text" id="searchInput" name="search" placeholder="Search by username, email, or phone..." value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" id="searchButton"><i class="fas fa-search"></i> Search</button>
                                <?php if (!empty($search)): ?>
                                    <button type="button" class="clear-search-btn" onclick="window.location.href='users_list.php'">Clear Search</button>
                                <?php endif; ?>
                            </form>
                        </div>
                        <?php if (empty($users)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                No users found.
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="users-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Referral Code</th>
                                            <th>Wallet Balance ($)</th>
                                            <th>Created At</th>
                                            <th>Last Login</th>
                                            <th>Last Active</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="userTableBody">
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td data-label="ID"><?php echo htmlspecialchars($user['id']); ?></td>
                                                <td data-label="Username"><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td data-label="Email"><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td data-label="Phone"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                                <td data-label="Referral Code"><?php echo htmlspecialchars($user['referral_code'] ?? 'N/A'); ?></td>
                                                <td data-label="Wallet Balance ($)">$<?php echo number_format($user['wallet_balance'], 2); ?></td>
                                                <td data-label="Created At"><?php echo htmlspecialchars($user['created_at']); ?></td>
                                                <td data-label="Last Login"><?php echo htmlspecialchars($user['last_login'] ?? 'Never'); ?></td>
                                                <td data-label="Last Active"><?php echo htmlspecialchars($user['last_active'] ?? 'Never'); ?></td>
                                                <td data-label="Action">
                                                    <button class="update-balance-btn" data-user-id="<?php echo $user['id']; ?>" data-current-balance="<?php echo $user['wallet_balance']; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                        <i class="fas fa-wallet"></i> Update Balance
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="pagination-wrapper">
                                <button class="scroll-nav scroll-left" id="scrollLeftBtn" title="Scroll Left">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <div class="pagination" id="paginationContainer">
                                    <a href="users_list.php?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-nav">First</a>
                                    <a href="users_list.php?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="<?php echo $page <= 1 ? 'disabled' : ''; ?>">Previous</a>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <a href="users_list.php?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
                                    <?php endfor; ?>
                                    <a href="users_list.php?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="<?php echo $page >= $total_pages ? 'disabled' : ''; ?>">Next</a>
                                    <a href="users_list.php?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-nav">Last</a>
                                </div>
                                <button class="scroll-nav scroll-right" id="scrollRightBtn" title="Scroll Right">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal for updating wallet balance -->
    <div id="walletModal" class="modal">
        <div class="modal-content">
            <h3>Update Wallet Balance for <span id="modalUsername"></span></h3>
            <form method="POST" action="users_list.php">
                <input type="hidden" name="user_id" id="modalUserId">
                <input type="hidden" name="page" value="<?php echo $page; ?>">
                <?php if (!empty($search)): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <?php endif; ?>
                <label for="wallet_balance">New Wallet Balance ($):</label>
                <input type="number" name="wallet_balance" id="wallet_balance" step="0.01" min="0" required>
                <div class="button-group">
                    <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="update_wallet" class="save-btn">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.sidebar');
            const sidebarToggle = document.querySelector('#sidebar-toggle');
            const modal = document.querySelector('#walletModal');
            const modalUsername = document.querySelector('#modalUsername');
            const modalUserId = document.querySelector('#modalUserId');
            const walletBalanceInput = document.querySelector('#wallet_balance');
            const updateButtons = document.querySelectorAll('.update-balance-btn');
            const searchInput = document.querySelector('#searchInput');
            const searchButton = document.querySelector('#searchButton');
            const userTableBody = document.querySelector('#userTableBody');
            const searchForm = document.querySelector('#searchForm');

            // Sidebar toggle
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                document.body.classList.toggle('sidebar-active');
            });

            document.addEventListener('click', (e) => {
                if (window.innerWidth < 768 && sidebar.classList.contains('active') && !sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                    document.body.classList.remove('sidebar-active');
                }
            });

            // Modal handling
            updateButtons.forEach(button => {
                button.addEventListener('click', () => {
                    modalUsername.textContent = button.dataset.username;
                    modalUserId.value = button.dataset.userId;
                    walletBalanceInput.value = button.dataset.currentBalance;
                    modal.style.display = 'block';
                });
            });

            window.closeModal = function() {
                modal.style.display = 'none';
            };

            // Close modal when clicking outside
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal();
                }
            });

            // Client-side search for immediate feedback
            window.searchUsers = function() {
                const searchTerm = searchInput.value.toLowerCase();
                const rows = userTableBody.querySelectorAll('tr');

                rows.forEach(row => {
                    const username = row.querySelector('td[data-label="Username"]').textContent.toLowerCase();
                    const email = row.querySelector('td[data-label="Email"]').textContent.toLowerCase();
                    const phone = row.querySelector('td[data-label="Phone"]').textContent.toLowerCase();

                    if (username.includes(searchTerm) || email.includes(searchTerm) || phone.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            };

            // Trigger client-side search on input with debounce
            let debounceTimeout;
            searchInput.addEventListener('input', () => {
                clearTimeout(debounceTimeout);
                debounceTimeout = setTimeout(searchUsers, 300);
            });

            // Submit form on Enter key
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchForm.submit();
                }
            });

            // Ensure search button submits the form
            searchButton.addEventListener('click', (e) => {
                e.preventDefault(); // Prevent any default behavior
                searchForm.submit(); // Explicitly submit the form
            });

            // Auto-scroll pagination to active page
            const paginationContainer = document.querySelector('#paginationContainer');
            const activePageLink = document.querySelector('.pagination a.active');
            
            if (paginationContainer && activePageLink) {
                // Calculate scroll position to center the active page
                const containerWidth = paginationContainer.offsetWidth;
                const activePageLeft = activePageLink.offsetLeft;
                const activePageWidth = activePageLink.offsetWidth;
                
                // Scroll to center the active page
                const scrollLeft = activePageLeft - (containerWidth / 2) + (activePageWidth / 2);
                paginationContainer.scrollLeft = Math.max(0, scrollLeft);
            }

            // Scroll navigation functionality
            const scrollLeftBtn = document.querySelector('#scrollLeftBtn');
            const scrollRightBtn = document.querySelector('#scrollRightBtn');
            
            if (scrollLeftBtn && scrollRightBtn && paginationContainer) {
                // Scroll left button
                scrollLeftBtn.addEventListener('click', () => {
                    paginationContainer.scrollBy({
                        left: -200,
                        behavior: 'smooth'
                    });
                });
                
                // Scroll right button
                scrollRightBtn.addEventListener('click', () => {
                    paginationContainer.scrollBy({
                        left: 200,
                        behavior: 'smooth'
                    });
                });
                
                // Update button states based on scroll position
                const updateScrollButtonStates = () => {
                    scrollLeftBtn.disabled = paginationContainer.scrollLeft <= 0;
                    scrollRightBtn.disabled = paginationContainer.scrollLeft >= paginationContainer.scrollWidth - paginationContainer.clientWidth;
                };
                
                // Initial state
                updateScrollButtonStates();
                
                // Update on scroll
                paginationContainer.addEventListener('scroll', updateScrollButtonStates);
            }
        });
    </script>
</body>
</html> 