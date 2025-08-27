<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'db_config.php';

try {
    $pdo = getDBConnection();
    
    // Check exact count of accepted candidates
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_accepted
        FROM job_applications 
        WHERE is_active = 1 AND application_status = 'accepted'
    ");
    $stmt->execute();
    $countResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalAccepted = $countResult['total_accepted'];
    
    // Get all accepted candidates with details
    $stmt = $pdo->prepare("
        SELECT 
            id,
            student_name,
            company_name,
            application_status,
            applied_date,
            photo_path,
            company_logo
        FROM job_applications 
        WHERE is_active = 1 AND application_status = 'accepted'
        ORDER BY applied_date DESC
    ");
    $stmt->execute();
    $acceptedCandidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h1>Accepted Candidates Debug</h1>";
    echo "<h2>Total Accepted: $totalAccepted</h2>";
    
    echo "<h3>All Accepted Candidates:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Company</th><th>Status</th><th>Date</th><th>Photo Path</th><th>Logo</th></tr>";
    
    foreach ($acceptedCandidates as $candidate) {
        echo "<tr>";
        echo "<td>{$candidate['id']}</td>";
        echo "<td>{$candidate['student_name']}</td>";
        echo "<td>{$candidate['company_name']}</td>";
        echo "<td>{$candidate['application_status']}</td>";
        echo "<td>{$candidate['applied_date']}</td>";
        echo "<td>{$candidate['photo_path']}</td>";
        echo "<td>{$candidate['company_logo']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Test API Response:</h3>";
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
                    <h4>Raw Data:</h4>
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