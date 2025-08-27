<?php
// Test file for successful candidates API
header('Content-Type: text/html; charset=utf-8');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Successful Candidates API</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; }
        pre { background-color: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        button { padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <h1>Test Successful Candidates API</h1>
    
    <div class="test-section">
        <h2>Test 1: Fetch Successful Candidates</h2>
        <button onclick="testSuccessfulCandidates()">Test API</button>
        <div id="result1"></div>
    </div>
    
    <div class="test-section">
        <h2>Test 2: Check Database Connection</h2>
        <button onclick="testDatabaseConnection()">Test Database</button>
        <div id="result2"></div>
    </div>
    
    <div class="test-section">
        <h2>Test 3: View Raw Data</h2>
        <button onclick="viewRawData()">View Data</button>
        <div id="result3"></div>
    </div>

    <script>
        async function testSuccessfulCandidates() {
            const resultDiv = document.getElementById('result1');
            resultDiv.innerHTML = '<p>Testing...</p>';
            
            try {
                const response = await fetch('fetch_successful_candidates.php');
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            <h3>✅ API Test Successful!</h3>
                            <p><strong>Message:</strong> ${data.message}</p>
                            <p><strong>Count:</strong> ${data.count}</p>
                            <p><strong>Last Updated:</strong> ${data.last_updated}</p>
                            <h4>Sample Data:</h4>
                            <pre>${JSON.stringify(data.data.slice(0, 2), null, 2)}</pre>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="error">
                            <h3>❌ API Test Failed!</h3>
                            <p><strong>Error:</strong> ${data.message}</p>
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="error">
                        <h3>❌ Network Error!</h3>
                        <p><strong>Error:</strong> ${error.message}</p>
                    </div>
                `;
            }
        }
        
        async function testDatabaseConnection() {
            const resultDiv = document.getElementById('result2');
            resultDiv.innerHTML = '<p>Testing database connection...</p>';
            
            try {
                const response = await fetch('db_config.php');
                const text = await response.text();
                
                if (text.includes('PDO') || text.includes('mysqli')) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            <h3>✅ Database Config Found!</h3>
                            <p>Database configuration file is accessible.</p>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="error">
                            <h3>❌ Database Config Issue!</h3>
                            <p>Database configuration file may not be properly configured.</p>
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="error">
                        <h3>❌ Database Test Failed!</h3>
                        <p><strong>Error:</strong> ${error.message}</p>
                    </div>
                `;
            }
        }
        
        async function viewRawData() {
            const resultDiv = document.getElementById('result3');
            resultDiv.innerHTML = '<p>Fetching raw data...</p>';
            
            try {
                const response = await fetch('fetch_successful_candidates.php');
                const text = await response.text();
                
                resultDiv.innerHTML = `
                    <div class="success">
                        <h3>📊 Raw API Response</h3>
                        <pre>${text}</pre>
                    </div>
                `;
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="error">
                        <h3>❌ Failed to fetch raw data!</h3>
                        <p><strong>Error:</strong> ${error.message}</p>
                    </div>
                `;
            }
        }
    </script>
</body>
</html> 