<?php
header('Content-Type: text/html; charset=utf-8');
?>

<!DOCTYPE html>
<html>
<head>
    <title>API Test</title>
</head>
<body>
    <h1>Testing Job Applications API</h1>
    
    <h2>1. Test fetch_job_applications.php</h2>
    <button onclick="testJobApplications()">Test Job Applications API</button>
    <div id="result1"></div>
    
    <h2>2. Test fetch_successful_candidates.php</h2>
    <button onclick="testSuccessfulCandidates()">Test Successful Candidates API</button>
    <div id="result2"></div>
    
    <h2>3. Raw API Response</h2>
    <button onclick="showRawResponse()">Show Raw Response</button>
    <div id="result3"></div>

    <script>
        async function testJobApplications() {
            const resultDiv = document.getElementById('result1');
            resultDiv.innerHTML = '<p>Testing...</p>';
            
            try {
                const response = await fetch('fetch_job_applications.php');
                const text = await response.text();
                
                try {
                    const data = JSON.parse(text);
                    resultDiv.innerHTML = `
                        <div style="background-color: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;">
                            <h3>✅ API Response Parsed Successfully!</h3>
                            <p><strong>Success:</strong> ${data.success}</p>
                            <p><strong>Message:</strong> ${data.message}</p>
                            <p><strong>Data Type:</strong> ${typeof data.data}</p>
                            <p><strong>Data Length:</strong> ${Array.isArray(data.data) ? data.data.length : 'Not an array'}</p>
                            <p><strong>Count:</strong> ${data.count}</p>
                            <h4>First 2 Items:</h4>
                            <pre>${JSON.stringify(data.data ? data.data.slice(0, 2) : 'No data', null, 2)}</pre>
                        </div>
                    `;
                } catch (parseError) {
                    resultDiv.innerHTML = `
                        <div style="background-color: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;">
                            <h3>❌ JSON Parse Error!</h3>
                            <p><strong>Error:</strong> ${parseError.message}</p>
                            <h4>Raw Response:</h4>
                            <pre>${text}</pre>
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
        
        async function testSuccessfulCandidates() {
            const resultDiv = document.getElementById('result2');
            resultDiv.innerHTML = '<p>Testing...</p>';
            
            try {
                const response = await fetch('fetch_successful_candidates.php');
                const text = await response.text();
                
                try {
                    const data = JSON.parse(text);
                    resultDiv.innerHTML = `
                        <div style="background-color: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;">
                            <h3>✅ Successful Candidates API Working!</h3>
                            <p><strong>Success:</strong> ${data.success}</p>
                            <p><strong>Message:</strong> ${data.message}</p>
                            <p><strong>Count:</strong> ${data.count}</p>
                            <h4>All Accepted Candidates:</h4>
                            <pre>${JSON.stringify(data.data, null, 2)}</pre>
                        </div>
                    `;
                } catch (parseError) {
                    resultDiv.innerHTML = `
                        <div style="background-color: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;">
                            <h3>❌ JSON Parse Error!</h3>
                            <p><strong>Error:</strong> ${parseError.message}</p>
                            <h4>Raw Response:</h4>
                            <pre>${text}</pre>
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
        
        async function showRawResponse() {
            const resultDiv = document.getElementById('result3');
            resultDiv.innerHTML = '<p>Fetching raw response...</p>';
            
            try {
                const response = await fetch('fetch_job_applications.php');
                const text = await response.text();
                
                resultDiv.innerHTML = `
                    <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;">
                        <h3>📄 Raw API Response</h3>
                        <pre style="background-color: #e9ecef; padding: 10px; border-radius: 5px; overflow-x: auto;">${text}</pre>
                    </div>
                `;
            } catch (error) {
                resultDiv.innerHTML = `
                    <div style="background-color: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;">
                        <h3>❌ Error!</h3>
                        <p><strong>Error:</strong> ${error.message}</p>
                    </div>
                `;
            }
        }
    </script>
</body>
</html> 