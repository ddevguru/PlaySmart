<!DOCTYPE html>
<html>
<head>
    <title>Test Photo URLs - Fixed API</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .candidate { border: 1px solid #ddd; margin: 10px; padding: 15px; border-radius: 8px; }
        .photo { max-width: 200px; max-height: 200px; border: 2px solid #007bff; border-radius: 8px; }
        .error { color: red; }
        .success { color: green; }
        .url { word-break: break-all; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <h1>🧪 Test Photo URLs - Fixed API</h1>
    <p>Testing the corrected photo URL generation with Admin folder path</p>
    
    <div id="results">
        <p>Loading...</p>
    </div>

    <script>
        async function testPhotoURLs() {
            try {
                const response = await fetch('https://playsmart.co.in/fetch_successful_candidates.php');
                const data = await response.json();
                
                if (data.success) {
                    displayResults(data.data);
                } else {
                    document.getElementById('results').innerHTML = `<p class="error">❌ API Error: ${data.message}</p>`;
                }
            } catch (error) {
                document.getElementById('results').innerHTML = `<p class="error">❌ Fetch Error: ${error.message}</p>`;
            }
        }

        function displayResults(candidates) {
            const resultsDiv = document.getElementById('results');
            let html = `<h2>📸 Photo Test Results (${candidates.length} candidates)</h2>`;
            
            candidates.forEach((candidate, index) => {
                const photoUrl = candidate.photo_url;
                const hasPhoto = photoUrl && photoUrl !== '';
                
                html += `
                    <div class="candidate">
                        <h3>${index + 1}. ${candidate.student_name}</h3>
                        <p><strong>Company:</strong> ${candidate.company_name}</p>
                        <p><strong>Status:</strong> <span style="color: ${candidate.application_status === 'accepted' ? 'green' : 'orange'}">${candidate.application_status}</span></p>
                        <p><strong>Photo Path:</strong> ${candidate.photo_path || 'N/A'}</p>
                        <p><strong>Photo URL:</strong> <span class="url">${photoUrl || 'N/A'}</span></p>
                        
                        ${hasPhoto ? `
                            <div>
                                <p class="success">✅ Photo URL Generated</p>
                                <img src="${photoUrl}" alt="${candidate.student_name}" class="photo" 
                                     onerror="this.style.borderColor='red'; this.alt='❌ Photo Failed to Load'; this.title='Photo failed to load'"
                                     onload="this.style.borderColor='green'; this.title='✅ Photo loaded successfully'">
                            </div>
                        ` : '<p class="error">❌ No Photo URL</p>'}
                    </div>
                `;
            });
            
            resultsDiv.innerHTML = html;
        }

        // Run test when page loads
        testPhotoURLs();
    </script>
</body>
</html> 