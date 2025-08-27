<?php
// Test Local Jobs API
// This script tests the fetch_local_jobs.php endpoint

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Local Jobs API Test</h1>";
echo "<hr>";

// Test 1: Basic fetch without filters
echo "<h2>1. Testing Basic Local Jobs Fetch</h2>";
$url = 'https://playsmart.co.in/fetch_local_jobs.php';
echo "<p>Testing URL: <code>$url</code></p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Status Code: <strong>$httpCode</strong></p>";

if ($httpCode == 200) {
    echo "<p style='color: green;'>✓ API responded successfully</p>";
    
    $data = json_decode($response, true);
    if ($data && isset($data['success'])) {
        if ($data['success']) {
            echo "<p style='color: green;'>✓ API returned success response</p>";
            
            $jobs = $data['data']['jobs'] ?? [];
            $pagination = $data['data']['pagination'] ?? [];
            $filters = $data['data']['filters'] ?? [];
            
            echo "<p>Jobs found: <strong>" . count($jobs) . "</strong></p>";
            echo "<p>Total jobs: <strong>" . ($pagination['total_jobs'] ?? 'N/A') . "</strong></p>";
            echo "<p>Current page: <strong>" . ($pagination['current_page'] ?? 'N/A') . "</strong></p>";
            
            if (!empty($jobs)) {
                echo "<h3>Sample Job Data:</h3>";
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr><th>Field</th><th>Value</th></tr>";
                
                $sampleJob = $jobs[0];
                foreach ($sampleJob as $key => $value) {
                    if (is_array($value)) {
                        $displayValue = json_encode($value);
                    } else {
                        $displayValue = $value ?? 'null';
                    }
                    echo "<tr>";
                    echo "<td><strong>$key</strong></td>";
                    echo "<td>" . htmlspecialchars($displayValue) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
        } else {
            echo "<p style='color: red;'>✗ API returned error: " . ($data['message'] ?? 'Unknown error') . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Invalid JSON response</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }
} else {
    echo "<p style='color: red;'>✗ API request failed with HTTP $httpCode</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

echo "<hr>";

// Test 2: Fetch with pagination
echo "<h2>2. Testing Pagination</h2>";
$url = 'https://playsmart.co.in/fetch_local_jobs.php?page=2&limit=5';
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
        $pagination = $data['data']['pagination'] ?? array();
        echo "<p style='color: green;'>✓ Pagination test successful</p>";
        echo "<p>Page 2 results: <strong>" . count($data['data']['jobs']) . "</strong> jobs</p>";
        echo "<p>Has next page: <strong>" . ($pagination['has_next'] ? 'Yes' : 'No') . "</strong></p>";
        echo "<p>Has previous page: <strong>" . ($pagination['has_prev'] ? 'Yes' : 'No') . "</strong></p>";
    } else {
        echo "<p style='color: red;'>✗ Pagination test failed: " . ($data['message'] ?? 'Unknown error') . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Pagination test failed with HTTP $httpCode</p>";
}

echo "<hr>";

// Test 3: Fetch with location filter
echo "<h2>3. Testing Location Filter</h2>";
$url = 'https://playsmart.co.in/fetch_local_jobs.php?location=Mumbai';
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
        $filters = $data['data']['filters'] ?? array();
        echo "<p style='color: green;'>✓ Location filter test successful</p>";
        echo "<p>Jobs found in Mumbai: <strong>" . count($jobs) . "</strong></p>";
        echo "<p>Applied filter: <strong>" . ($filters['location'] ?? 'None') . "</strong></p>";
        
        // Check if all jobs are actually in Mumbai
        $mumbaiJobs = 0;
        foreach ($jobs as $job) {
            if (stripos($job['location'] ?? '', 'Mumbai') !== false) {
                $mumbaiJobs++;
            }
        }
        echo "<p>Jobs actually in Mumbai: <strong>$mumbaiJobs</strong></p>";
        
    } else {
        echo "<p style='color: red;'>✗ Location filter test failed: " . ($data['message'] ?? 'Unknown error') . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Location filter test failed with HTTP $httpCode</p>";
}

echo "<hr>";

// Test 4: Fetch with job type filter
echo "<h2>4. Testing Job Type Filter</h2>";
$url = 'https://playsmart.co.in/fetch_local_jobs.php?job_type=Full-time';
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
        $filters = $data['data']['filters'] ?? array();
        echo "<p style='color: green;'>✓ Job type filter test successful</p>";
        echo "<p>Full-time jobs found: <strong>" . count($jobs) . "</strong></p>";
        echo "<p>Applied filter: <strong>" . ($filters['job_type'] ?? 'None') . "</strong></p>";
        
    } else {
        echo "<p style='color: red;'>✗ Job type filter test failed: " . ($data['message'] ?? 'Unknown error') . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Job type filter test failed with HTTP $httpCode</p>";
}

echo "<hr>";

// Test 5: Verify package filtering (should be < 10 LPA)
echo "<h2>5. Verifying Package Filter (Local Jobs < 10 LPA)</h2>";
$url = 'https://playsmart.co.in/fetch_local_jobs.php?limit=50';
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
        echo "<p style='color: green;'>✓ Package filter verification successful</p>";
        echo "<p>Total jobs fetched: <strong>" . count($jobs) . "</strong></p>";
        
        // Check package values
        $validPackages = 0;
        $invalidPackages = 0;
        $packageDetails = [];
        
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
                if ($numericValue >= 1000000) { // 10 LPA = 10,00,000 PA
                    $isValid = false;
                }
            }
            
            if ($isValid) {
                $validPackages++;
            } else {
                $invalidPackages++;
                $packageDetails[] = $package;
            }
        }
        
        echo "<p>Valid packages (< 10 LPA): <strong style='color: green;'>$validPackages</strong></p>";
        echo "<p>Invalid packages (≥ 10 LPA): <strong style='color: red;'>$invalidPackages</strong></p>";
        
        if ($invalidPackages > 0) {
            echo "<p style='color: orange;'>⚠️ Found $invalidPackages jobs with packages ≥ 10 LPA:</p>";
            echo "<ul>";
            foreach (array_slice($packageDetails, 0, 10) as $package) { // Show first 10
                echo "<li>$package</li>";
            }
            if (count($packageDetails) > 10) {
                echo "<li>... and " . (count($packageDetails) - 10) . " more</li>";
            }
            echo "</ul>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Package filter verification failed: " . ($data['message'] ?? 'Unknown error') . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Package filter verification failed with HTTP $httpCode</p>";
}

echo "<hr>";

// Test 6: Performance test
echo "<h2>6. Performance Test</h2>";
$startTime = microtime(true);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://playsmart.co.in/fetch_local_jobs.php?limit=20');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
curl_close($ch);

$endTime = microtime(true);
$executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

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
echo "<h2>Test Summary</h2>";
echo "<p><em>Local Jobs API testing completed at: " . date('Y-m-d H:i:s') . "</em></p>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Test in Flutter app:</strong> Use the LocalJobsController to fetch jobs</li>";
echo "<li><strong>Verify package filtering:</strong> Ensure only jobs < 10 LPA are returned</li>";
echo "<li><strong>Test filters:</strong> Location, job type, and experience filters</li>";
echo "<li><strong>Check pagination:</strong> Verify load more functionality works</li>";
echo "</ol>";

echo "<p><strong>API Endpoint:</strong> <code>https://playsmart.co.in/fetch_local_jobs.php</code></p>";
echo "<p><strong>Flutter Controller:</strong> <code>lib/controller/local_jobs_controller.dart</code></p>";
echo "<p><strong>Flutter Screen:</strong> <code>lib/local_jobs_screen.dart</code></p>";
?> 