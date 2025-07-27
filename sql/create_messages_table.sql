-- Tạo bảng messages để lưu trữ tin nhắn trong conversations
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` varchar(50) NOT NULL COMMENT 'ID của conversation',
  `sender_phone` varchar(20) NOT NULL COMMENT 'Số điện thoại người gửi',
  `content` text NOT NULL COMMENT 'Nội dung tin nhắn',
  `message_type` enum('text','image','voice','file') NOT NULL DEFAULT 'text' COMMENT 'Loại tin nhắn',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Đã đọc chưa',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian gửi',
  PRIMARY KEY (`id`),
  KEY `idx_conversation_id` (`conversation_id`),
  KEY `idx_sender_phone` (`sender_phone`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_is_read` (`is_read`),
  CONSTRAINT `fk_messages_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng lưu trữ tin nhắn trong conversations';

-- Thêm comment cho bảng
ALTER TABLE `messages` COMMENT = 'Bảng lưu trữ tin nhắn trong conversations'; 