-- Update Job Applications Table to add missing columns
-- This adds user_id, payment_order_id, and other required fields

-- Simple approach: Add columns directly (will fail if they already exist, but that's OK)

-- Add user_id column
ALTER TABLE `job_applications` 
ADD COLUMN `user_id` int(11) NOT NULL DEFAULT 0 AFTER `id`;

-- Add payment_order_id column
ALTER TABLE `job_applications` 
ADD COLUMN `payment_order_id` varchar(255) DEFAULT NULL AFTER `payment_id`;

-- Add application_fee column
ALTER TABLE `job_applications` 
ADD COLUMN `application_fee` decimal(10,2) DEFAULT 1000.00 AFTER `payment_order_id`;

-- Add created_at column
ALTER TABLE `job_applications` 
ADD COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `application_version`;

-- Add updated_at column
ALTER TABLE `job_applications` 
ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Add indexes for better performance (will fail if they already exist, but that's OK)
CREATE INDEX `idx_job_applications_user_id` ON `job_applications` (`user_id`);
CREATE INDEX `idx_job_applications_payment_order_id` ON `job_applications` (`payment_order_id`);

-- Update existing records to set user_id = 1 (or appropriate default) if needed
-- UPDATE `job_applications` SET `user_id` = 1 WHERE `user_id` = 0 OR `user_id` IS NULL;

-- Add foreign key constraint to users table if it doesn't exist
-- ALTER TABLE `job_applications` ADD CONSTRAINT `fk_job_applications_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE; 