-- 0. Tạo và chọn Database
CREATE DATABASE IF NOT EXISTS `servervg` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `servervg`;

-- 1. Tạo bảng Users (Người dùng)
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('elderly','relative') NOT NULL DEFAULT 'relative',
  `private_key` varchar(50) DEFAULT NULL COMMENT 'Chỉ dành cho Elderly',
  `unique_code` varchar(20) DEFAULT NULL COMMENT 'Mã để Relative join vào gia đình',
  `avatar_url` text DEFAULT NULL,
  `device_token` text DEFAULT NULL COMMENT 'Dùng cho Push Notification',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `private_key` (`private_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tạo bảng OTP (Quên mật khẩu)
CREATE TABLE IF NOT EXISTS `otp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,z
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tạo bảng Friendships (Quan hệ bạn bè/gia đình)
CREATE TABLE IF NOT EXISTS `friendships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id_1` int(11) NOT NULL,
  `user_id_2` int(11) NOT NULL,
  `status` enum('pending','accepted','blocked') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_friendship` (`user_id_1`, `user_id_2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
