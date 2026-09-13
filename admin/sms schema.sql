USE `Biratnagar-Collection`;

-- Insert SMS Configuration Keys into store_settings
INSERT INTO `store_settings` (`setting_key`, `setting_value`) VALUES
('sms_provider', 'aakash'),
('sms_api_token', 'YOUR_API_TOKEN_HERE'),
('sms_sender_id', 'RamroPasal'),
('sms_trigger_placed', '1'),
('sms_trigger_dispatched', '1'),
('sms_trigger_delivered', '1')
ON DUPLICATE KEY UPDATE `setting_key`=`setting_key`;

-- Table for tracking sent SMS history
CREATE TABLE IF NOT EXISTS `sms_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `phone` VARCHAR(20) NOT NULL,
  `message` TEXT NOT NULL,
  `event` VARCHAR(50) NOT NULL,
  `response_code` VARCHAR(50) DEFAULT NULL,
  `status` ENUM('SENT', 'FAILED') DEFAULT 'SENT',
  `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;