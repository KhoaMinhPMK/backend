-- Tạo bảng conversations để lưu trữ các cuộc trò chuyện
CREATE TABLE IF NOT EXISTS `conversations` (
  `id` varchar(50) NOT NULL COMMENT 'ID conversation format: phone1|phone2',
  `participant1_phone` varchar(20) NOT NULL COMMENT 'Số điện thoại participant 1 (nhỏ hơn)',
  `participant2_phone` varchar(20) NOT NULL COMMENT 'Số điện thoại participant 2 (lớn hơn)',
  `last_message_id` int(11) DEFAULT NULL COMMENT 'ID của tin nhắn cuối cùng',
  `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian hoạt động cuối',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo conversation',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_participants` (`participant1_phone`, `participant2_phone`),
  KEY `idx_participant1` (`participant1_phone`),
  KEY `idx_participant2` (`participant2_phone`),
  KEY `idx_last_activity` (`last_activity`),
  KEY `idx_last_message` (`last_message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng lưu trữ các cuộc trò chuyện giữa 2 user';

-- Thêm comment cho bảng
ALTER TABLE `conversations` COMMENT = 'Bảng lưu trữ các cuộc trò chuyện giữa 2 user'; 