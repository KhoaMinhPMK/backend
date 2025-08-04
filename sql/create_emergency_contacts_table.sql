-- Tạo bảng emergency_contacts để lưu trữ số điện thoại khẩn cấp
CREATE TABLE IF NOT EXISTS emergency_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_email VARCHAR(255) NOT NULL,
    emergency_number VARCHAR(20) NOT NULL,
    contact_name VARCHAR(100) DEFAULT 'Số khẩn cấp',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_emergency (user_email)
);

-- Thêm index để tối ưu truy vấn
CREATE INDEX idx_user_email ON emergency_contacts(user_email);
CREATE INDEX idx_is_active ON emergency_contacts(is_active);

-- Thêm dữ liệu mẫu (tùy chọn)
INSERT INTO emergency_contacts (user_email, emergency_number, contact_name) VALUES 
('test@example.com', '0902716951', 'Số khẩn cấp')
ON DUPLICATE KEY UPDATE 
emergency_number = VALUES(emergency_number),
contact_name = VALUES(contact_name),
updated_at = CURRENT_TIMESTAMP; 