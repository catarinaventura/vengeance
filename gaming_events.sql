-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 13, 2026 at 12:19 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gaming_events`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'League Of Legends'),
(4, 'Marvel Rivals'),
(3, 'Overwatch 2'),
(2, 'Valorant');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `date` datetime NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL CHECK (`price` >= 0),
  `stock` int(11) NOT NULL CHECK (`stock` >= 0),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `name`, `date`, `description`, `image`, `price`, `stock`, `is_active`, `created_at`, `updated_at`, `category_id`) VALUES
(9, 'Torneio ARAM MAYHEM', '2026-02-18 18:30:00', 'Local: Campo Pequeno\r\nEquipas: FP Team, Praia dos Rapazes, MtG Lovers, Agartha', 'uploads/events/event_698a3edcea3a82.36806781.png', 15.99, 188, 1, '2026-02-09 20:09:00', '2026-02-12 23:16:11', 1),
(10, 'Torenio 6v6 Overwatch 2', '2026-02-28 15:00:00', 'Local: Pavilhão Multiusos de Vila Franca de Xira \r\nEquipas: Bolinhos fofos, Taloners, Crusaders, LMG', 'uploads/events/event_698a47dd9f93f1.36573810.png', 10.99, 96, 1, '2026-02-09 20:47:25', '2026-02-12 23:18:14', 3),
(11, 'Torneio Valorant Competitive', '2026-03-06 19:30:00', 'Local: Campo Pequeno\r\nEquipas: FP Team, Aftershock, Seraph e MtG Lovers', 'uploads/events/event_698a5b73251ae8.26115235.webp', 12.99, 185, 1, '2026-02-09 22:10:59', '2026-02-12 23:11:44', 2),
(12, 'Torneio Overwatch 2', '2026-03-13 20:30:00', 'Local: Pavilhão Multiusos de Vila Franca de Xira \r\nEquipas: FP Team, Bolinhos Fofos, Taloners, PTG', 'uploads/events/event_698a5c2a0558f6.62554177.jpg', 10.99, 84, 1, '2026-02-09 22:14:02', '2026-02-12 23:17:46', 3),
(13, 'Torneio League Of Legends SR', '2026-03-14 20:00:00', 'Local: Campo Pequeno\r\nEquipas: FP Team, LMG, T1, FTW', 'uploads/events/event_698a5dc578d976.87801844.jpg', 17.99, 191, 1, '2026-02-09 22:20:53', '2026-02-12 23:12:53', 1),
(14, 'Torneio Marvel Rivals', '2026-03-27 17:30:00', 'Local: Ateneu Artístico de Vila Franca de Xira\r\nEquipas: Valkyries, Black Rose, i-dle, Wonder Girls', 'uploads/events/event_698a5ea1d53470.83337083.webp', 13.99, 127, 1, '2026-02-09 22:24:33', '2026-02-12 23:16:33', 4);

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','cancelled','refunded') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`id`, `user_id`, `total_amount`, `status`, `created_at`) VALUES
(20, 3, 38.97, 'paid', '2026-02-12 21:18:29'),
(21, 9, 269.80, 'paid', '2026-02-12 23:11:44'),
(22, 9, 43.96, 'paid', '2026-02-12 23:11:57'),
(23, 4, 15.99, 'paid', '2026-02-12 23:12:44'),
(24, 4, 89.95, 'paid', '2026-02-12 23:12:53'),
(25, 4, 27.98, 'paid', '2026-02-12 23:13:09'),
(26, 3, 43.96, 'paid', '2026-02-12 23:13:23'),
(27, 10, 59.96, 'paid', '2026-02-12 23:14:34'),
(28, 8, 47.97, 'paid', '2026-02-12 23:16:11'),
(29, 8, 74.94, 'paid', '2026-02-12 23:16:33'),
(30, 7, 10.99, 'cancelled', '2026-02-12 23:17:38'),
(31, 7, 21.98, 'paid', '2026-02-12 23:17:46');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_purchase` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_items`
--

INSERT INTO `purchase_items` (`id`, `purchase_id`, `event_id`, `quantity`, `price_at_purchase`) VALUES
(22, 20, 11, 3, 38.97),
(23, 21, 11, 10, 129.90),
(24, 21, 14, 10, 139.90),
(25, 22, 12, 4, 43.96),
(26, 23, 9, 1, 15.99),
(27, 24, 13, 5, 89.95),
(28, 25, 14, 2, 27.98),
(29, 26, 12, 4, 43.96),
(30, 27, 14, 2, 27.98),
(31, 27, 9, 2, 31.98),
(32, 28, 9, 3, 47.97),
(33, 29, 14, 3, 41.97),
(34, 29, 12, 3, 32.97),
(35, 30, 10, 1, 10.99),
(36, 31, 12, 2, 21.98);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`) VALUES
(2, 'Admin', 'admin@admin.com', '$2y$10$cKUH.e1tGqdnF/OinbTwz.SqFPq2/pq0lNloA9z/ik59Ai3Jpl79u', 'admin'),
(3, 'GamerPT', 'gamerpt@user.com', '$2y$10$p2XZfHtCitUBlKJ0L6MCqeKLZ92gfGqsZNll87pUWmRpUxb4D2qUG', 'user'),
(4, 'YuumiPlayer', 'yuumiplayer@user.com', '$2y$10$EpqDi9iwPhWd/yx6IkzZIOsL81Sstqva6VVSQAUR/5Vuwy0XJESpS', 'user'),
(6, 'andre123', 'andre@user.com', '$2y$10$LR8BR5yxvclHsTKg3OwHjuCwbYG8RuyaDVBC0iewB8g48MAxRYFT6', 'user'),
(7, 'PickMercy', 'pickmercy@user.com', '$2y$10$w2eKVk0nJ29dKNiLVCBplOfQDRdEBwyMh.isST9zsjlWklFrgQyra', 'user'),
(8, 'FP Venturex', 'fpventurex@user.com', '$2y$10$1W09MxiMdCvVXUKJZRJg5.wIihogtQ4UB/X2kaaaUM.Hmzej71xEG', 'user'),
(9, 'ViperMain', 'vipermain@user.com', '$2y$10$QhSeby1tBRHFjK5imeQx5uS.28GGdewlOkqkgfBJXCH/kVNBuPs32', 'user'),
(10, 'Jokinhas', 'jokinhas@user.com', '$2y$10$5cbK4CnnDK0f9emvph34qeqZz27bruUhvst7aOQIHaAR1qRfUFzVC', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_events_category` (`category_id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_purchase_user` (`user_id`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_item_purchase` (`purchase_id`),
  ADD KEY `fk_purchase_event` (`event_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `fk_events_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `fk_purchase_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD CONSTRAINT `fk_item_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_purchase_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
