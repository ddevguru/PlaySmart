-- Payment Tracking Table for PlaySmart Job Applications
-- This table will store all payment details and status

CREATE TABLE IF NOT EXISTS `payment_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `payment_id` varchar(255) NOT NULL,
  `razorpay_payment_id` varchar(255) DEFAULT NULL,
  `razorpay_order_id` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'INR',
  `payment_status` enum('pending','processing','completed','failed','refunded') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `gateway_response` text,
  `error_message` text,
  `refund_amount` decimal(10,2) DEFAULT 0.00,
  `refund_date` datetime DEFAULT NULL,
  `refund_reason` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_id` (`payment_id`),
  KEY `application_id` (`application_id`),
  KEY `razorpay_payment_id` (`razorpay_payment_id`),
  KEY `payment_status` (`payment_status`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_payment_application` FOREIGN KEY (`application_id`) REFERENCES `job_applications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data structure (optional)
-- INSERT INTO payment_tracking (application_id, payment_id, amount, currency, payment_status) 
-- VALUES (1, 'pay_1234567890', 2000.00, 'INR', 'pending'); 