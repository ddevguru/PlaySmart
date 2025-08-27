-- Add referral_code column to job_applications table
ALTER TABLE `job_applications` 
ADD COLUMN `referral_code` VARCHAR(100) DEFAULT NULL AFTER `skills`;

-- Add index for better performance on referral code queries
CREATE INDEX `idx_referral_code` ON `job_applications` (`referral_code`);

-- Add comment to document the column purpose
ALTER TABLE `job_applications` 
MODIFY COLUMN `referral_code` VARCHAR(100) DEFAULT NULL COMMENT 'Referral code used during application';

-- Update existing records to have empty referral codes
UPDATE `job_applications` SET `referral_code` = '' WHERE `referral_code` IS NULL;

-- Verify the column was added
DESCRIBE `job_applications`; 