-- Create face_data table for storing face video uploads
CREATE TABLE IF NOT EXISTS `face_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `video_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `upload_date` datetime NOT NULL,
  `status` enum('uploaded','processed','failed') DEFAULT 'uploaded',
  `processing_result` text DEFAULT NULL,
  `private_key` varchar(255) NOT NULL,
  `is_appended` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `email` (`email`),
  KEY `upload_date` (`upload_date`),
  KEY `status` (`status`),
  KEY `private_key` (`private_key`),
  KEY `is_appended` (`is_appended`),
  CONSTRAINT `face_data_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`userId`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add index for better performance
CREATE INDEX `idx_face_data_user_status` ON `face_data` (`user_id`, `status`);
CREATE INDEX `idx_face_data_upload_date` ON `face_data` (`upload_date` DESC);
CREATE INDEX `idx_face_data_private_key` ON `face_data` (`private_key`);
CREATE INDEX `idx_face_data_private_key_upload_date` ON `face_data` (`private_key`, `upload_date` DESC); 