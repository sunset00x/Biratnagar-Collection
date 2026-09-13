USE `Biratnagar-Collection`;

-- Store conversation history for multi-turn LLM memory
CREATE TABLE IF NOT EXISTS `ai_chat_sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `session_id` VARCHAR(100) NOT NULL,
  `role` ENUM('user', 'model', 'system') NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Log customer search queries for unmet inventory demand analysis
CREATE TABLE IF NOT EXISTS `ai_search_analytics` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `query` VARCHAR(255) NOT NULL,
  `results_found` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;