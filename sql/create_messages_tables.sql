-- Tạo bảng conversations (cuộc trò chuyện)
CREATE TABLE IF NOT EXISTS `conversations` (
  `id` varchar(255) NOT NULL,
  `participant1_phone` varchar(20) NOT NULL,
  `participant2_phone` varchar(20) NOT NULL,
  `last_message_id` int(11) DEFAULT NULL,
  `last_activity` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_participant1` (`participant1_phone`),
  KEY `idx_participant2` (`participant2_phone`),
  KEY `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng messages (tin nhắn)
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` varchar(255) NOT NULL,
  `sender_phone` varchar(20) NOT NULL,
  `receiver_phone` varchar(20) NOT NULL,
  `message_text` text NOT NULL,
  `message_type` enum('text','image','file','voice') DEFAULT 'text',
  `file_url` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `sent_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL,
  `requires_friendship` tinyint(1) DEFAULT 1,
  `friendship_status` varchar(50) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `idx_conversation_id` (`conversation_id`),
  KEY `idx_sender_phone` (`sender_phone`),
  KEY `idx_receiver_phone` (`receiver_phone`),
  KEY `idx_sent_at` (`sent_at`),
  KEY `idx_is_read` (`is_read`),
  FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm comment cho các bảng
ALTER TABLE `conversations` COMMENT = 'Bảng lưu trữ các cuộc trò chuyện giữa 2 người dùng';
ALTER TABLE `messages` COMMENT = 'Bảng lưu trữ các tin nhắn trong cuộc trò chuyện';

-- Thêm comment cho các cột quan trọng
ALTER TABLE `conversations` MODIFY COLUMN `id` varchar(255) NOT NULL COMMENT 'ID duy nhất của cuộc trò chuyện';
ALTER TABLE `conversations` MODIFY COLUMN `participant1_phone` varchar(20) NOT NULL COMMENT 'Số điện thoại người tham gia 1';
ALTER TABLE `conversations` MODIFY COLUMN `participant2_phone` varchar(20) NOT NULL COMMENT 'Số điện thoại người tham gia 2';
ALTER TABLE `conversations` MODIFY COLUMN `last_message_id` int(11) DEFAULT NULL COMMENT 'ID tin nhắn cuối cùng';
ALTER TABLE `conversations` MODIFY COLUMN `last_activity` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian hoạt động cuối cùng';

ALTER TABLE `messages` MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID duy nhất của tin nhắn';
ALTER TABLE `messages` MODIFY COLUMN `conversation_id` varchar(255) NOT NULL COMMENT 'ID cuộc trò chuyện';
ALTER TABLE `messages` MODIFY COLUMN `sender_phone` varchar(20) NOT NULL COMMENT 'Số điện thoại người gửi';
ALTER TABLE `messages` MODIFY COLUMN `receiver_phone` varchar(20) NOT NULL COMMENT 'Số điện thoại người nhận';
ALTER TABLE `messages` MODIFY COLUMN `message_text` text NOT NULL COMMENT 'Nội dung tin nhắn';
ALTER TABLE `messages` MODIFY COLUMN `message_type` enum('text','image','file','voice') DEFAULT 'text' COMMENT 'Loại tin nhắn';
ALTER TABLE `messages` MODIFY COLUMN `is_read` tinyint(1) DEFAULT 0 COMMENT 'Trạng thái đã đọc (0: chưa đọc, 1: đã đọc)';
ALTER TABLE `messages` MODIFY COLUMN `sent_at` timestamp DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian gửi';
ALTER TABLE `messages` MODIFY COLUMN `read_at` timestamp NULL DEFAULT NULL COMMENT 'Thời gian đọc'; 