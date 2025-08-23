-- Featured Content table for PlaySmart app
CREATE TABLE `featured_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text,
  `image_url` varchar(255) DEFAULT NULL,
  `action_text` varchar(100) NOT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data for testing
INSERT INTO `featured_content` (`title`, `description`, `image_url`, `action_text`, `action_url`, `is_active`) VALUES
('Special Offer! 🎉', 'Get 50% off on all premium contests this week. Limited time offer!', 'special_offer.png', 'Claim Now', 'https://playsmart.co.in/offers', 1),
('New Features', 'Discover our latest quiz features and win more prizes!', 'new_features.png', 'Explore', 'https://playsmart.co.in/features', 1),
('Referral Bonus', 'Invite friends and earn bonus rewards. Share the fun!', 'referral_bonus.png', 'Invite Friends', 'https://playsmart.co.in/refer', 1),
('Daily Challenge', 'Complete daily challenges and earn extra points!', 'daily_challenge.png', 'Start Challenge', 'https://playsmart.co.in/challenge', 1); 