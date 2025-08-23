<?php
// Test file to check job applications API
header('Content-Type: application/json');

echo "Testing Job Applications API...\n\n";

// Test database connection
echo "1. Testing Database Connection:\n";
try {
    require_once 'db_config.php';
    $pdo = getDBConnection();
    echo "✅ Database connection successful\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

// Test if job_applications table exists
echo "\n2. Testing Table Existence:\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'job_applications'");
    if ($stmt->rowCount() > 0) {
        echo "✅ job_applications table exists\n";
    } else {
        echo "❌ job_applications table does not exist\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking table: " . $e->getMessage() . "\n";
}

// Test if table has data
echo "\n3. Testing Table Data:\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM job_applications");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Table has " . $result['count'] . " records\n";
    
    if ($result['count'] > 0) {
        $stmt = $pdo->query("SELECT * FROM job_applications LIMIT 3");
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Sample data:\n";
        foreach ($applications as $app) {
            echo "- ID: {$app['id']}, Company: {$app['company_name']}, Student: {$app['student_name']}\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking data: " . $e->getMessage() . "\n";
}

// Test the actual fetch query
echo "\n4. Testing Fetch Query:\n";
try {
    $stmt = $pdo->prepare("
        SELECT 
            id, job_id, company_name, company_logo, student_name, 
            district, package, profile, photo_path, resume_path,
            email, phone, experience, skills, payment_id,
            application_status, applied_date, is_active
        FROM job_applications 
        WHERE is_active = 1 
        ORDER BY applied_date DESC
    ");
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Fetch query successful\n";
    echo "Found " . count($applications) . " active applications\n";
    
    if (count($applications) > 0) {
        echo "First application:\n";
        $first = $applications[0];
        echo "- Company: {$first['company_name']}\n";
        echo "- Student: {$first['student_name']}\n";
        echo "- Status: {$first['application_status']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fetch query failed: " . $e->getMessage() . "\n";
}

echo "\n5. Testing JSON Response:\n";
try {
    $stmt = $pdo->prepare("
        SELECT 
            id, job_id, company_name, company_logo, student_name, 
            district, package, profile, photo_path, resume_path,
            email, phone, experience, skills, payment_id,
            application_status, applied_date, is_active
        FROM job_applications 
        WHERE is_active = 1 
        ORDER BY applied_date DESC
    ");
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = [
        'success' => true,
        'data' => $applications,
        'count' => count($applications)
    ];
    
    echo "✅ JSON response test successful\n";
    echo "Response structure:\n";
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo "❌ JSON response test failed: " . $e->getMessage() . "\n";
}

echo "\n\nTest completed!";
?> 