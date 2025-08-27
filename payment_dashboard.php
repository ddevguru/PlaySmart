<?php
// Payment Dashboard for PlaySmart Admin
// This page shows all payment records and allows admin management

session_start();

// Check if admin is logged in (you can implement your own admin authentication)
$isAdmin = true; // For now, set to true. Implement proper admin authentication

if (!$isAdmin) {
    header('Location: admin_login.php');
    exit;
}

// Load database config
require_once 'db_config.php';

// Set database connection variables
$host = DB_HOST;
$dbname = DB_NAME;
$username = DB_USERNAME;
$password = DB_PASSWORD;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get payment statistics
    $statsSql = "SELECT 
                    COUNT(*) as total_payments,
                    SUM(CASE WHEN payment_status = 'completed' THEN 1 ELSE 0 END) as completed_payments,
                    SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
                    SUM(CASE WHEN payment_status = 'failed' THEN 1 ELSE 0 END) as failed_payments,
                    SUM(CASE WHEN payment_status = 'completed' THEN amount ELSE 0 END) as total_amount
                  FROM payment_tracking 
                  WHERE is_active = 1";
    
    $statsStmt = $pdo->query($statsSql);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent payments
    $recentPaymentsSql = "SELECT 
                            pt.*,
                            ja.student_name,
                            ja.company_name,
                            ja.profile,
                            ja.email
                          FROM payment_tracking pt
                          LEFT JOIN job_applications ja ON pt.application_id = ja.id
                          WHERE pt.is_active = 1
                          ORDER BY pt.created_at DESC
                          LIMIT 20";
    
    $recentPaymentsStmt = $pdo->query($recentPaymentsSql);
    $recentPayments = $recentPaymentsStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Dashboard - PlaySmart Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .payment-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-completed { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-failed { background-color: #f8d7da; color: #721c24; }
        .status-refunded { background-color: #e2e3e5; color: #383d41; }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="fas fa-credit-card text-primary"></i>
                    Payment Dashboard
                </h1>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Error: <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo $stats['total_payments'] ?? 0; ?></h4>
                                    <small>Total Payments</small>
                                </div>
                                <i class="fas fa-credit-card fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo $stats['completed_payments'] ?? 0; ?></h4>
                                    <small>Completed</small>
                                </div>
                                <i class="fas fa-check-circle fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0">₹<?php echo number_format($stats['total_amount'] ?? 0, 2); ?></h4>
                                    <small>Total Amount</small>
                                </div>
                                <i class="fas fa-rupee-sign fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="stats-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo $stats['pending_payments'] ?? 0; ?></h4>
                                    <small>Pending</small>
                                </div>
                                <i class="fas fa-clock fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Payments Table -->
                <div class="card payment-table">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-list"></i>
                            Recent Payments
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>Student Name</th>
                                        <th>Company</th>
                                        <th>Profile</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recentPayments)): ?>
                                        <?php foreach ($recentPayments as $payment): ?>
                                            <tr>
                                                <td>
                                                    <small class="text-muted"><?php echo htmlspecialchars($payment['payment_id']); ?></small>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($payment['student_name'] ?? 'N/A'); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($payment['email'] ?? ''); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($payment['company_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($payment['profile'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <strong class="text-success">₹<?php echo number_format($payment['amount'], 2); ?></strong>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusClass = 'status-' . $payment['payment_status'];
                                                    $statusText = ucfirst($payment['payment_status']);
                                                    ?>
                                                    <span class="status-badge <?php echo $statusClass; ?>">
                                                        <?php echo $statusText; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></small>
                                                    <br>
                                                    <small class="text-muted"><?php echo date('H:i', strtotime($payment['created_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary btn-sm" 
                                                                onclick="viewPaymentDetails(<?php echo $payment['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if ($payment['payment_status'] === 'completed'): ?>
                                                            <button class="btn btn-outline-warning btn-sm" 
                                                                    onclick="processRefund(<?php echo $payment['id']; ?>, '<?php echo $payment['razorpay_payment_id']; ?>')">
                                                                <i class="fas fa-undo"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                                <p>No payment records found</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Export Buttons -->
                <div class="mt-4">
                    <button class="btn btn-success me-2" onclick="exportPayments('csv')">
                        <i class="fas fa-download"></i> Export to CSV
                    </button>
                    <button class="btn btn-info me-2" onclick="exportPayments('pdf')">
                        <i class="fas fa-file-pdf"></i> Export to PDF
                    </button>
                    <button class="btn btn-secondary" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Details Modal -->
    <div class="modal fade" id="paymentDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Payment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="paymentDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Refund Modal -->
    <div class="modal fade" id="refundModal" tabindex="-1">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Process Refund</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="refundForm">
                    <div class="mb-3">
                        <label class="form-label">Refund Amount</label>
                        <input type="number" class="form-control" id="refundAmount" step="0.01" required>
                        <small class="text-muted">Leave empty for full refund</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Refund Reason</label>
                        <textarea class="form-control" id="refundReason" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="confirmRefund()">Process Refund</button>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentPaymentId = null;
        let currentRazorpayPaymentId = null;
        
        function viewPaymentDetails(paymentId) {
            currentPaymentId = paymentId;
            
            // Load payment details via AJAX
            fetch(`check_payment_status.php?payment_id=${paymentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.payments && data.data.payments.length > 0) {
                        const payment = data.data.payments[0];
                        displayPaymentDetails(payment);
                    } else {
                        alert('Payment details not found');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading payment details');
                });
        }
        
        function displayPaymentDetails(payment) {
            const content = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Payment Information</h6>
                        <p><strong>Payment ID:</strong> ${payment.payment_id}</p>
                        <p><strong>Razorpay Payment ID:</strong> ${payment.razorpay_payment_id || 'N/A'}</p>
                        <p><strong>Amount:</strong> ₹${parseFloat(payment.amount).toFixed(2)}</p>
                        <p><strong>Status:</strong> <span class="status-badge status-${payment.payment_status}">${payment.payment_status}</span></p>
                        <p><strong>Method:</strong> ${payment.payment_method || 'N/A'}</p>
                        <p><strong>Date:</strong> ${new Date(payment.payment_date).toLocaleString()}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Application Information</h6>
                        <p><strong>Student Name:</strong> ${payment.student_name || 'N/A'}</p>
                        <p><strong>Company:</strong> ${payment.company_name || 'N/A'}</p>
                        <p><strong>Profile:</strong> ${payment.profile || 'N/A'}</p>
                        <p><strong>Email:</strong> ${payment.email || 'N/A'}</p>
                        <p><strong>Phone:</strong> ${payment.phone || 'N/A'}</p>
                        <p><strong>District:</strong> ${payment.district || 'N/A'}</p>
                    </div>
                </div>
            `;
            
            document.getElementById('paymentDetailsContent').innerHTML = content;
            new bootstrap.Modal(document.getElementById('paymentDetailsModal')).show();
        }
        
        function processRefund(paymentId, razorpayPaymentId) {
            currentPaymentId = paymentId;
            currentRazorpayPaymentId = razorpayPaymentId;
            
            // Reset form
            document.getElementById('refundForm').reset();
            
            new bootstrap.Modal(document.getElementById('refundModal')).show();
        }
        
        function confirmRefund() {
            const amount = document.getElementById('refundAmount').value;
            const reason = document.getElementById('refundReason').value;
            
            if (!reason.trim()) {
                alert('Please provide a refund reason');
                return;
            }
            
            // Process refund via AJAX
            fetch('process_refund.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    payment_id: currentPaymentId,
                    razorpay_payment_id: currentRazorpayPaymentId,
                    refund_amount: amount || null,
                    refund_reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Refund processed successfully');
                    bootstrap.Modal.getInstance(document.getElementById('refundModal')).hide();
                    refreshDashboard();
                } else {
                    alert('Refund failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error processing refund');
            });
        }
        
        function exportPayments(format) {
            // Implement export functionality
            alert(`${format.toUpperCase()} export functionality will be implemented here`);
        }
        
        function refreshDashboard() {
            location.reload();
        }
        
        // Auto-refresh every 30 seconds
        setInterval(refreshDashboard, 30000);
    </script>
</body>
</html> 