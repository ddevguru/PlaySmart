<?php
/**
 * Fix Duplicate User-Job Constraint Issue
 * 
 * This script fixes the "Duplicate entry '0-34' for key 'user_job_unique'" error
 * by updating existing job application records that have user_id = 0
 */

header('Content-Type: text/html; charset=utf-8');

try {
    // Include database configuration
    if (!file_exists('db_config.php')) {
        throw new Exception('Database configuration file not found');
    }
    
    include 'db_config.php';
    
    // Check database connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->error);
    }
    
    echo "<h2>Fixing Duplicate User-Job Constraint Issue</h2>";
    
    // First, let's see the current state
    echo "<h3>Current State Analysis:</h3>";
    
    // Check how many records have user_id = 0
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM job_applications WHERE user_id = 0");
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    echo "<p>Records with user_id = 0: <strong>$count</strong></p>";
    
    if ($count > 0) {
        // Show sample records
        $stmt = $conn->prepare("SELECT id, job_id, email, student_name FROM job_applications WHERE user_id = 0 LIMIT 5");
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo "<p>Sample records with user_id = 0:</p>";
        echo "<ul>";
        while ($row = $result->fetch_assoc()) {
            echo "<li>ID: {$row['id']}, Job ID: {$row['job_id']}, Email: {$row['email']}, Name: {$row['student_name']}</li>";
        }
        echo "</ul>";
        
        // Check if users table has matching emails
        echo "<h3>Finding Matching Users:</h3>";
        
        $stmt = $conn->prepare("
            SELECT ja.id, ja.job_id, ja.email, ja.student_name, u.id as user_id, u.email as user_email
            FROM job_applications ja
            LEFT JOIN users u ON ja.email = u.email
            WHERE ja.user_id = 0
            LIMIT 10
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo "<p>Matching users found:</p>";
        echo "<ul>";
        $matchedCount = 0;
        $unmatchedCount = 0;
        
        while ($row = $result->fetch_assoc()) {
            if ($row['user_id']) {
                echo "<li style='color: green;'>✓ Job App ID: {$row['id']} → User ID: {$row['user_id']} (Email: {$row['email']})</li>";
                $matchedCount++;
            } else {
                echo "<li style='color: red;'>✗ Job App ID: {$row['id']} → No user found for email: {$row['email']}</li>";
                $unmatchedCount++;
            }
        }
        echo "</ul>";
        
        echo "<p>Matched: <strong>$matchedCount</strong>, Unmatched: <strong>$unmatchedCount</strong></p>";
        
        // Fix the issue by updating user_id for matched records
        if ($matchedCount > 0) {
            echo "<h3>Fixing Records:</h3>";
            
            // Update records where we can find matching users
            $updateStmt = $conn->prepare("
                UPDATE job_applications ja
                JOIN users u ON ja.email = u.email
                SET ja.user_id = u.id
                WHERE ja.user_id = 0 AND u.id IS NOT NULL
            ");
            
            if ($updateStmt->execute()) {
                $affectedRows = $updateStmt->affected_rows;
                echo "<p style='color: green;'>✓ Successfully updated <strong>$affectedRows</strong> records with proper user IDs</p>";
            } else {
                echo "<p style='color: red;'>✗ Error updating records: " . $updateStmt->error . "</p>";
            }
            $updateStmt->close();
            
            // Check remaining records with user_id = 0
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM job_applications WHERE user_id = 0");
            $stmt->execute();
            $result = $stmt->get_result();
            $remainingCount = $result->fetch_assoc()['count'];
            
            echo "<p>Remaining records with user_id = 0: <strong>$remainingCount</strong></p>";
            
            if ($remainingCount > 0) {
                echo "<h3>Handling Unmatched Records:</h3>";
                echo "<p>For unmatched records, we have a few options:</p>";
                echo "<ol>";
                echo "<li><strong>Create placeholder users</strong> for each unmatched email</li>";
                echo "<li><strong>Delete unmatched records</strong> (if they're invalid)</li>";
                echo "<li><strong>Set user_id to a default value</strong> (not recommended)</li>";
                echo "</ol>";
                
                // Show unmatched records
                $stmt = $conn->prepare("
                    SELECT ja.id, ja.job_id, ja.email, ja.student_name
                    FROM job_applications ja
                    LEFT JOIN users u ON ja.email = u.email
                    WHERE ja.user_id = 0 AND u.id IS NULL
                    LIMIT 10
                ");
                $stmt->execute();
                $result = $stmt->get_result();
                
                echo "<p>Unmatched records:</p>";
                echo "<ul>";
                while ($row = $result->fetch_assoc()) {
                    echo "<li>ID: {$row['id']}, Job ID: {$row['job_id']}, Email: {$row['email']}, Name: {$row['student_name']}</li>";
                }
                echo "</ul>";
                
                // Option 1: Create placeholder users for unmatched emails
                echo "<h4>Option 1: Create Placeholder Users</h4>";
                echo "<p>This will create user accounts for unmatched emails so they can be properly linked.</p>";
                
                if (isset($_POST['create_placeholder_users'])) {
                    echo "<p>Creating placeholder users...</p>";
                    
                    $stmt = $conn->prepare("
                        SELECT DISTINCT ja.email, ja.student_name
                        FROM job_applications ja
                        LEFT JOIN users u ON ja.email = u.email
                        WHERE ja.user_id = 0 AND u.id IS NULL
                    ");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    $createdCount = 0;
                    while ($row = $result->fetch_assoc()) {
                        $email = $row['email'];
                        $name = $row['student_name'];
                        
                        // Create placeholder user
                        $insertStmt = $conn->prepare("
                            INSERT INTO users (email, name, status, created_at) 
                            VALUES (?, ?, 'inactive', NOW())
                        ");
                        $insertStmt->bind_param("ss", $email, $name);
                        
                        if ($insertStmt->execute()) {
                            $userId = $insertStmt->insert_id;
                            
                            // Update job application with new user ID
                            $updateStmt = $conn->prepare("
                                UPDATE job_applications 
                                SET user_id = ? 
                                WHERE email = ? AND user_id = 0
                            ");
                            $updateStmt->bind_param("is", $userId, $email);
                            
                            if ($updateStmt->execute()) {
                                $createdCount++;
                                echo "<p style='color: green;'>✓ Created user for $email and updated job applications</p>";
                            }
                            $updateStmt->close();
                        }
                        $insertStmt->close();
                    }
                    
                    echo "<p style='color: green;'>✓ Created <strong>$createdCount</strong> placeholder users</p>";
                    
                    // Check final count
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM job_applications WHERE user_id = 0");
                    $stmt->execute();
                    $result = $stmt->fetch_assoc()['count'];
                    echo "<p>Final count of records with user_id = 0: <strong>$count</strong></p>";
                } else {
                    echo "<form method='post'>";
                    echo "<input type='submit' name='create_placeholder_users' value='Create Placeholder Users' style='background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
                    echo "</form>";
                }
            }
        }
    } else {
        echo "<p style='color: green;'>✓ No records with user_id = 0 found. The issue is already resolved!</p>";
    }
    
    // Final verification
    echo "<h3>Final Verification:</h3>";
    
    // Check if there are any duplicate user_id + job_id combinations
    $stmt = $conn->prepare("
        SELECT user_id, job_id, COUNT(*) as count
        FROM job_applications
        WHERE user_id > 0
        GROUP BY user_id, job_id
        HAVING COUNT(*) > 1
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<p style='color: red;'>⚠ Found duplicate user_id + job_id combinations:</p>";
        echo "<ul>";
        while ($row = $result->fetch_assoc()) {
            echo "<li>User ID: {$row['user_id']}, Job ID: {$row['job_id']}, Count: {$row['count']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: green;'>✓ No duplicate user_id + job_id combinations found</p>";
    }
    
    echo "<h3>Summary:</h3>";
    echo "<p>The duplicate constraint issue should now be resolved. Users can apply for jobs without encountering the 'Duplicate entry' error.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: <pre>" . $e->getTraceAsString() . "</pre></p>";
}

if (isset($conn)) {
    $conn->close();
}
?> 