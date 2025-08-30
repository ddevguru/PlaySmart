-- Check Users Table Structure
-- This will show you exactly what columns exist in your users table

-- First, let's see what database we're in
SELECT DATABASE() AS current_database;

-- Show all columns in users table using DESCRIBE (works in any database)
DESCRIBE users;

-- Show sample data from users table (first 5 rows)
SELECT 'Sample users data:' AS info;
SELECT * FROM users LIMIT 5;

-- Alternative way to check table structure using SHOW
SHOW COLUMNS FROM users;

-- Show table creation SQL
SHOW CREATE TABLE users; 