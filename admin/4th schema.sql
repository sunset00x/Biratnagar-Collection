USE `Biratnagar-Collection`;

-- Payment Logs for eSewa and Khalti
CREATE TABLE IF NOT EXISTS `payment_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `transaction_id` VARCHAR(100) NOT NULL,
  `gateway` ENUM('eSewa', 'Khalti') NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `status` ENUM('PENDING', 'COMPLETE', 'FAILED') DEFAULT 'PENDING',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;