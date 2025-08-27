<?php
// Test Multiple Applications Script
// This script tests if users can submit multiple applications for the same job

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Test Multiple Applications</h1>";
echo "<hr>";

try {
    // Test data for multiple applications
    $testData = [
        'name' => 'Test User ' . date('Y-m-d H:i:s'),
        'email' => 'test.multiple@example.com',
        'phone' => '9876543210',
        'education' => 'Bachelor\'s Degree',
        'experience' => '3 years',
        'skills' => 'PHP, MySQL, JavaScript',
        'job_id' => 101,
        'referral_code' => '',
        'photo_path' => '/test/photo.png',
        'resume_path' => '/test/resume.pdf',
        'company_name' => 'Test Company',
        'package' => '8LPA',
        'profile' => 'Software Developer',
        'district' => 'Mumbai'
    ];
    
    echo "<h2>Testing Multiple Applications for Same Job</h2>";
    echo "<p>This test will submit 3 applications for the same job to verify the new functionality.</p>";
    
    $applications = [];
    
    // Submit 3 applications
    for ($i = 1; $i <= 3; $i++) {
        echo "<h3>Application $i</h3>";
        
        // Update some data to make each application slightly different
        $testData['experience'] = $i . ' years';
        $testData['skills'] = 'PHP, MySQL, JavaScript, Version ' . $i;
        $testData['photo_path'] = "/test/photo_v$i.png";
        $testData['resume_path'] = "/test/resume_v$i.pdf";
        
        echo "<p>Submitting with experience: {$testData['experience']}</p>";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://playsmart.co.in/submit_job_application_working.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $data = json_decode($response, true);
            if ($data && $data['success']) {
                echo "<p style='color: green;'>✓ Application $i submitted successfully!</p>";
                echo "<ul>";
                echo "<li>Application ID: " . $data['data']['application_id'] . "</li>";
                echo "<li>Payment ID: " . $data['data']['payment_id'] . "</li>";
                echo "<li>Version: " . $data['data']['application_version'] . "</li>";
                echo "<li>Total Applications: " . $data['data']['total_applications'] . "</li>";
                echo "</ul>";
                
                $applications[] = $data['data'];
            } else {
                echo "<p style='color: red;'>✗ Application $i failed: " . ($data['message'] ?? 'Unknown error') . "</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Application $i HTTP error: $httpCode</p>";
            echo "<p>Response: $response</p>";
        }
        
        echo "<hr>";
        
        // Small delay between submissions
        if ($i < 3) {
            sleep(1);
        }
    }
    
    // Summary
    echo "<h2>Test Summary</h2>";
    if (count($applications) == 3) {
        echo "<p style='color: green;'>🎉 SUCCESS! All 3 applications were submitted successfully!</p>";
        echo "<p>The system now allows multiple applications for the same job.</p>";
        
        echo "<h3>Application Details:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>App #</th><th>ID</th><th>Version</th><th>Payment ID</th><th>Status</th></tr>";
        foreach ($applications as $index => $app) {
            echo "<tr>";
            echo "<td>" . ($index + 1) . "</td>";
            echo "<td>" . $app['application_id'] . "</td>";
            echo "<td>" . $app['application_version'] . "</td>";
            echo "<td>" . $app['payment_id'] . "</td>";
            echo "<td>" . $app['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>✗ Test failed. Only " . count($applications) . " out of 3 applications were submitted.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Test error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>What Was Fixed</h2>";
echo "<ol>";
echo "<li><strong>Removed duplicate check:</strong> Users can now submit multiple applications for the same job</li>";
echo "<li><strong>Added version tracking:</strong> Each application gets a version number (1, 2, 3, etc.)</li>";
echo "<li><strong>Added daily limit check:</strong> Optional limit of 5 applications per day per job</li>";
echo "<li><strong>Enhanced response:</strong> Shows application version and total count</li>";
echo "</ol>";

echo "<h2>Benefits</h2>";
echo "<ul>";
echo "<li>Users can update their applications with new files or information</li>";
echo "<li>Users can submit multiple versions of their resume</li>";
echo "<li>Admin can see all versions of applications</li>";
echo "<li>System tracks application history</li>";
echo "</ul>";

echo "<hr>";
echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?> 