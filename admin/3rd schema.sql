USE `Biratnagar-Collection`;

-- Table to store contact submissions
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table to store dynamic contact info shown in the 3 top boxes
CREATE TABLE IF NOT EXISTS `store_settings` (
  `setting_key` VARCHAR(50) PRIMARY KEY,
  `setting_value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default contact info values matching your UI
INSERT INTO `store_settings` (`setting_key`, `setting_value`) VALUES
('contact_phone', '+977 9812345678'),
('contact_email', 'support@biratnagarramropasal.com'),
('contact_address', 'Biratnagar, Morang, Nepal')
ON DUPLICATE KEY UPDATE `setting_key`=`setting_key`;