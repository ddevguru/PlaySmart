-- Create Payment Tracking Table
-- This table tracks all payment transactions and their status

CREATE TABLE IF NOT EXISTS `payment_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'User ID who made the payment',
  `job_id` int(11) NOT NULL COMMENT 'Job ID for which payment was made',
  `razorpay_order_id` varchar(255) NOT NULL COMMENT 'Razorpay order ID',
  `razorpay_payment_id` varchar(255) NOT NULL COMMENT 'Razorpay payment ID',
  `amount` decimal(10,2) NOT NULL COMMENT 'Payment amount in INR',
  `payment_status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending' COMMENT 'Payment status',
  `referral_code` varchar(50) DEFAULT NULL COMMENT 'Referral code used (if any)',
  `referral_bonus_paid` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether referral bonus was paid',
  `bonus_amount` decimal(10,2) DEFAULT 0.00 COMMENT 'Referral bonus amount paid',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Payment tracking record creation timestamp',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
  PRIMARY KEY (`id`),
  UNIQUE KEY `razorpay_payment_id` (`razorpay_payment_id`),
  UNIQUE KEY `razorpay_order_id` (`razorpay_order_id`),
  KEY `user_id` (`user_id`),
  KEY `job_id` (`job_id`),
  KEY `payment_status` (`payment_status`),
  KEY `referral_code` (`referral_code`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Payment tracking and referral management';

-- Add foreign key constraints if the tables exist
-- ALTER TABLE `payment_tracking` ADD CONSTRAINT `fk_payment_tracking_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
-- ALTER TABLE `payment_tracking` ADD CONSTRAINT `fk_payment_tracking_job_id` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE; 