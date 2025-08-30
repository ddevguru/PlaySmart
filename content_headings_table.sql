CREATE TABLE `content_headings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section_name` varchar(100) NOT NULL,
  `heading_text` varchar(255) NOT NULL,
  `sub_heading_text` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `section_name` (`section_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert the heading
INSERT INTO `content_headings` (`section_name`, `heading_text`, `sub_heading_text`) VALUES
('successful_candidates', 'Our Successfully Placed', 'Candidates'); 