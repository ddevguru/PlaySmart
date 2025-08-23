-- Job Applications Table
-- This table stores information about students who have applied for jobs

CREATE TABLE IF NOT EXISTS `job_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `company_logo` varchar(255) DEFAULT NULL,
  `student_name` varchar(255) NOT NULL,
  `district` varchar(100) NOT NULL,
  `package` varchar(50) NOT NULL,
  `profile` varchar(255) DEFAULT NULL,
  `photo_path` varchar(500) DEFAULT NULL,
  `resume_path` varchar(500) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `experience` varchar(100) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `payment_id` varchar(255) DEFAULT NULL,
  `application_status` enum('pending','shortlisted','rejected','accepted') DEFAULT 'pending',
  `applied_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  KEY `company_name` (`company_name`),
  KEY `student_name` (`student_name`),
  KEY `application_status` (`application_status`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data for job applications
INSERT INTO `job_applications` (`job_id`, `company_name`, `company_logo`, `student_name`, `district`, `package`, `profile`, `photo_path`, `resume_path`, `email`, `phone`, `experience`, `skills`, `payment_id`, `application_status`, `applied_date`, `is_active`) VALUES
(1, 'Google', 'google_logo.png', 'Rahul Sharma', 'Mumbai', '12LPA', 'Product Manager', 'uploads/photos/rahul_sharma.jpg', 'uploads/resumes/rahul_sharma_resume.pdf', 'rahul.sharma@email.com', '+91-9876543210', '5 years', 'Product Management, Analytics, Leadership', 'pay_123456789', 'shortlisted', '2025-08-21 10:30:00', 1),
(1, 'Google', 'google_logo.png', 'Priya Patel', 'Pune', '12LPA', 'Product Manager', 'uploads/photos/priya_patel.jpg', 'uploads/resumes/priya_patel_resume.pdf', 'priya.patel@email.com', '+91-9876543211', '4 years', 'Product Strategy, User Research, Data Analysis', 'pay_123456790', 'pending', '2025-08-21 11:15:00', 1),
(2, 'Spotify', 'spotify_logo.png', 'Amit Kumar', 'Delhi', '12LPA', 'UI Designer', 'uploads/photos/amit_kumar.jpg', 'uploads/resumes/amit_kumar_resume.pdf', 'amit.kumar@email.com', '+91-9876543212', '6 years', 'UI/UX Design, Figma, Prototyping', 'pay_123456791', 'accepted', '2025-08-21 09:45:00', 1),
(2, 'Spotify', 'spotify_logo.png', 'Neha Singh', 'Mumbai', '12LPA', 'UI Designer', 'uploads/photos/neha_singh.jpg', 'uploads/resumes/neha_singh_resume.pdf', 'neha.singh@email.com', '+91-9876543213', '3 years', 'Visual Design, Adobe Creative Suite', 'pay_123456792', 'rejected', '2025-08-21 14:20:00', 1),
(3, 'Microsoft', 'microsoft_logo.png', 'Vikram Verma', 'Bangalore', '15LPA', 'Software Engineer', 'uploads/photos/vikram_verma.jpg', 'uploads/resumes/vikram_verma_resume.pdf', 'vikram.verma@email.com', '+91-9876543214', '7 years', 'Java, Spring Boot, Microservices', 'pay_123456793', 'shortlisted', '2025-08-21 13:10:00', 1),
(3, 'Microsoft', 'microsoft_logo.png', 'Sneha Reddy', 'Hyderabad', '15LPA', 'Software Engineer', 'uploads/photos/sneha_reddy.jpg', 'uploads/resumes/sneha_reddy_resume.pdf', 'sneha.reddy@email.com', '+91-9876543215', '4 years', 'Python, Django, React', 'pay_123456794', 'pending', '2025-08-21 16:30:00', 1),
(4, 'Amazon', 'amazon_logo.png', 'Rajesh Kumar', 'Chennai', '18LPA', 'Data Scientist', 'uploads/photos/rajesh_kumar.jpg', 'uploads/resumes/rajesh_kumar_resume.pdf', 'rajesh.kumar@email.com', '+91-9876543216', '8 years', 'Python, Machine Learning, SQL', 'pay_123456795', 'accepted', '2025-08-21 08:15:00', 1),
(5, 'Netflix', 'netflix_logo.png', 'Anjali Desai', 'Mumbai', '14LPA', 'Frontend Developer', 'uploads/photos/anjali_desai.jpg', 'uploads/resumes/anjali_desai_resume.pdf', 'anjali.desai@email.com', '+91-9876543217', '5 years', 'React, JavaScript, CSS', 'pay_123456796', 'shortlisted', '2025-08-21 12:45:00', 1),
(6, 'Apple', 'apple_logo.png', 'Karan Malhotra', 'Gurgaon', '16LPA', 'iOS Developer', 'uploads/photos/karan_malhotra.jpg', 'uploads/resumes/karan_malhotra_resume.pdf', 'karan.malhotra@email.com', '+91-9876543218', '6 years', 'Swift, iOS SDK, Xcode', 'pay_123456797', 'pending', '2025-08-21 15:20:00', 1),
(7, 'Meta', 'meta_logo.png', 'Divya Sharma', 'Pune', '17LPA', 'Backend Engineer', 'uploads/photos/divya_sharma.jpg', 'uploads/resumes/divya_sharma_resume.pdf', 'divya.sharma@email.com', '+91-9876543219', '7 years', 'Node.js, Python, Databases', 'pay_123456798', 'shortlisted', '2025-08-21 10:00:00', 1),
(8, 'Uber', 'uber_logo.png', 'Ravi Singh', 'Delhi', '13LPA', 'DevOps Engineer', 'uploads/photos/ravi_singh.jpg', 'uploads/resumes/ravi_singh_resume.pdf', 'ravi.singh@email.com', '+91-9876543220', '5 years', 'Docker, Kubernetes, AWS', 'pay_123456799', 'pending', '2025-08-21 17:30:00', 1);

-- Add foreign key constraint (optional - uncomment if you want referential integrity)
-- ALTER TABLE `job_applications` 
-- ADD CONSTRAINT `fk_job_applications_job_id` 
-- FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;

-- Indexes for better performance
CREATE INDEX `idx_job_applications_company` ON `job_applications` (`company_name`);
CREATE INDEX `idx_job_applications_student` ON `job_applications` (`student_name`);
CREATE INDEX `idx_job_applications_status` ON `job_applications` (`application_status`);
CREATE INDEX `idx_job_applications_date` ON `job_applications` (`applied_date`);
CREATE INDEX `idx_job_applications_active` ON `job_applications` (`is_active`); 