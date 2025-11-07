-- إنشاء قاعدة البيانات
CREATE DATABASE IF NOT EXISTS `perfume-db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `perfume-db`;

-- إنشاء جدول العطور
CREATE TABLE IF NOT EXISTS `perfumes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `category` enum('man','woman','unisex') NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `brand` (`brand`),
  KEY `category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج بيانات تجريبية للعطور النسائية (Woman)
INSERT INTO `perfumes` (`name`, `brand`, `category`, `price`, `image`) VALUES
('Chanel No. 5', 'chanel', 'woman', 120.00, 'perfume1.jpg'),
('Coco Mademoiselle', 'chanel', 'woman', 130.00, 'perfume2.jpg'),
('Chance Eau Tendre', 'chanel', 'woman', 115.00, 'perfume3.jpg'),
('Gabrielle Chanel', 'chanel', 'woman', 125.00, 'perfume4.jpg'),
('Chanel Allure', 'chanel', 'woman', 110.00, 'perfume5.jpg'),
('Chanel Cristalle', 'chanel', 'woman', 105.00, 'perfume6.jpg'),

('Miss Dior', 'dior', 'woman', 115.00, 'perfume7.jpg'),
('J\'adore', 'dior', 'woman', 125.00, 'perfume8.jpg'),
('Dior Poison', 'dior', 'woman', 120.00, 'perfume9.jpg'),
('Diorissimo', 'dior', 'woman', 110.00, 'perfume10.jpg'),
('Dior Addict', 'dior', 'woman', 118.00, 'perfume11.jpg'),
('Dior Joy', 'dior', 'woman', 135.00, 'perfume12.jpg'),

('Black Orchid', 'tomford', 'woman', 145.00, 'perfume1.jpg'),
('Lost Cherry', 'tomford', 'woman', 150.00, 'perfume2.jpg'),
('Velvet Orchid', 'tomford', 'woman', 140.00, 'perfume3.jpg'),
('Rose Prick', 'tomford', 'woman', 155.00, 'perfume4.jpg'),
('Bitter Peach', 'tomford', 'woman', 148.00, 'perfume5.jpg'),
('White Suede', 'tomford', 'woman', 138.00, 'perfume6.jpg');

-- إدراج بيانات تجريبية للعطور الرجالية (Man)
INSERT INTO `perfumes` (`name`, `brand`, `category`, `price`, `image`) VALUES
('Bleu de Chanel', 'chanel', 'man', 125.00, 'perfume1.jpg'),
('Allure Homme Sport', 'chanel', 'man', 120.00, 'perfume2.jpg'),
('Chanel Égoïste', 'chanel', 'man', 115.00, 'perfume3.jpg'),
('Antaeus', 'chanel', 'man', 110.00, 'perfume4.jpg'),
('Platinum Égoïste', 'chanel', 'man', 118.00, 'perfume5.jpg'),
('Pour Monsieur', 'chanel', 'man', 112.00, 'perfume6.jpg'),

('Dior Sauvage', 'dior', 'man', 130.00, 'perfume7.jpg'),
('Dior Homme', 'dior', 'man', 125.00, 'perfume8.jpg'),
('Fahrenheit', 'dior', 'man', 120.00, 'perfume9.jpg'),
('Dior Homme Intense', 'dior', 'man', 135.00, 'perfume10.jpg'),
('Eau Sauvage', 'dior', 'man', 115.00, 'perfume11.jpg'),
('Dior Homme Sport', 'dior', 'man', 122.00, 'perfume12.jpg');

-- إدراج بيانات تجريبية للعطور للجنسين (Unisex)
INSERT INTO `perfumes` (`name`, `brand`, `category`, `price`, `image`) VALUES
('Chanel Eau de Cologne', 'chanel', 'unisex', 115.00, 'perfume1.jpg'),
('Sycomore', 'chanel', 'unisex', 125.00, 'perfume2.jpg'),
('Bois des Îles', 'chanel', 'unisex', 120.00, 'perfume3.jpg'),
('Coromandel', 'chanel', 'unisex', 130.00, 'perfume4.jpg'),
('31 Rue Cambon', 'chanel', 'unisex', 122.00, 'perfume5.jpg'),
('1957', 'chanel', 'unisex', 128.00, 'perfume6.jpg');
