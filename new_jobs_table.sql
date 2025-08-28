-- New Jobs table for PlaySmart app (simplified structure)
CREATE TABLE `new_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_post` varchar(200) NOT NULL,
  `salary` varchar(100) NOT NULL,
  `education` varchar(200) NOT NULL,
  `job_type` enum('higher_job','local_job') NOT NULL DEFAULT 'higher_job',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_job_post` (`job_post`),
  KEY `idx_job_type` (`job_type`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data for testing
INSERT INTO `new_jobs` (`job_post`, `salary`, `education`, `job_type`) VALUES
('Software Engineer', '25LPA', 'B.Tech Computer Science', 'higher_job'),
('Data Analyst', '18LPA', 'B.Tech IT', 'higher_job'),
('Marketing Manager', '22LPA', 'MBA Marketing', 'higher_job'),
('Office Assistant', '25000/month', '12th Pass', 'local_job'),
('Delivery Executive', '15000/month', '10th Pass', 'local_job'),
('Sales Representative', '30000/month', 'Graduate', 'local_job'),
('Product Manager', '35LPA', 'MBA', 'higher_job'),
('Customer Service', '20000/month', '12th Pass', 'local_job'); 