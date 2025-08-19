-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 14, 2025 at 06:29 AM
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
  `last_health_check` timestamp NULL DEFAULT NULL COMMENT 'Thời gian kiểm tra sức khỏe cuối cùng',
  `restricted_contents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of keywords that this elderly user should not watchArray of keywords that this elderly user should not watch' CHECK (json_valid(`restricted_contents`)),
  `device_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng thông tin người dùng với hỗ trợ gói Premium có thời hạn';

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`userId`, `userName`, `email`, `password`, `age`, `gender`, `blood`, `chronic_diseases`, `allergies`, `premium_status`, `notifications`, `relative_phone`, `home_address`, `created_at`, `updated_at`, `premium_start_date`, `premium_end_date`, `phone`, `relative_name`, `role`, `private_key`, `hypertension`, `heart_disease`, `ever_married`, `work_type`, `residence_type`, `avg_glucose_level`, `bmi`, `smoking_status`, `stroke`, `height`, `weight`, `blood_pressure_systolic`, `blood_pressure_diastolic`, `heart_rate`, `last_health_check`, `restricted_contents`, `device_token`) VALUES
(93, 'Nguyễn Văn An', 'an.nguyen@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, '', NULL, NULL, NULL, 1, 1, NULL, NULL, '2025-08-07 07:44:16', '2025-08-07 07:55:23', NULL, NULL, '1111111111', NULL, 'elderly', 'abc123def', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(94, 'Trần Thị Bình', 'binh.tran@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 6, '', NULL, NULL, NULL, 1, 1, NULL, NULL, '2025-08-07 07:44:16', '2025-08-07 08:07:15', NULL, NULL, '1111111112', NULL, 'elderly', 'def456ghi', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(95, 'Lê Văn Cường', 'cuong.le@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 7, '', NULL, NULL, NULL, 0, 1, NULL, NULL, '2025-08-07 07:44:16', '2025-08-07 07:44:16', NULL, NULL, '1111111113', NULL, 'elderly', 'ghi789jkl', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(96, 'Phạm Thị Dung', 'dung.pham@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 8, '', NULL, NULL, NULL, 1, 1, NULL, NULL, '2025-08-07 07:44:16', '2025-08-07 07:48:52', NULL, NULL, '1111111114', NULL, 'elderly', 'jkl012mno', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(97, 'Hoàng Văn Em', 'em.hoang@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 9, '', NULL, NULL, NULL, 1, 1, NULL, NULL, '2025-08-07 07:44:16', '2025-08-07 08:24:43', NULL, NULL, '1111111115', NULL, 'elderly', 'mno345pqr', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(98, 'Vũ Thị Phương', 'phuong.vu@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 10, '', NULL, NULL, NULL, 0, 1, NULL, NULL, '2025-08-07 07:44:16', '2025-08-07 07:44:16', NULL, NULL, '1111111116', NULL, 'elderly', 'pqr678stu', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(99, 'Đặng Văn Giang', 'giang.dang@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 11, '', NULL, NULL, NULL, 0, 1, NULL, NULL, '2025-08-07 07:44:16', '2025-08-07 07:44:16', NULL, NULL, '1111111117', NULL, 'elderly', 'stu901vwx', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(100, 'Ngô Thị Hoa', 'hoa.ngo@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 12, '', NULL, NULL, NULL, 1, 1, NULL, NULL, '2025-08-07 07:44:16', '2025-08-08 04:34:08', NULL, NULL, '1111111118', NULL, 'elderly', 'vwx234yza', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(101, 'Lý Văn Ích', 'ich.ly@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 13, '', NULL, NULL, NULL, 1, 1, NULL, NULL, '2025-08-07 07:44:16', '2025-08-08 04:41:24', NULL, NULL, '1111111119', NULL, 'elderly', 'yza567bcd', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(102, 'Hồ Thị Kim', 'kim.ho@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 14, '', NULL, NULL, NULL, 0, 1, NULL, NULL, '2025-08-07 07:44:16', '2025-08-07 07:44:16', NULL, NULL, '1111111120', NULL, 'elderly', 'bcd890efg', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(103, 'Nguyễn Văn Relative1', 'relative1@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 25, '', NULL, NULL, NULL, 0, 1, NULL, NULL, '2025-08-07 07:44:16', '2025-08-07 07:44:16', NULL, NULL, '2222222221', NULL, 'relative', 'rel123abc', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(104, 'Trần Thị Relative2', 'relative2@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 30, '', NULL, NULL, NULL, 0, 1, NULL, NULL, '2025-08-07 07:44:16', '2025-08-07 07:44:16', NULL, NULL, '2222222222', NULL, 'relative', 'rel456def', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(105, 'Lê Văn Relative3', 'relative3@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 35, '', NULL, NULL, NULL, 0, 1, NULL, NULL, '2025-08-07 07:44:16', '2025-08-07 07:44:16', NULL, NULL, '2222222223', NULL, 'relative', 'rel789ghi', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(106, 'Phạm Thị Relative4', 'relative4@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 28, '', NULL, NULL, NULL, 0, 1, NULL, NULL, '2025-08-07 07:44:16', '2025-08-07 07:44:16', NULL, NULL, '2222222224', NULL, 'relative', 'rel012jkl', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(107, 'Hoàng Văn Relative5', 'relative5@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 32, '', NULL, NULL, NULL, 0, 1, NULL, NULL, '2025-08-07 07:44:16', '2025-08-07 07:44:16', NULL, NULL, '2222222225', NULL, 'relative', 'rel345mno', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(108, 'test1', 'a@gmail.com', '$2y$10$ZCmn8DR8ZdWSJFZ9EsDTkeRLb.NKr0xa8cizh4BP.YavuK.15eunS', NULL, NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, '2025-08-07 07:45:17', '2025-08-07 07:45:25', '2025-08-07 09:45:48', '2025-09-06 09:45:48', '1231231231', NULL, 'relative', 'amg77gg3', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(109, 'cdscdsc', 'b@gmail.com', '$2y$10$3qG.i4NfAcsfudUUi5xhOOINwRLUAaNW0Ul6nRP.Km5HtGiCdfloG', NULL, NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, '2025-08-07 08:03:31', '2025-08-07 08:03:40', '2025-08-07 10:04:04', '2025-09-06 10:04:04', '1112223336', NULL, 'relative', 'r4qwjvo6', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(111, 'Huy', 'z@gmail.com', '$2y$10$1EigJMJvjkzYcLuNQW7kXuqV1AkWZAcDPZ7zluXP27xla1QdhbmIi', 20, 'Nam', NULL, NULL, NULL, 1, 1, NULL, NULL, '2025-08-08 03:27:12', '2025-08-12 14:37:42', '2025-08-08 05:27:56', '2025-09-07 05:27:56', '0000465723', NULL, 'relative', 'c3upvt4o', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'fCnXrrndRmaN67J3kKAY-y:APA91bE9Skb-JHoNOinNxDVyFiF7e-OJjFEKYkFsHBTLLSFDwdnVtgtDRYWQjq38GiZCoSrhX94po5AmDtmp0Ka1uBsl0Ku_bmhTmwKTr79_LrrBoObD0NI'),
(112, 'ascascasc', 'ss@gmail.com', '$2y$10$6nFTqk6hT2PB9NsysbUOy.vzah72KmL/tY0JnMBAy8ZeRZG4zvZqG', NULL, NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, '2025-08-08 04:04:10', '2025-08-14 04:04:50', NULL, NULL, '1112223333', NULL, 'elderly', '0pu3ulfw', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, '[\"war\"]', 'fCnXrrndRmaN67J3kKAY-y:APA91bE9Skb-JHoNOinNxDVyFiF7e-OJjFEKYkFsHBTLLSFDwdnVtgtDRYWQjq38GiZCoSrhX94po5AmDtmp0Ka1uBsl0Ku_bmhTmwKTr79_LrrBoObD0NI'),
(113, 'a1', 'k@gmail.com', '$2y$10$iJEpx/iI7fD1tAVSozkIuuLYOYG4bjIeknR0hS.M0fqmXMzakmZju', NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2025-08-08 06:57:01', '2025-08-11 07:05:24', NULL, NULL, '1111111119', NULL, 'elderly', 'evc9t2w2', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'fCnXrrndRmaN67J3kKAY-y:APA91bE9Skb-JHoNOinNxDVyFiF7e-OJjFEKYkFsHBTLLSFDwdnVtgtDRYWQjq38GiZCoSrhX94po5AmDtmp0Ka1uBsl0Ku_bmhTmwKTr79_LrrBoObD0NI'),
(114, 'Uuny', 'pmkkhoaminh@gmail.com', '$2y$10$DFynK7RbgjZQJ2TUmLY1ruyucVqxCU4eBm6M6blW.jPEGfLu2fcuK', NULL, NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, '2025-08-08 07:02:41', '2025-08-11 07:48:21', '2025-08-08 11:22:54', '2025-09-07 11:22:54', '2314569870', NULL, 'relative', 'k5qz8bkh', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'dtFZEkyXSDSA4Tnwlhcdw5:APA91bG7e_OcxZmZAFZRgj06xNsSFd7PBEJUN0fEsRNQ2HhQSZ4C6jSk6dwB5Ii4IDFqG44Frka9Bk_mr4JbCW8zocSNNUgp7pzBWPB6EKSJb0osOPj5k-g'),
(116, 'Huy', 'phamquochuy131106@gmail.com', '$2y$10$IwG/9xWJkvRt7EXffM7c8OAbaWRong8FpDDNIBODf1s054YpqoBa6', NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2025-08-13 04:43:17', '2025-08-13 08:14:55', NULL, NULL, '0123456784', NULL, 'elderly', 'vc56inst', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'fCnXrrndRmaN67J3kKAY-y:APA91bE9Skb-JHoNOinNxDVyFiF7e-OJjFEKYkFsHBTLLSFDwdnVtgtDRYWQjq38GiZCoSrhX94po5AmDtmp0Ka1uBsl0Ku_bmhTmwKTr79_LrrBoObD0NI'),
(117, 'Dnxnxn', 'aa@gmail.com', '$2y$10$.hjqBu7eQyQtGP63S05oe..EJTzz4HCFwk50Iy2ChmfxWC4/XDZOm', NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2025-08-13 07:03:13', '2025-08-13 08:49:36', NULL, NULL, '1234567899', NULL, 'elderly', 'd9gf4w3n', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, 140, 88, 86, '2025-08-13 01:13:58', NULL, 'fEEqUw-xSZqtvsJhvK6RFh:APA91bEGZzqTztlKFNIzp_yCol25LgnTBlgHWWQK9BWppbkQnlgx6x_Jw-YTeVN-E2O5w3-jdlD0i2_Y6Q8YT56JCB4TKCQaUfpN3xGkCvqEhH57oyycCzQ'),
(118, 'Fbmm', 'pmkkhoaminh@gmail.com', '$2y$10$fP0A2sSLv.UwCADGWhREV.xp3iEpxy.j8bEyqiOLvg284zNhhBxuC', NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2025-08-13 08:31:11', '2025-08-13 08:31:11', NULL, NULL, '2580741369', NULL, 'elderly', 'f2k6fcpa', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'dtFZEkyXSDSA4Tnwlhcdw5:APA91bG7e_OcxZmZAFZRgj06xNsSFd7PBEJUN0fEsRNQ2HhQSZ4C6jSk6dwB5Ii4IDFqG44Frka9Bk_mr4JbCW8zocSNNUgp7pzBWPB6EKSJb0osOPj5k-g'),
(119, 'Hhjj', 'xinchao10a8@gmail.com', '$2y$10$MqLHhHQZoJw7gXs3PpIP9u5K6d2Rr0JuXjXwi0BS2ScfmRIbWIR26', NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2025-08-13 08:33:26', '2025-08-13 08:34:10', NULL, NULL, '1112223336', NULL, 'elderly', 'fmltfa1k', 0, 0, 'No', 'Private', 'Urban', NULL, NULL, 'never smoked', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'dtFZEkyXSDSA4Tnwlhcdw5:APA91bG7e_OcxZmZAFZRgj06xNsSFd7PBEJUN0fEsRNQ2HhQSZ4C6jSk6dwB5Ii4IDFqG44Frka9Bk_mr4JbCW8zocSNNUgp7pzBWPB6EKSJb0osOPj5k-g');

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
  MODIFY `userId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;