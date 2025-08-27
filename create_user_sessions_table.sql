-- Create user_sessions table for PlaySmart session management
-- This table stores user session information for authentication

CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `user_id` (`user_id`),
  KEY `expires_at` (`expires_at`),
  KEY `is_active` (`is_active`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add last_activity column to users table if it doesn't exist
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `last_activity` timestamp NULL DEFAULT NULL AFTER `status`;

-- Create index on last_activity for better performance
CREATE INDEX IF NOT EXISTS `idx_users_last_activity` ON `users` (`last_activity`);

-- Sample data for testing (optional)
-- INSERT INTO `user_sessions` (`user_id`, `session_token`, `expires_at`) VALUES 
-- (1, 'test_token_123', DATE_ADD(NOW(), INTERVAL 24 HOUR)); 