-- Create Razorpay Orders Table
-- This table stores Razorpay order details to prevent auto-refunds

CREATE TABLE IF NOT EXISTS `razorpay_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` varchar(255) NOT NULL COMMENT 'Razorpay order ID',
  `user_id` int(11) NOT NULL COMMENT 'User ID who created the order',
  `job_id` int(11) NOT NULL COMMENT 'Job ID for which order was created',
  `amount` decimal(10,2) NOT NULL COMMENT 'Order amount in INR',
  `currency` varchar(10) NOT NULL DEFAULT 'INR' COMMENT 'Currency code',
  `receipt` varchar(255) NOT NULL COMMENT 'Receipt ID for the order',
  `payment_capture` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=auto-capture, 0=manual capture',
  `status` varchar(50) NOT NULL DEFAULT 'created' COMMENT 'Order status',
  `notes` text COMMENT 'Additional notes and metadata',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Order creation timestamp',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`),
  KEY `job_id` (`job_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Razorpay orders to prevent auto-refunds';

-- Add foreign key constraint if jobs table exists
-- ALTER TABLE `razorpay_orders` ADD CONSTRAINT `fk_razorpay_orders_job_id` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE; 