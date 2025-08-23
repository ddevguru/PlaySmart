<?php
// Debug file to check job applications API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Job Applications API</h2>";

// Test 1: Check if db_config.php exists
echo "<h3>1. Checking db_config.php</h3>";
if (file_exists('db_config.php')) {
    echo "✅ db_config.php exists<br>";
} else {
    echo "❌ db_config.php not found<br>";
    exit;
}

// Test 2: Check database connection
echo "<h3>2. Testing Database Connection</h3>";
try {
    require_once 'db_config.php';
    $pdo = getDBConnection();
    echo "✅ Database connection successful<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Test 3: Check if job_applications table exists
echo "<h3>3. Checking job_applications table</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'job_applications'");
    if ($stmt->rowCount() > 0) {
        echo "✅ job_applications table exists<br>";
    } else {
        echo "❌ job_applications table does not exist<br>";
        echo "Creating table...<br>";
        
        // Create the table
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS `job_applications` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `job_id` int(11) NOT NULL,
          `company_name` varchar(255) NOT NULL,
          `company_logo` varchar(255) DEFAULT NULL,
          `student_name` varchar(255) NOT NULL,
          `district` varchar(100) NOT NULL,
          `package` varchar(50) NOT NULL,
          `profile` varchar(255) DEFAULT NULL,
          `photo_path` varchar(500) DEFAULT NULL,
          `resume_path` varchar(500) DEFAULT NULL,
          `email` varchar(255) DEFAULT NULL,
          `phone` varchar(20) DEFAULT NULL,
          `experience` varchar(100) DEFAULT NULL,
          `skills` text DEFAULT NULL,
          `payment_id` varchar(255) DEFAULT NULL,
          `application_status` enum('pending','shortlisted','rejected','accepted') DEFAULT 'pending',
          `applied_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `is_active` tinyint(1) NOT NULL DEFAULT 1,
          PRIMARY KEY (`id`),
          KEY `idx_job_id` (`job_id`),
          KEY `idx_company_name` (`company_name`),
          KEY `idx_student_name` (`student_name`),
          KEY `idx_application_status` (`application_status`),
          KEY `idx_applied_date` (`applied_date`),
          KEY `idx_is_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($createTableSQL);
        echo "✅ Table created successfully<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking table: " . $e->getMessage() . "<br>";
}

// Test 4: Check table data
echo "<h3>4. Checking table data</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM job_applications");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Table has " . $result['count'] . " records<br>";
    
    if ($result['count'] == 0) {
        echo "Inserting sample data...<br>";
        
        // Insert sample data
        $sampleData = [
            [1, 'Google', 'google_logo.png', 'Rahul Sharma', 'Mumbai', '12LPA', 'Product Manager', 'uploads/photos/rahul.jpg', 'uploads/resumes/rahul.pdf', 'rahul@email.com', '+91-9876543210', '5 years', 'Product Management, Analytics', 'pay_123', 'shortlisted'],
            [2, 'Spotify', 'spotify_logo.png', 'Priya Patel', 'Delhi', '12LPA', 'UI Designer', 'uploads/photos/priya.jpg', 'uploads/resumes/priya.pdf', 'priya@email.com', '+91-9876543211', '4 years', 'UI/UX Design, Figma', 'pay_124', 'pending'],
            [3, 'Microsoft', 'microsoft_logo.png', 'Amit Kumar', 'Bangalore', '15LPA', 'Software Engineer', 'uploads/photos/amit.jpg', 'uploads/resumes/amit.pdf', 'amit@email.com', '+91-9876543212', '6 years', 'Java, Spring Boot', 'pay_125', 'accepted']
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO job_applications (
                job_id, company_name, company_logo, student_name, district, package, profile,
                photo_path, resume_path, email, phone, experience, skills, payment_id, application_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($sampleData as $data) {
            $stmt->execute($data);
        }
        
        echo "✅ Sample data inserted successfully<br>";
        
        // Check count again
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM job_applications");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Table now has " . $result['count'] . " records<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking data: " . $e->getMessage() . "<br>";
}

// Test 5: Test the actual query
echo "<h3>5. Testing the fetch query</h3>";
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
    
    echo "✅ Query executed successfully<br>";
    echo "Found " . count($applications) . " applications<br>";
    
    if (count($applications) > 0) {
        echo "<h4>Sample Applications:</h4>";
        foreach (array_slice($applications, 0, 3) as $app) {
            echo "- ID: {$app['id']}, Company: {$app['company_name']}, Student: {$app['student_name']}, Status: {$app['application_status']}<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Query failed: " . $e->getMessage() . "<br>";
}

// Test 6: Test JSON response
echo "<h3>6. Testing JSON response</h3>";
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
    
    // Process company logo URLs
    foreach ($applications as &$application) {
        if (!empty($application['company_logo'])) {
            $application['company_logo_url'] = 'https://playsmart.co.in/uploads/' . $application['company_logo'];
        } else {
            $application['company_logo_url'] = '';
        }
        unset($application['company_logo']);
    }
    
    $response = [
        'success' => true,
        'message' => 'Job applications fetched successfully',
        'data' => $applications,
        'count' => count($applications)
    ];
    
    echo "✅ JSON response test successful<br>";
    echo "<h4>Response Structure:</h4>";
    echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT) . "</pre>";
    
} catch (Exception $e) {
    echo "❌ JSON response test failed: " . $e->getMessage() . "<br>";
}

echo "<h3>✅ Debug completed!</h3>";
echo "<p>Now try accessing: <a href='fetch_job_applications.php' target='_blank'>fetch_job_applications.php</a></p>";
?> 