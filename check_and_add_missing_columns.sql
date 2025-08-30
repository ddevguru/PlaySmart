-- Check and Add Missing Columns Script
-- This script will check what columns exist and add only the missing ones

-- First, let's see what columns currently exist in job_applications table
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'job_applications' 
ORDER BY ORDINAL_POSITION;

-- Let's also see what columns exist in razorpay_orders table
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'razorpay_orders' 
ORDER BY ORDINAL_POSITION;

-- Now let's add only the missing columns to job_applications table

-- Check if payment_order_id column exists, if not add it
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'job_applications' 
     AND COLUMN_NAME = 'payment_order_id') = 0,
    'ALTER TABLE `job_applications` ADD COLUMN `payment_order_id` varchar(255) DEFAULT NULL AFTER `payment_id`',
    'SELECT "Column payment_order_id already exists" AS message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if application_fee column exists, if not add it
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'job_applications' 
     AND COLUMN_NAME = 'application_fee') = 0,
    'ALTER TABLE `job_applications` ADD COLUMN `application_fee` decimal(10,2) DEFAULT 1000.00 AFTER `payment_order_id`',
    'SELECT "Column application_fee already exists" AS message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if created_at column exists, if not add it
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'job_applications' 
     AND COLUMN_NAME = 'created_at') = 0,
    'ALTER TABLE `job_applications` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `application_version`',
    'SELECT "Column created_at already exists" AS message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if updated_at column exists, if not add it
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'job_applications' 
     AND COLUMN_NAME = 'updated_at') = 0,
    'ALTER TABLE `job_applications` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`',
    'SELECT "Column updated_at already exists" AS message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add indexes if they don't exist
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'job_applications' 
     AND INDEX_NAME = 'idx_job_applications_user_id') = 0,
    'CREATE INDEX `idx_job_applications_user_id` ON `job_applications` (`user_id`)',
    'SELECT "Index idx_job_applications_user_id already exists" AS message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'job_applications' 
     AND INDEX_NAME = 'idx_job_applications_payment_order_id') = 0,
    'CREATE INDEX `idx_job_applications_payment_order_id` ON `job_applications` (`payment_order_id`)',
    'SELECT "Index idx_job_applications_payment_order_id already exists" AS message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Show final table structure
SELECT 'Final job_applications table structure:' AS info;
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'job_applications' 
ORDER BY ORDINAL_POSITION; 