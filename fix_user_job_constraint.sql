-- Fix Duplicate User-Job Constraint Issue
-- This script fixes the "Duplicate entry '0-34' for key 'user_job_unique'" error

-- Step 1: Check current state
SELECT 'Current state analysis:' AS info;
SELECT COUNT(*) as records_with_user_id_0 FROM job_applications WHERE user_id = 0;

-- Step 2: Show sample records with user_id = 0
SELECT 'Sample records with user_id = 0:' AS info;
SELECT id, job_id, email, student_name FROM job_applications WHERE user_id = 0 LIMIT 5;

-- Step 3: Find matching users for records with user_id = 0
SELECT 'Finding matching users:' AS info;
SELECT 
    ja.id as job_app_id, 
    ja.job_id, 
    ja.email, 
    ja.student_name, 
    u.id as user_id, 
    u.email as user_email
FROM job_applications ja
LEFT JOIN users u ON ja.email = u.email
WHERE ja.user_id = 0
LIMIT 10;

-- Step 4: Update records where we can find matching users
-- This will fix the duplicate constraint issue for most records
UPDATE job_applications ja
JOIN users u ON ja.email = u.email
SET ja.user_id = u.id
WHERE ja.user_id = 0 AND u.id IS NOT NULL;

-- Step 5: Check how many records were updated
SELECT 'Records updated:' AS info;
SELECT ROW_COUNT() as updated_records;

-- Step 6: Check remaining records with user_id = 0
SELECT 'Remaining records with user_id = 0:' AS info;
SELECT COUNT(*) as remaining_count FROM job_applications WHERE user_id = 0;

-- Step 7: For remaining unmatched records, create placeholder users
-- This handles cases where email exists in job_applications but not in users table
INSERT IGNORE INTO users (email, name, status, created_at)
SELECT DISTINCT ja.email, ja.student_name, 'inactive', NOW()
FROM job_applications ja
LEFT JOIN users u ON ja.email = u.email
WHERE ja.user_id = 0 AND u.id IS NULL;

-- Step 8: Update job applications with newly created placeholder users
UPDATE job_applications ja
JOIN users u ON ja.email = u.email
SET ja.user_id = u.id
WHERE ja.user_id = 0 AND u.id IS NOT NULL;

-- Step 9: Final verification - check for any remaining issues
SELECT 'Final verification:' AS info;
SELECT COUNT(*) as final_count_user_id_0 FROM job_applications WHERE user_id = 0;

-- Step 10: Check for any duplicate user_id + job_id combinations
SELECT 'Checking for duplicate combinations:' AS info;
SELECT user_id, job_id, COUNT(*) as count
FROM job_applications
WHERE user_id > 0
GROUP BY user_id, job_id
HAVING COUNT(*) > 1;

-- Step 11: Show final table structure
SELECT 'Final table structure:' AS info;
DESCRIBE job_applications;

-- Step 12: Summary
SELECT 'Summary:' AS info;
SELECT 
    'The duplicate constraint issue should now be resolved.' AS message,
    'Users can apply for jobs without encountering the "Duplicate entry" error.' AS details; 