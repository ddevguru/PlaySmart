-- Add user_id column to razorpay_orders table
-- This column is required for the payment system to work

ALTER TABLE `razorpay_orders` 
ADD COLUMN `user_id` int(11) NOT NULL DEFAULT 0 AFTER `order_id`;

-- Add index for better performance
CREATE INDEX `idx_razorpay_orders_user_id` ON `razorpay_orders` (`user_id`); 