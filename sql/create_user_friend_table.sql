-- Tạo bảng user_friend để lưu trữ mối quan hệ bạn bè
CREATE TABLE IF NOT EXISTS `user_friend` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_phone_1` varchar(20) NOT NULL COMMENT 'Số điện thoại user 1',
  `user_phone_2` varchar(20) NOT NULL COMMENT 'Số điện thoại user 2',
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái kết bạn',
  `requester_phone` varchar(20) NOT NULL COMMENT 'Số điện thoại người gửi lời mời',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_friendship` (`user_phone_1`, `user_phone_2`),
  KEY `idx_user_phone_1` (`user_phone_1`),
  KEY `idx_user_phone_2` (`user_phone_2`),
  KEY `idx_status` (`status`),
  KEY `idx_requester` (`requester_phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng lưu trữ mối quan hệ bạn bè giữa các user';

-- Thêm comment cho bảng
ALTER TABLE `user_friend` COMMENT = 'Bảng lưu trữ mối quan hệ bạn bè giữa các user'; 