-- Jobs table for PlaySmart app
CREATE TABLE `jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(100) NOT NULL,
  `company_logo` varchar(255) DEFAULT NULL,
  `student_name` varchar(100) NOT NULL,
  `district` varchar(100) NOT NULL,
  `package` varchar(50) NOT NULL,
  `profile` varchar(200) DEFAULT NULL,
  `job_title` varchar(200) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `job_type` enum('full_time','part_time','contract','internship') DEFAULT 'full_time',
  `experience_level` varchar(50) DEFAULT NULL,
  `skills_required` text,
  `job_description` text,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company_name` (`company_name`),
  KEY `idx_student_name` (`student_name`),
  KEY `idx_district` (`district`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data for testing
INSERT INTO `jobs` (`company_name`, `company_logo`, `student_name`, `district`, `package`, `profile`, `job_title`, `location`, `job_type`, `experience_level`, `skills_required`, `job_description`) VALUES
('Google', 'google_logo.png', 'Ram Kumar Palghar', 'Mumbai', '12LPA', 'Software Engineer', 'Lead Product Manager', 'Mumbai', 'full_time', '5-8 years', 'Product Management, Analytics, Leadership', 'We are looking for a Lead Product Manager to drive product strategy and execution.'),
('Spotify', 'spotify_logo.png', 'Priya Sharma', 'Delhi', '12LPA', 'UI/UX Designer', 'Senior UI Designer', 'Mumbai', 'full_time', '3-6 years', 'UI/UX Design, Figma, Prototyping', 'Join our design team to create amazing user experiences.'),
('Microsoft', 'microsoft_logo.png', 'Amit Patel', 'Bangalore', '15LPA', 'Software Developer', 'Software Engineer', 'Bangalore', 'full_time', '2-5 years', 'Java, Spring Boot, Microservices', 'Build scalable software solutions for millions of users.'),
('Amazon', 'amazon_logo.png', 'Neha Singh', 'Hyderabad', '18LPA', 'Data Analyst', 'Data Scientist', 'Hyderabad', 'full_time', '4-7 years', 'Python, Machine Learning, SQL', 'Work on cutting-edge AI/ML projects.'),
('Netflix', 'netflix_logo.png', 'Rahul Verma', 'Mumbai', '14LPA', 'Frontend Developer', 'Frontend Developer', 'Mumbai', 'full_time', '2-4 years', 'React, JavaScript, CSS', 'Create responsive and interactive web applications.'),
('Apple', 'apple_logo.png', 'Sneha Reddy', 'Bangalore', '16LPA', 'iOS Developer', 'iOS Developer', 'Bangalore', 'full_time', '3-6 years', 'Swift, iOS SDK, Xcode', 'Develop innovative iOS applications.'),
('Meta', 'meta_logo.png', 'Vikram Kumar', 'Gurgaon', '17LPA', 'Backend Developer', 'Backend Engineer', 'Gurgaon', 'full_time', '3-5 years', 'Node.js, Python, Databases', 'Build robust backend systems.'),
('Uber', 'uber_logo.png', 'Anjali Desai', 'Mumbai', '13LPA', 'DevOps Engineer', 'DevOps Engineer', 'Mumbai', 'full_time', '2-4 years', 'Docker, Kubernetes, AWS', 'Manage cloud infrastructure and deployment.'); 