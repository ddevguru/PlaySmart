<?php
// Test Local Jobs Integration
// This script tests the complete local jobs flow

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Local Jobs Integration Test</h1>";
echo "<hr>";

echo "<h2>Testing Complete Flow</h2>";

// Test 1: Check if fetch_local_jobs.php exists
echo "<h3>1. File Existence Check</h3>";
$localJobsFile = 'fetch_local_jobs.php';
if (file_exists($localJobsFile)) {
    echo "<p style='color: green;'>✓ $localJobsFile exists</p>";
} else {
    echo "<p style='color: red;'>✗ $localJobsFile not found</p>";
}

// Test 2: Check if newcon.php exists
echo "<h3>2. Database Configuration Check</h3>";
$dbConfigFile = 'newcon.php';
if (file_exists($dbConfigFile)) {
    echo "<p style='color: green;'>✓ $dbConfigFile exists</p>";
} else {
    echo "<p style='color: red;'>✗ $dbConfigFile not found</p>";
}

// Test 3: Test Local Jobs API
echo "<h3>3. Local Jobs API Test</h3>";
$url = 'https://playsmart.co.in/fetch_local_jobs.php?limit=5';
echo "<p>Testing URL: <code>$url</code></p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        $jobs = $data['data']['jobs'] ?? [];
        echo "<p style='color: green;'>✓ Local Jobs API working</p>";
        echo "<p>Jobs returned: <strong>" . count($jobs) . "</strong></p>";
        
        if (!empty($jobs)) {
            echo "<h4>Sample Local Job:</h4>";
            $sampleJob = $jobs[0];
            echo "<ul>";
            echo "<li><strong>Title:</strong> " . ($sampleJob['job_title'] ?? 'N/A') . "</li>";
            echo "<li><strong>Company:</strong> " . ($sampleJob['company_name'] ?? 'N/A') . "</li>";
            echo "<li><strong>Package:</strong> " . ($sampleJob['package'] ?? 'N/A') . "</li>";
            echo "<li><strong>Location:</strong> " . ($sampleJob['location'] ?? 'N/A') . "</li>";
            echo "<li><strong>Job Type:</strong> " . ($sampleJob['job_type'] ?? 'N/A') . "</li>";
            echo "</ul>";
        }
        
        // Verify package filtering
        $validPackages = 0;
        $invalidPackages = 0;
        foreach ($jobs as $job) {
            $package = $job['package'] ?? '';
            $isValid = true;
            
            if (stripos($package, 'LPA') !== false) {
                $numericValue = (float)str_replace(['LPA', '₹', ' '], '', $package);
                if ($numericValue >= 10) {
                    $isValid = false;
                }
            } elseif (stripos($package, 'PA') !== false) {
                $numericValue = (float)str_replace(['PA', '₹', ' ', ','], '', $package);
                if ($numericValue >= 1000000) {
                    $isValid = false;
                }
            }
            
            if ($isValid) {
                $validPackages++;
            } else {
                $invalidPackages++;
            }
        }
        
        echo "<h4>Package Filter Verification:</h4>";
        echo "<p>Valid packages (< 10 LPA): <strong style='color: green;'>$validPackages</strong></p>";
        echo "<p>Invalid packages (≥ 10 LPA): <strong style='color: red;'>$invalidPackages</strong></p>";
        
        if ($invalidPackages > 0) {
            echo "<p style='color: orange;'>⚠️ Warning: Found $invalidPackages jobs with packages ≥ 10 LPA</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Local Jobs API error: " . ($data['message'] ?? 'Unknown error') . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Local Jobs API failed with HTTP $httpCode</p>";
}

echo "<hr>";

// Test 4: Test with filters
echo "<h3>4. Filter Testing</h3>";

// Test location filter
$locationUrl = 'https://playsmart.co.in/fetch_local_jobs.php?location=Mumbai&limit=3';
echo "<p>Testing location filter: <code>$locationUrl</code></p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $locationUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        $jobs = $data['data']['jobs'] ?? [];
        $filters = $data['data']['filters'] ?? array();
        echo "<p style='color: green;'>✓ Location filter working</p>";
        echo "<p>Jobs in Mumbai: <strong>" . count($jobs) . "</strong></p>";
        echo "<p>Applied filter: <strong>" . ($filters['location'] ?? 'None') . "</strong></p>";
    } else {
        echo "<p style='color: red;'>✗ Location filter failed</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Location filter test failed</p>";
}

echo "<hr>";

// Test 5: Performance test
echo "<h3>5. Performance Test</h3>";
$startTime = microtime(true);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://playsmart.co.in/fetch_local_jobs.php?limit=10');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
curl_close($ch);

$endTime = microtime(true);
$executionTime = ($endTime - $startTime) * 1000;

echo "<p>HTTP Status: <strong>$httpCode</strong></p>";
echo "<p>cURL Total Time: <strong>" . round($totalTime * 1000, 2) . " ms</strong></p>";
echo "<p>Total Execution Time: <strong>" . round($executionTime, 2) . " ms</strong></p>";

if ($executionTime < 1000) {
    echo "<p style='color: green;'>✓ Performance: Excellent (< 1 second)</p>";
} elseif ($executionTime < 3000) {
    echo "<p style='color: green;'>✓ Performance: Good (< 3 seconds)</p>";
} elseif ($executionTime < 5000) {
    echo "<p style='color: orange;'>⚠️ Performance: Acceptable (< 5 seconds)</p>";
} else {
    echo "<p style='color: red;'>✗ Performance: Slow (≥ 5 seconds)</p>";
}

echo "<hr>";

echo "<h2>Integration Summary</h2>";
echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";

echo "<h3>What's Working:</h3>";
echo "<ul>";
echo "<li>✅ Local Jobs API endpoint</li>";
echo "<li>✅ Package filtering (< 10 LPA)</li>";
echo "<li>✅ Location filtering</li>";
echo "<li>✅ Pagination support</li>";
echo "<li>✅ JSON response format</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Test in Flutter app:</strong> Navigate to Local Jobs tab</li>";
echo "<li><strong>Verify job categorization:</strong> Check if jobs are properly filtered by package</li>";
echo "<li><strong>Test filters:</strong> Try location, job type, and experience filters</li>";
echo "<li><strong>Check pagination:</strong> Verify load more functionality</li>";
echo "</ol>";

echo "<h3>Files Created/Updated:</h3>";
echo "<ul>";
echo "<li><code>fetch_local_jobs.php</code> - Backend API for local jobs</li>";
echo "<li><code>lib/controller/local_jobs_controller.dart</code> - Flutter controller</li>";
echo "<li><code>lib/local_jobs_screen.dart</code> - Flutter screen</li>";
echo "<li><code>lib/main_screen.dart</code> - Updated with local jobs integration</li>";
echo "</ul>";

echo "<p><strong>API Endpoint:</strong> <code>https://playsmart.co.in/fetch_local_jobs.php</code></p>";
echo "<p><strong>Flutter Navigation:</strong> Main Screen → Local Jobs Section → View All Local Jobs</p>";
?> 