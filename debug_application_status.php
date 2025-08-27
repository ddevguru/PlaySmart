<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'db_config.php';

try {
    $pdo = getDBConnection();
    
    // Check all application statuses
    $stmt = $pdo->prepare("
        SELECT 
            id,
            student_name,
            company_name,
            application_status,
            applied_date
        FROM job_applications 
        WHERE is_active = 1 
        ORDER BY applied_date DESC
    ");
    
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count by status
    $statusCounts = [];
    foreach ($applications as $app) {
        $status = $app['application_status'];
        if (!isset($statusCounts[$status])) {
            $statusCounts[$status] = 0;
        }
        $statusCounts[$status]++;
    }
    
    echo "<h1>Application Status Debug</h1>";
    echo "<h2>Status Counts:</h2>";
    echo "<ul>";
    foreach ($statusCounts as $status => $count) {
        echo "<li><strong>$status:</strong> $count candidates</li>";
    }
    echo "</ul>";
    
    echo "<h2>All Applications:</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Company</th><th>Status</th><th>Date</th></tr>";
    
    foreach ($applications as $app) {
        $statusColor = '';
        switch ($app['application_status']) {
            case 'accepted':
                $statusColor = 'background-color: #d4edda; color: #155724;';
                break;
            case 'shortlisted':
                $statusColor = 'background-color: #fff3cd; color: #856404;';
                break;
            case 'pending':
                $statusColor = 'background-color: #d1ecf1; color: #0c5460;';
                break;
            case 'rejected':
                $statusColor = 'background-color: #f8d7da; color: #721c24;';
                break;
        }
        
        echo "<tr>";
        echo "<td>{$app['id']}</td>";
        echo "<td>{$app['student_name']}</td>";
        echo "<td>{$app['company_name']}</td>";
        echo "<td style='$statusColor'>{$app['application_status']}</td>";
        echo "<td>{$app['applied_date']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>Test Successful Candidates API:</h2>";
    echo "<button onclick='testAPI()'>Test API</button>";
    echo "<div id='result'></div>";
    
} catch (Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>

<script>
async function testAPI() {
    const resultDiv = document.getElementById('result');
    resultDiv.innerHTML = '<p>Testing...</p>';
    
    try {
        const response = await fetch('fetch_successful_candidates.php');
        const data = await response.json();
        
        if (data.success) {
            resultDiv.innerHTML = `
                <div style="background-color: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;">
                    <h3>✅ API Test Successful!</h3>
                    <p><strong>Message:</strong> ${data.message}</p>
                    <p><strong>Count:</strong> ${data.count}</p>
                    <p><strong>Last Updated:</strong> ${data.last_updated}</p>
                    <h4>Accepted Candidates:</h4>
                    <pre>${JSON.stringify(data.data, null, 2)}</pre>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div style="background-color: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;">
                    <h3>❌ API Test Failed!</h3>
                    <p><strong>Error:</strong> ${data.message}</p>
                </div>
            `;
        }
    } catch (error) {
        resultDiv.innerHTML = `
            <div style="background-color: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;">
                <h3>❌ Network Error!</h3>
                <p><strong>Error:</strong> ${error.message}</p>
            </div>
        `;
    }
}
</script> 