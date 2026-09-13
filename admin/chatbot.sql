USE `Biratnagar-Collection`;

-- Chatbot FAQ Knowledge Base Table
CREATE TABLE IF NOT EXISTS `chatbot_faq` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `keywords` VARCHAR(255) NOT NULL,
  `question` VARCHAR(255) NOT NULL,
  `answer` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Knowledge Base Data
INSERT INTO `chatbot_faq` (`keywords`, `question`, `answer`) VALUES
('delivery, shipping, area, biratnagar, time', 'Where do you deliver?', 'We deliver across all wards in Biratnagar and surrounding areas in Morang district within 24–48 hours! Orders over Rs. 2,000 get FREE delivery.'),
('payment, esewa, cod, cash', 'What payment methods are supported?', 'We accept eSewa online payments and Cash on Delivery (COD) for all orders in Biratnagar.'),
('return, refund, exchange', 'What is your return policy?', 'We offer a 7-day hassle-free return policy for damaged or defective items. Contact our support team with your order code for quick assistance.');