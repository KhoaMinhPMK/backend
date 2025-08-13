-- Create OTP table for password reset functionality
CREATE TABLE IF NOT EXISTS `password_reset_otp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `used_at` datetime NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_otp` (`otp`),
  KEY `idx_expires_at` (`expires_at`),
  KEY `idx_used` (`used`),
  CONSTRAINT `fk_password_reset_otp_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`userId`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add index for better performance
CREATE INDEX `idx_user_otp_expires` ON `password_reset_otp` (`user_id`, `otp`, `expires_at`);

-- Clean up expired OTPs (optional: create a scheduled event)
-- This can be run periodically to clean up expired OTPs
-- DELETE FROM password_reset_otp WHERE expires_at < NOW() AND used = 0; 