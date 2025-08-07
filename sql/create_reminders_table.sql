-- Tạo bảng nhắc nhở
CREATE TABLE `nhac_nho` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_nguoi_dung` varchar(255) NOT NULL,
  `ten_nguoi_dung` varchar(255) NOT NULL,
  `thoi_gian` time NOT NULL,
  `ngay_gio` datetime NOT NULL,
  `noi_dung` text NOT NULL,
  `trang_thai` enum('chua_thuc_hien','da_thuc_hien','da_huy') DEFAULT 'chua_thuc_hien',
  `ngay_tao` timestamp DEFAULT CURRENT_TIMESTAMP,
  `ngay_cap_nhat` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email_nguoi_dung`),
  KEY `idx_ngay_gio` (`ngay_gio`),
  KEY `idx_trang_thai` (`trang_thai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm comment cho bảng
ALTER TABLE `nhac_nho` COMMENT = 'Bảng lưu trữ thông tin nhắc nhở của người dùng';

-- Thêm comment cho các cột
ALTER TABLE `nhac_nho` 
MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID tự động tăng',
MODIFY COLUMN `email_nguoi_dung` varchar(255) NOT NULL COMMENT 'Email của người dùng',
MODIFY COLUMN `ten_nguoi_dung` varchar(255) NOT NULL COMMENT 'Tên của người dùng',
MODIFY COLUMN `thoi_gian` time NOT NULL COMMENT 'Thời gian nhắc nhở (HH:MM:SS)',
MODIFY COLUMN `ngay_gio` datetime NOT NULL COMMENT 'Ngày và giờ nhắc nhở',
MODIFY COLUMN `noi_dung` text NOT NULL COMMENT 'Nội dung nhắc nhở',
MODIFY COLUMN `trang_thai` enum('chua_thuc_hien','da_thuc_hien','da_huy') DEFAULT 'chua_thuc_hien' COMMENT 'Trạng thái nhắc nhở',
MODIFY COLUMN `ngay_tao` timestamp DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo nhắc nhở',
MODIFY COLUMN `ngay_cap_nhat` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật cuối cùng'; 

ALTER TABLE `nhac_nho` 
ADD COLUMN `private_key_nguoi_nhan` varchar(255) DEFAULT NULL COMMENT 'Private key của người cao tuổi nhận nhắc nhở' AFTER `email_nguoi_dung`; 