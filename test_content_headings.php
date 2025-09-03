<?php
// Test Content Headings API
// This script tests if the fetch_content_headings.php endpoint is working

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Testing Content Headings API</h1>";
echo "<hr>";

// Test 1: Direct database connection
echo "<h2>1. Testing Database Connection</h2>";
try {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'u968643667_playsmart');
    define('DB_PASS', 'Playsmart@123');
    define('DB_NAME', 'u968643667_playsmart');
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    $conn->set_charset('utf8mb4');
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
    exit;
}

// Test 2: Check if content_headings table exists
echo "<h2>2. Checking Content Headings Table</h2>";
$result = $conn->query("SHOW TABLES LIKE 'content_headings'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✓ content_headings table exists</p>";
} else {
    echo "<p style='color: red;'>✗ content_headings table does not exist</p>";
    echo "<p>Please run setup_content_headings.php first</p>";
    exit;
}

// Test 3: Check table structure
echo "<h2>3. Checking Table Structure</h2>";
$result = $conn->query("DESCRIBE content_headings");
if ($result) {
    echo "<p style='color: green;'>✓ Table structure retrieved</p>";
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li><strong>{$row['Field']}</strong> - {$row['Type']}</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>✗ Failed to get table structure</p>";
}

// Test 4: Check if successful_candidates section exists
echo "<h2>4. Checking Successful Candidates Section</h2>";
$stmt = $conn->prepare("SELECT * FROM content_headings WHERE section_name = 'successful_candidates'");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo "<p style='color: green;'>✓ successful_candidates section found</p>";
    echo "<p><strong>Heading:</strong> {$data['heading_text']}</p>";
    echo "<p><strong>Sub-heading:</strong> {$data['sub_heading_text']}</p>";
    echo "<p><strong>Active:</strong> " . ($data['is_active'] ? 'Yes' : 'No') . "</p>";
} else {
    echo "<p style='color: red;'>✗ successful_candidates section not found</p>";
    echo "<p>Please run setup_content_headings.php to create it</p>";
}

$stmt->close();

// Test 5: Test the actual API endpoint
echo "<h2>5. Testing API Endpoint</h2>";
$apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/fetch_content_headings.php?section=successful_candidates';
echo "<p><strong>API URL:</strong> <a href='$apiUrl' target='_blank'>$apiUrl</a></p>";

// Test the API call
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "<p style='color: green;'>✓ API returned HTTP 200</p>";
    $data = json_decode($response, true);
    if ($data) {
        echo "<p><strong>API Response:</strong></p>";
        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
        
        if ($data['success']) {
            echo "<p style='color: green;'>✓ API returned success: true</p>";
        } else {
            echo "<p style='color: orange;'>⚠ API returned success: false</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ API response is not valid JSON</p>";
        echo "<p><strong>Raw response:</strong> $response</p>";
    }
} else {
    echo "<p style='color: red;'>✗ API returned HTTP $httpCode</p>";
    echo "<p><strong>Response:</strong> $response</p>";
}

// Test 6: Test with different section
echo "<h2>6. Testing with Different Section</h2>";
$testSection = 'test_section';
$stmt = $conn->prepare("SELECT * FROM content_headings WHERE section_name = ?");
$stmt->bind_param("s", $testSection);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✓ Test section exists</p>";
} else {
    echo "<p style='color: orange;'>⚠ Test section does not exist (this is expected)</p>";
}

$stmt->close();
$conn->close();

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>If all tests passed, your content headings API should be working correctly.</p>";
echo "<p>You can now use this API in your Flutter app to fetch dynamic headings.</p>";
echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?> 