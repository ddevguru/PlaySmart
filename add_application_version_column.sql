-- Add Application Version Column to job_applications table
-- This allows users to submit multiple applications for the same job

-- Add application_version column if it doesn't exist
ALTER TABLE `job_applications` 
ADD COLUMN IF NOT EXISTS `application_version` int(11) NOT NULL DEFAULT 1 AFTER `is_active`;

-- Add index for better performance when checking versions
CREATE INDEX IF NOT EXISTS `idx_job_applications_version` 
ON `job_applications` (`email`, `job_id`, `application_version`);

-- Update existing records to have version 1
UPDATE `job_applications` 
SET `application_version` = 1 
WHERE `application_version` IS NULL OR `application_version` = 0;

-- Show the updated table structure
DESCRIBE `job_applications`;

-- Show sample data with versions
SELECT 
    id,
    email,
    job_id,
    application_version,
    applied_date,
    application_status
FROM `job_applications` 
ORDER BY email, job_id, application_version 
LIMIT 10; 