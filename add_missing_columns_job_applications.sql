-- Add missing columns to job_applications table for Razorpay integration
-- This will fix the payment capture issue

ALTER TABLE `job_applications` 
ADD COLUMN `razorpay_payment_id` VARCHAR(255) NULL COMMENT 'Razorpay payment ID after successful payment',
ADD COLUMN `razorpay_order_id` VARCHAR(255) NULL COMMENT 'Razorpay order ID for the payment';

-- Add indexes for better performance
ALTER TABLE `job_applications` 
ADD INDEX `idx_razorpay_payment_id` (`razorpay_payment_id`),
ADD INDEX `idx_razorpay_order_id` (`razorpay_order_id`);

-- Update existing records to link with existing orders (optional)
-- This will help link existing orders with applications
UPDATE `job_applications` ja
JOIN `razorpay_orders` ro ON ja.job_id = ro.job_id AND ja.user_id = ro.user_id
SET ja.razorpay_order_id = ro.order_id
WHERE ja.razorpay_order_id IS NULL AND ro.status = 'paid';

-- Show the updated table structure
DESCRIBE `job_applications`; 