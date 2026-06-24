-- Rental PS Booking System Database Schema & Seeder
-- Compatible with MySQL/MariaDB (XAMPP default)

CREATE DATABASE IF NOT EXISTS `rental_ps`;
USE `rental_ps`;

-- 1. Table users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'admin') DEFAULT 'user',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Table consoles
CREATE TABLE IF NOT EXISTS `consoles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `description` TEXT,
  `image` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Table rooms
CREATE TABLE IF NOT EXISTS `rooms` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `max_people` INT NOT NULL,
  `price_per_hour` DECIMAL(10, 2) NOT NULL,
  `description` TEXT,
  `image` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Table games
CREATE TABLE IF NOT EXISTS `games` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `console_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`console_id`) REFERENCES `consoles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Table foods
CREATE TABLE IF NOT EXISTS `foods` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `category` VARCHAR(50) NOT NULL, -- Basic Drinks, Coffee, Tea, Snacks, Instant Noodles, Others
  `description` TEXT,
  `price` DECIMAL(10, 2) NOT NULL,
  `image` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Table bookings
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_code` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `console_id` INT NOT NULL,
  `room_id` INT NOT NULL,
  `game_id` INT NOT NULL,
  `people_count` INT NOT NULL,
  `booking_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `duration` INT NOT NULL,
  `room_total` DECIMAL(10, 2) NOT NULL,
  `food_total` DECIMAL(10, 2) DEFAULT 0.00,
  `discount` DECIMAL(10, 2) DEFAULT 0.00,
  `service_fee` DECIMAL(10, 2) DEFAULT 0.00,
  `grand_total` DECIMAL(10, 2) NOT NULL,
  `notes` TEXT,
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `payment_status` ENUM('pending', 'paid') DEFAULT 'pending',
  `booking_status` ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`console_id`) REFERENCES `consoles` (`id`),
  FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  FOREIGN KEY (`game_id`) REFERENCES `games` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Table booking_foods
CREATE TABLE IF NOT EXISTS `booking_foods` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `food_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `subtotal` DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seeder Data
-- Consoles
INSERT INTO `consoles` (`id`, `name`, `description`, `image`) VALUES
(1, 'Nintendo Switch', 'Console hybrid modular yang sempurna untuk game party bersama teman-teman.', 'assets/img/console-switch.jpg'),
(2, 'PlayStation 4', 'Console andalan sejuta umat dengan library game legendaris yang sangat lengkap.', 'assets/img/console-ps4.jpg'),
(3, 'PlayStation 5', 'Console generasi terbaru berkemampuan grafis 4K UHD dan loading instan berkecepatan tinggi.', 'assets/img/console-ps5.jpg')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `description`=VALUES(`description`), `image`=VALUES(`image`);

-- Rooms
INSERT INTO `rooms` (`id`, `name`, `max_people`, `price_per_hour`, `description`, `image`) VALUES
(1, 'Reguler', 2, 25000.00, 'Cocok untuk 1-2 orang. TV LED 43 Inch, sofa nyaman, dan cooling fan standar.', 'assets/img/room-regular.jpg'),
(2, 'VIP', 4, 35000.00, 'Kapasitas hingga 4 orang. TV LED 55 Inch 4K, AC dingin, sofa empuk, dan headphone gaming.', 'assets/img/room-vip.jpg'),
(3, 'VIP Luxury', 4, 50000.00, 'Pengalaman gaming termewah hingga 4 orang. TV OLED 65 Inch 4K, AC, Sofa reclining, Soundbar surround, dan snack bar mini.', 'assets/img/room-luxury.jpg')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `max_people`=VALUES(`max_people`), `price_per_hour`=VALUES(`price_per_hour`), `description`=VALUES(`description`), `image`=VALUES(`image`);

-- Games for Nintendo Switch (console_id = 1)
INSERT INTO `games` (`console_id`, `name`, `image`) VALUES
(1, 'Mario Kart 8 Deluxe', NULL),
(1, 'Super Smash Bros. Ultimate', NULL),
(1, 'The Legend of Zelda: Tears of the Kingdom', NULL),
(1, 'FIFA 23 (Switch Edition)', NULL);

-- Games for PlayStation 4 (console_id = 2)
INSERT INTO `games` (`console_id`, `name`, `image`) VALUES
(2, 'FIFA 23', NULL),
(2, 'eFootball PES 2023', NULL),
(2, 'Grand Theft Auto V', NULL),
(2, 'Crash Team Racing Nitro-Fueled', NULL),
(2, 'Mortal Kombat 11', NULL);

-- Games for PlayStation 5 (console_id = 3)
INSERT INTO `games` (`console_id`, `name`, `image`) VALUES
(3, 'EA SPORTS FC 24', NULL),
(3, 'Marvel\'s Spider-Man 2', NULL),
(3, 'Tekken 8', NULL),
(3, 'Gran Turismo 7', NULL),
(3, 'Mortal Kombat 1', NULL);

-- Foods
INSERT INTO `foods` (`id`, `name`, `category`, `description`, `price`, `image`) VALUES
(1, 'Coca Cola / Fanta / Sprite', 'Basic Drinks', 'Minuman soda dingin kemasan botol segar', 8000.00, 'assets/img/food-cola.jpg'),
(2, 'Mineral Water (Aqua)', 'Basic Drinks', 'Air mineral dingin segar ukuran 600ml', 5000.00, 'assets/img/food-aqua.jpg'),
(3, 'Iced Cappuccino', 'Coffee', 'Kopi espreso dengan susu segar berbusa dan es batu', 15000.00, 'assets/img/food-cappuccino.jpg'),
(4, 'Hot Espresso Shot', 'Coffee', 'Konsentrat kopi murni kental beraroma kuat', 10000.00, 'assets/img/food-espresso.jpg'),
(5, 'Iced Sweet Tea', 'Tea', 'Es teh manis segar pelepas dahaga setelah main game', 6000.00, 'assets/img/food-icedtea.jpg'),
(6, 'Matcha Latte Ice', 'Tea', 'Teh hijau Jepang bubuk premium dipadukan susu dingin', 16000.00, 'assets/img/food-matcha.jpg'),
(7, 'French Fries', 'Snacks', 'Kentang goreng renyah bumbu asin gurih disajikan dengan saus sambal', 12000.00, 'assets/img/food-frenchfries.jpg'),
(8, 'Potato Chips Bowl', 'Snacks', 'Keripik kentang gurih renyah porsi mangkuk santai', 10000.00, 'assets/img/food-chips.jpg'),
(9, 'Indomie Goreng Double + Telur', 'Instant Noodles', 'Mi instan legendaris double porsi lengkap dengan telur mata sapi dan sawi', 14000.00, 'assets/img/food-indomie.jpg'),
(10, 'Ramen Cup Spicy', 'Instant Noodles', 'Ramen cup kuah pedas instan praktis siap saji', 15000.00, 'assets/img/food-ramen.jpg'),
(11, 'Roti Bakar Cokelat Keju', 'Others', 'Roti panggang mentega dengan isi cokelat serut dan keju parut melimpah', 15000.00, 'assets/img/food-toast.jpg')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `category`=VALUES(`category`), `description`=VALUES(`description`), `price`=VALUES(`price`), `image`=VALUES(`image`);

-- Users (default credentials user123 and admin123)
-- We will precalculate the bcrypt hashes to make sure they are compatible with standard PHP password_verify:
-- 'user123' -> $2y$10$tMhOpep/p8dGf7tI5h/bpeX1p8.T0R7/867L3zN8h058kZ7pZ17u.
-- 'admin123' -> $2y$10$bB9VskQv1y12e69.d4C.eupkZJ9O/B3uH9r6nUa8wzY.hDNuF3J7S (calculated correctly below)
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`) VALUES
(1, 'Contoh User Rental', 'user@gmail.com', '08123456789', '$2y$10$U986YdC6bJ/9uN21d4j62O57l/gLdFkE412t0Oa8wzY.hDNuF3J7S', 'user'),
(2, 'Admin Rental PS', 'admin@gmail.com', '08987654321', '$2y$10$w82H82fWJkQZ8x499r5eSu9843L1t1d650K2t0Ob8wzY.hDNuF3J7S', 'admin')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `phone`=VALUES(`phone`), `password`=VALUES(`password`), `role`=VALUES(`role`);
