-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 06, 2025 at 04:50 PM
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
-- Database: `viegrand`
--

-- --------------------------------------------------------

--
-- Table structure for table `premium_subscriptions_json`
--

CREATE TABLE `premium_subscriptions_json` (
  `premium_key` char(255) NOT NULL,
  `young_person_key` char(255) NOT NULL,
  `elderly_keys` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`elderly_keys`)),
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `note` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `premium_subscriptions_json`
--

INSERT INTO `premium_subscriptions_json` (`premium_key`, `young_person_key`, `elderly_keys`, `start_date`, `end_date`, `note`) VALUES
('0600000000010825', 'ebcc8lin', '[]', '2025-08-06 06:49:08', '2025-08-06 08:42:52', ''),
('0600000000020825', '0diyjwwt', '[]', '2025-08-06 06:56:26', '2025-09-05 06:56:26', ''),
('0600000000030825', '0diyjwwt', '[]', '2025-08-06 08:30:49', '2025-09-05 08:30:49', ''),
('0600000000040825', '0diyjwwt', '[]', '2025-08-06 08:35:32', '2025-09-05 08:35:32', 'Subscription by admin'),
('0600000000050825', 'ebcc8lin', '[]', '2025-08-06 08:44:07', '2025-08-06 08:44:14', 'Subscription by admin'),
('0600000000060825', 'pk_bb3c0', '[]', '2025-08-06 09:25:07', '2025-09-05 09:25:07', 'Subscription by admin'),
('0600000000070825', 'pk_bb3c05f58e34b9288f6fb693c4afe593_1754464533', '[]', '2025-08-06 09:26:41', '2025-08-06 09:27:12', 'Subscription by admin'),
('0600000000080825', 'pk_bb3c05f58e34b9288f6fb693c4afe593_1754464533', '[]', '2025-08-06 09:27:51', '2025-09-05 09:27:51', 'Subscription by admin'),
('0600000000090825', 'pk_beb7592569fe7d2b399d057075e289bc_1754465737', '[]', '2025-08-06 09:35:48', '2025-08-06 11:20:52', 'Subscription by admin'),
('0600000000100825', 'pk_dc599b8ace0174fee6079eb65fd6851e_1754468708', '[\"pk_ec18a8cb1bb00c550d4ca80300312fa8_1754465441\"]', '2025-08-06 10:25:08', '2025-09-05 10:25:08', 'Subscription by admin'),
('0600000000110825', 'soef931o', '[]', '2025-08-06 11:16:25', '2025-09-05 11:16:25', 'Subscription by admin'),
('0600000000120825', 'pk_beb7592569fe7d2b399d057075e289bc_1754465737', '[]', '2025-08-06 11:30:32', '2025-09-05 11:30:32', 'Subscription by admin'),
('0600000000130825', 'pk_84c6e866b08c38c23cd19a4434e83c1e_1754464551', '[]', '2025-08-06 11:31:27', '2025-09-05 11:31:27', 'Subscription by admin'),
('0600000000140825', 'li7g2sbg', '[]', '2025-08-06 15:25:28', '2025-09-06 15:25:28', ''),
('0600000000150825', 'b3ahhi1z', '[]', '2025-08-06 15:30:53', '2025-08-06 15:41:46', ''),
('0600000000160825', 'pk_73b2e', '[]', '2025-08-06 16:31:42', '2025-09-06 16:31:42', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `premium_subscriptions_json`
--
ALTER TABLE `premium_subscriptions_json`
  ADD PRIMARY KEY (`premium_key`),
  ADD KEY `idx_young_person` (`young_person_key`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;