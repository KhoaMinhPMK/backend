-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 05, 2025 at 03:47 PM
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
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `userId` int(11) NOT NULL,
  `userName` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` enum('Nam','Nữ','Khác') DEFAULT NULL,
  `blood` varchar(10) DEFAULT NULL,
  `chronic_diseases` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `premium_status` tinyint(1) DEFAULT 0,
  `notifications` tinyint(1) DEFAULT 1,
  `relative_phone` varchar(20) DEFAULT NULL,
  `home_address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `premium_start_date` datetime DEFAULT NULL COMMENT 'Ngày bắt đầu gói Premium',
  `premium_end_date` datetime DEFAULT NULL COMMENT 'Ngày kết thúc gói Premium',
  `phone` varchar(15) DEFAULT NULL COMMENT 'Số điện thoại của user',
  `relative_name` varchar(255) DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'user',
  `private_key` varchar(255) DEFAULT NULL,
  `hypertension` tinyint(1) DEFAULT 0 COMMENT 'Hypertension status (0: No, 1: Yes)',
  `heart_disease` tinyint(1) DEFAULT 0 COMMENT 'Heart disease status (0: No, 1: Yes)',
  `ever_married` enum('Yes','No') DEFAULT 'No' COMMENT 'Whether the individual has ever been married (Yes or No)',
  `work_type` enum('Private','Self-employed','Govt_job','children','Never_worked') DEFAULT 'Private' COMMENT 'The type of work the individual is engaged in (e.g., Private, Self-employed)',
  `residence_type` enum('Urban','Rural') DEFAULT 'Urban' COMMENT 'The type of area where the individual resides (Urban or Rural)',
  `avg_glucose_level` decimal(5,2) DEFAULT NULL COMMENT 'The individual''s average glucose level (in mg/dL)',
  `bmi` decimal(4,2) DEFAULT NULL COMMENT 'The individual''s Body Mass Index (BMI)',
  `smoking_status` enum('formerly smoked','never smoked','smokes','Unknown') DEFAULT 'never smoked' COMMENT 'The smoking status of the individual (e.g., never smoked, formerly smoked, smokes)',
  `stroke` tinyint(1) DEFAULT 0 COMMENT 'Whether the individual has experienced a stroke (0: No, 1: Yes)',
  `height` decimal(5,2) DEFAULT NULL COMMENT 'Height in centimeters',
  `weight` decimal(5,2) DEFAULT NULL COMMENT 'Weight in kilograms',
  `blood_pressure_systolic` int(11) DEFAULT NULL COMMENT 'Huyết áp tâm thu (mmHg)',
  `blood_pressure_diastolic` int(11) DEFAULT NULL COMMENT 'Huyết áp tâm trương (mmHg)',
  `heart_rate` int(11) DEFAULT NULL COMMENT 'Nhịp tim (bpm)',
  `last_health_check` timestamp NULL DEFAULT NULL COMMENT 'Thời gian kiểm tra sức khỏe cuối cùng'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng thông tin người dùng với hỗ trợ gói Premium có thời hạn';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`userId`),
  ADD KEY `idx_user_premium_dates` (`premium_start_date`,`premium_end_date`),
  ADD KEY `idx_user_premium_status` (`premium_status`),
  ADD KEY `idx_user_phone` (`phone`),
  ADD KEY `idx_user_health_conditions` (`hypertension`,`heart_disease`,`stroke`),
  ADD KEY `idx_user_health_check` (`last_health_check`),
  ADD KEY `idx_user_blood_pressure` (`blood_pressure_systolic`,`blood_pressure_diastolic`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `userId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;