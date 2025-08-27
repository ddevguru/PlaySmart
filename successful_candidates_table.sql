-- Successful Candidates table for PlaySmart app
CREATE TABLE `successful_candidates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(100) NOT NULL,
  `candidate_name` varchar(100) NOT NULL,
  `salary` varchar(50) NOT NULL,
  `job_location` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company_name` (`company_name`),
  KEY `idx_candidate_name` (`candidate_name`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data for testing
INSERT INTO `successful_candidates` (`company_name`, `candidate_name`, `salary`, `job_location`) VALUES
('Google', 'Rahul Sharma', '25LPA', 'Mumbai'),
('Microsoft', 'Priya Patel', '18LPA', 'Bangalore'),
('Amazon', 'Amit Kumar', '22LPA', 'Hyderabad'),
('Netflix', 'Neha Singh', '20LPA', 'Mumbai'),
('Apple', 'Vikram Reddy', '28LPA', 'Bangalore'); 