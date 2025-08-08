-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 08, 2025 at 10:15 AM
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
-- Table structure for table `nhac_nho`
--

CREATE TABLE `nhac_nho` (
  `id` int(11) NOT NULL,
  `email_nguoi_dung` varchar(255) NOT NULL,
  `private_key_nguoi_nhan` varchar(255) DEFAULT NULL COMMENT 'Private key của người cao tuổi nhận nhắc nhở',
  `ten_nguoi_dung` varchar(255) NOT NULL,
  `thoi_gian` time NOT NULL,
  `ngay_gio` datetime NOT NULL,
  `noi_dung` text NOT NULL,
  `trang_thai` enum('chua_thuc_hien','da_thuc_hien','da_huy') DEFAULT 'chua_thuc_hien',
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp(),
  `ngay_cap_nhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nhac_nho`
--

INSERT INTO `nhac_nho` (`id`, `email_nguoi_dung`, `private_key_nguoi_nhan`, `ten_nguoi_dung`, `thoi_gian`, `ngay_gio`, `noi_dung`, `trang_thai`, `ngay_tao`, `ngay_cap_nhat`) VALUES
(1, 'an.nguyen@test.com', 'abc123def', 'Nguyễn Văn An', '12:00:00', '2025-02-02 12:00:00', 'dddd: dddd', 'chua_thuc_hien', '2025-08-08 02:49:42', '2025-08-08 02:49:42'),
(2, 'ss@gmail.com', '0pu3ulfw', 'ascascasc', '12:00:00', '2025-01-01 12:00:00', 'Hihi: Hihi', 'chua_thuc_hien', '2025-08-08 08:13:12', '2025-08-08 08:13:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `nhac_nho`
--
ALTER TABLE `nhac_nho`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email_nguoi_dung`),
  ADD KEY `idx_ngay_gio` (`ngay_gio`),
  ADD KEY `idx_trang_thai` (`trang_thai`),
  ADD KEY `idx_private_key_nguoi_nhan` (`private_key_nguoi_nhan`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `nhac_nho`
--
ALTER TABLE `nhac_nho`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;