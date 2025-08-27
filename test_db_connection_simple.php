<?php
/**
 * Simple Database Connection Test - PlaySmart
 * This file tests the database connection using your credentials
 */

echo "<h2>🔍 Database Connection Test - PlaySmart</h2>";
echo "<hr>";

// Test 1: Check if db_config.php exists and load it
echo "<h3>1. Configuration File Check</h3>";
if (file_exists('db_config.php')) {
    echo "✅ db_config.php file exists<br>";
    include_once 'db_config.php';
    
    // Check if constants are defined
    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USERNAME') && defined('DB_PASSWORD')) {
        echo "✅ Database constants are defined<br>";
        echo "   Host: " . DB_HOST . "<br>";
        echo "   Database: " . DB_NAME . "<br>";
        echo "   Username: " . DB_USERNAME . "<br>";
        echo "   Password: " . (DB_PASSWORD === 'your_actual_password' ? '❌ NOT SET (placeholder)' : '✅ SET') . "<br>";
    } else {
        echo "❌ Database constants are NOT defined<br>";
    }
} else {
    echo "❌ db_config.php file NOT found<br>";
}

echo "<hr>";

// Test 2: Check if newcon.php exists and load it
echo "<h3>2. Newcon.php File Check</h3>";
if (file_exists('newcon.php')) {
    echo "✅ newcon.php file exists<br>";
    include_once 'newcon.php';
    
    // Check if constants are defined
    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USERNAME') && defined('DB_PASSWORD')) {
        echo "✅ Database constants are defined in newcon.php<br>";
        echo "   Host: " . DB_HOST . "<br>";
        echo "   Database: " . DB_NAME . "<br>";
        echo "   Username: " . DB_USERNAME . "<br>";
        echo "   Password: " . (DB_PASSWORD === 'your_actual_password' ? '❌ NOT SET (placeholder)' : '✅ SET') . "<br>";
    } else {
        echo "❌ Database constants are NOT defined in newcon.php<br>";
    }
} else {
    echo "❌ newcon.php file NOT found<br>";
}

echo "<hr>";

// Test 3: Try to connect to database
echo "<h3>3. Database Connection Test</h3>";
try {
    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USERNAME') && defined('DB_PASSWORD')) {
        if (DB_PASSWORD === 'your_actual_password') {
            echo "❌ Cannot test connection - password is still placeholder<br>";
            echo "   Please update the password in your config file<br>";
        } else {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "✅ Database connection successful!<br>";
            echo "   Connected to: " . DB_HOST . "/" . DB_NAME . "<br>";
            
            // Test a simple query
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "   Tables in database: " . $result['count'] . "<br>";
            
            // Check if required tables exist
            $requiredTables = ['jobs', 'job_applications', 'payment_tracking'];
            foreach ($requiredTables as $table) {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    echo "   ✅ Table '$table' exists<br>";
                } else {
                    echo "   ❌ Table '$table' NOT found<br>";
                }
            }
            
        }
    } else {
        echo "❌ Cannot test connection - database constants not defined<br>";
    }
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    echo "   Error code: " . $e->getCode() . "<br>";
}

echo "<hr>";

// Test 4: Show current working directory and file paths
echo "<h3>4. File System Check</h3>";
echo "Current working directory: " . getcwd() . "<br>";
echo "Script location: " . __FILE__ . "<br>";
echo "Document root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set') . "<br>";

echo "<hr>";

// Test 5: Recommendations
echo "<h3>5. Recommendations</h3>";
if (DB_PASSWORD === 'your_actual_password') {
    echo "🚨 <strong>CRITICAL:</strong> You need to update your database password!<br>";
    echo "   Current password is still the placeholder: 'your_actual_password'<br>";
    echo "   Please update the password in either db_config.php or newcon.php<br>";
    echo "   <br>";
    echo "   Example:<br>";
    echo "   <code>define('DB_PASSWORD', 'your_real_password_here');</code><br>";
} else {
    echo "✅ Database password appears to be set correctly<br>";
}

echo "<br>";
echo "📋 <strong>Next Steps:</strong><br>";
echo "1. Update your database password in the config file<br>";
echo "2. Test the connection again<br>";
echo "3. If connection works, test the payment flow<br>";
echo "4. Check if emails are being sent<br>";

echo "<hr>";
echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?> 