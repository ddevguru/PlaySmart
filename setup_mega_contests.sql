-- Setup script for Mega Contests database tables
-- Run this in your MySQL database

-- Create mega_contests table
CREATE TABLE IF NOT EXISTS `mega_contests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'mega',
  `entry_fee` decimal(10,2) NOT NULL,
  `num_players` int(11) NOT NULL DEFAULT 2,
  `num_questions` int(11) NOT NULL DEFAULT 5,
  `start_datetime` datetime NOT NULL,
  `status` enum('open','active','completed','cancelled') NOT NULL DEFAULT 'open',
  `total_winning_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_start_datetime` (`start_datetime`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create mega_contest_participants table
CREATE TABLE IF NOT EXISTS `mega_contest_participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contest_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `match_id` varchar(255) NOT NULL,
  `has_submitted` tinyint(1) NOT NULL DEFAULT 0,
  `has_viewed_results` tinyint(1) NOT NULL DEFAULT 0,
  `score` int(11) DEFAULT NULL,
  `rank` int(11) DEFAULT NULL,
  `prize_won` decimal(10,2) DEFAULT NULL,
  `joined_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `viewed_results_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_contest_user` (`contest_id`, `user_id`),
  KEY `idx_contest_id` (`contest_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_match_id` (`match_id`),
  FOREIGN KEY (`contest_id`) REFERENCES `mega_contests` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create mega_contest_rankings table
CREATE TABLE IF NOT EXISTS `mega_contest_rankings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contest_id` int(11) NOT NULL,
  `rank_start` int(11) NOT NULL,
  `rank_end` int(11) NOT NULL,
  `prize_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contest_id` (`contest_id`),
  FOREIGN KEY (`contest_id`) REFERENCES `mega_contests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create questions table (if not exists)
CREATE TABLE IF NOT EXISTS `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_text` text NOT NULL,
  `option_a` varchar(255) NOT NULL,
  `option_b` varchar(255) NOT NULL,
  `option_c` varchar(255) NOT NULL,
  `option_d` varchar(255) NOT NULL,
  `correct_answer` char(1) NOT NULL,
  `category` varchar(100) DEFAULT 'general',
  `difficulty` enum('easy','medium','hard') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_difficulty` (`difficulty`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample mega contest
INSERT INTO `mega_contests` (`name`, `type`, `entry_fee`, `num_players`, `num_questions`, `start_datetime`, `status`, `total_winning_amount`) VALUES
('Mega Contest Test', 'mega', 10.00, 2, 5, DATE_ADD(NOW(), INTERVAL 5 MINUTE), 'open', 18.00);

-- Insert sample rankings for the contest
INSERT INTO `mega_contest_rankings` (`contest_id`, `rank_start`, `rank_end`, `prize_amount`) VALUES
(LAST_INSERT_ID(), 1, 1, 18.00);

-- Insert sample questions
INSERT INTO `questions` (`question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `category`, `difficulty`) VALUES
('What is the capital of India?', 'Mumbai', 'Delhi', 'Kolkata', 'Chennai', 'B', 'geography', 'easy'),
('Which planet is known as the Red Planet?', 'Venus', 'Mars', 'Jupiter', 'Saturn', 'B', 'science', 'easy'),
('What is 2 + 2?', '3', '4', '5', '6', 'B', 'math', 'easy'),
('Who wrote Romeo and Juliet?', 'Charles Dickens', 'William Shakespeare', 'Jane Austen', 'Mark Twain', 'B', 'literature', 'medium'),
('What is the chemical symbol for gold?', 'Ag', 'Au', 'Fe', 'Cu', 'B', 'science', 'medium'); 