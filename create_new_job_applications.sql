-- Create new job_applications table with correct structure
CREATE TABLE `job_applications_new` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `application_status` enum('Pending','Applied','Shortlisted','Rejected','Withdrawn') DEFAULT 'Pending',
  `payment_status` enum('Pending','Paid','Failed','Refunded') DEFAULT 'Pending',
  `payment_order_id` varchar(255) DEFAULT NULL,
  `razorpay_payment_id` varchar(255) DEFAULT NULL,
  `payment_id` varchar(255) DEFAULT NULL,
  `application_fee` decimal(10,2) DEFAULT NULL,
  `applied_at` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_job_unique` (`user_id`, `job_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_job_id` (`job_id`),
  KEY `idx_application_status` (`application_status`),
  KEY `idx_payment_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copy data from old table to new table
INSERT INTO `job_applications_new` (
  `user_id`, `job_id`, `application_status`, `payment_status`, 
  `application_fee`, `applied_at`, `created_at`
)
SELECT 
  COALESCE(u.id, 0) as user_id,
  ja.job_id,
  CASE 
    WHEN ja.application_status = 'shortlisted' THEN 'Shortlisted'
    WHEN ja.application_status = 'pending' THEN 'Pending'
    ELSE 'Pending'
  END as application_status,
  CASE 
    WHEN ja.payment_status = 'completed' THEN 'Paid'
    WHEN ja.payment_status = 'pending' THEN 'Pending'
    ELSE 'Pending'
  END as payment_status,
  ja.application_fee,
  ja.applied_date,
  COALESCE(ja.applied_date, NOW()) as created_at
FROM `job_applications` ja
LEFT JOIN `users` u ON ja.email = u.email;

-- Drop old table and rename new one
-- DROP TABLE `job_applications`;
-- ALTER TABLE `job_applications_new` RENAME TO `job_applications`; 