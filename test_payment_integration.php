<?php
// Test Payment Integration for PlaySmart
header('Content-Type: text/html');

echo "<h1>Payment Integration Test</h1>";

// Test 1: Check if files exist
echo "<h2>Test 1: File Existence</h2>";
$requiredFiles = [
    'payment_integration.php',
    'razorpay_config.php',
    'db_config.php',
    'submit_job_application_with_files.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file not found<br>";
    }
}

// Test 2: Check database connection
echo "<h2>Test 2: Database Connection</h2>";
if (file_exists('db_config.php')) {
    include_once 'db_config.php';
    
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "✅ Database connection successful<br>";
        
        // Check if payment_tracking table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'payment_tracking'");
        if ($stmt->rowCount() > 0) {
            echo "✅ payment_tracking table exists<br>";
        } else {
            echo "❌ payment_tracking table not found<br>";
        }
        
        // Check if job_applications table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'job_applications'");
        if ($stmt->rowCount() > 0) {
            echo "✅ job_applications table exists<br>";
        } else {
            echo "❌ job_applications table not found<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ db_config.php not found<br>";
}

// Test 3: Check Razorpay configuration
echo "<h2>Test 3: Razorpay Configuration</h2>";
if (file_exists('razorpay_config.php')) {
    include_once 'razorpay_config.php';
    
    if (defined('RAZORPAY_KEY_ID') && defined('RAZORPAY_KEY_SECRET')) {
        echo "✅ Razorpay configuration loaded<br>";
        echo "Key ID: " . (RAZORPAY_KEY_ID ? '***SET***' : 'NOT SET') . "<br>";
        echo "Key Secret: " . (RAZORPAY_KEY_SECRET ? '***SET***' : 'NOT SET') . "<br>";
        echo "Test Mode: " . (RAZORPAY_TEST_MODE ? 'Yes' : 'No') . "<br>";
    } else {
        echo "❌ Razorpay configuration not properly defined<br>";
    }
} else {
    echo "❌ razorpay_config.php not found<br>";
}

// Test 4: Test payment integration API
echo "<h2>Test 4: Payment Integration API Test</h2>";
echo "<form id='paymentTestForm'>";
echo "<label>Application ID: <input type='number' id='appId' value='1' required></label><br>";
echo "<label>Payment Amount: <input type='number' id='amount' value='2000' required></label><br>";
echo "<label>Job Type: <select id='jobType'><option value='higher_package'>Higher Package (₹2000)</option><option value='local'>Local (₹1000)</option></select></label><br>";
echo "<button type='button' onclick='testPaymentIntegration()'>Test Payment Integration</button>";
echo "</form>";

echo "<div id='testResult'></div>";

// Test 5: Check log files
echo "<h2>Test 5: Log Files</h2>";
$logFiles = [
    'payment_integration_log.txt',
    'debug_log.txt',
    'payment_debug_log.txt'
];

foreach ($logFiles as $logFile) {
    if (file_exists($logFile)) {
        echo "✅ $logFile exists<br>";
        $size = filesize($logFile);
        echo "Size: " . ($size > 0 ? $size . " bytes" : "Empty") . "<br>";
    } else {
        echo "❌ $logFile not found<br>";
    }
}

echo "<hr>";
echo "<p><strong>Server Info:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>cURL Extension:</strong> " . (extension_loaded('curl') ? '✅ Available' : '❌ Not Available') . "</p>";
echo "<p><strong>JSON Extension:</strong> " . (extension_loaded('json') ? '✅ Available' : '❌ Not Available') . "</p>";
?>

<script>
async function testPaymentIntegration() {
    const appId = document.getElementById('appId').value;
    const amount = document.getElementById('amount').value;
    const jobType = document.getElementById('jobType').value;
    const resultDiv = document.getElementById('testResult');
    
    resultDiv.innerHTML = '<p>Testing payment integration...</p>';
    
    try {
        const response = await fetch('payment_integration.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                application_id: parseInt(appId),
                payment_amount: parseInt(amount),
                job_type: jobType
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            resultDiv.innerHTML = `
                <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;">
                    <h4>✅ Payment Integration Test Successful!</h4>
                    <p><strong>Message:</strong> ${data.message}</p>
                    <p><strong>Application ID:</strong> ${data.data.application_id}</p>
                    <p><strong>Amount:</strong> ₹${data.data.payment_amount}</p>
                    <p><strong>Order ID:</strong> ${data.data.razorpay_order_id}</p>
                    <p><strong>Job Type:</strong> ${data.data.job_type}</p>
                    <p><strong>Key ID:</strong> ${data.data.key_id}</p>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;">
                    <h4>❌ Payment Integration Test Failed!</h4>
                    <p><strong>Error:</strong> ${data.message}</p>
                    <p><strong>Debug Info:</strong> ${JSON.stringify(data.debug_info)}</p>
                </div>
            `;
        }
    } catch (error) {
        resultDiv.innerHTML = `
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;">
                <h4>❌ Test Error!</h4>
                <p><strong>Error:</strong> ${error.message}</p>
            </div>
        `;
    }
}
</script> 