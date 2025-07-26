-- ĐỀ XUẤT: Thiết kế tối ưu cho hệ thống bạn bè
-- Thiết kế này linh hoạt hơn, dễ quản lý và mở rộng

-- Bảng lưu thông tin chi tiết của contact/friend
CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    avatar_url VARCHAR(255) DEFAULT NULL,
    relationship VARCHAR(50) DEFAULT NULL, -- 'con gái', 'con trai', 'vợ/chồng', 'bạn bè', etc.
    is_emergency_contact BOOLEAN DEFAULT FALSE,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Index để tối ưu hóa tìm kiếm
    INDEX idx_phone (phone),
    INDEX idx_name (name),
    
    -- Đảm bảo phone là duy nhất
    UNIQUE KEY unique_phone (phone)
);

-- Bảng quan hệ giữa user và contact (many-to-many relationship)
CREATE TABLE user_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_phone VARCHAR(20) NOT NULL,
    contact_id INT NOT NULL,
    is_favorite BOOLEAN DEFAULT FALSE,
    contact_order INT DEFAULT 0, -- Thứ tự hiển thị
    blocked BOOLEAN DEFAULT FALSE,
    last_contacted TIMESTAMP NULL,
    contact_frequency INT DEFAULT 0, -- Số lần liên lạc
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    
    -- Index để tối ưu hóa truy vấn
    INDEX idx_user_phone (user_phone),
    INDEX idx_contact_id (contact_id),
    INDEX idx_user_contact (user_phone, contact_id),
    INDEX idx_favorite (user_phone, is_favorite),
    INDEX idx_order (user_phone, contact_order),
    
    -- Đảm bảo một user không thể có duplicate contact
    UNIQUE KEY unique_user_contact (user_phone, contact_id)
);

-- Bảng lưu lịch sử tin nhắn (để hỗ trợ chat)
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id VARCHAR(100) NOT NULL,
    sender_phone VARCHAR(20) NOT NULL,
    receiver_phone VARCHAR(20) NOT NULL,
    message_text TEXT NOT NULL,
    message_type ENUM('text', 'image', 'voice', 'video') DEFAULT 'text',
    file_url VARCHAR(255) DEFAULT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    
    -- Index để tối ưu hóa truy vấn
    INDEX idx_conversation (conversation_id),
    INDEX idx_sender (sender_phone),
    INDEX idx_receiver (receiver_phone),
    INDEX idx_sent_at (sent_at),
    INDEX idx_unread (receiver_phone, is_read)
);

-- Bảng lưu thông tin cuộc hội thoại
CREATE TABLE conversations (
    id VARCHAR(100) PRIMARY KEY,
    participant1_phone VARCHAR(20) NOT NULL,
    participant2_phone VARCHAR(20) NOT NULL,
    last_message_id INT DEFAULT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key
    FOREIGN KEY (last_message_id) REFERENCES messages(id) ON DELETE SET NULL,
    
    -- Index
    INDEX idx_participant1 (participant1_phone),
    INDEX idx_participant2 (participant2_phone),
    INDEX idx_last_activity (last_activity),
    
    -- Đảm bảo không có conversation trùng lặp
    UNIQUE KEY unique_participants (participant1_phone, participant2_phone)
);

-- Insert sample data
INSERT INTO contacts (name, phone, relationship, is_emergency_contact) VALUES
('Ngọc Anh', '0987654321', 'con gái', TRUE),
('Minh Tuấn', '0123456789', 'con trai', TRUE),
('Lan Hương', '0345678901', 'bạn bè', FALSE);

-- Sample user_contacts relationship
INSERT INTO user_contacts (user_phone, contact_id, is_favorite, contact_order) VALUES
('0901234567', 1, TRUE, 1),
('0901234567', 2, TRUE, 2),
('0901234567', 3, FALSE, 3);

-- Comments cho các bảng
ALTER TABLE contacts COMMENT = 'Bảng lưu thông tin chi tiết của contacts/friends';
ALTER TABLE user_contacts COMMENT = 'Bảng quan hệ many-to-many giữa user và contacts';
ALTER TABLE messages COMMENT = 'Bảng lưu lịch sử tin nhắn';
ALTER TABLE conversations COMMENT = 'Bảng lưu thông tin cuộc hội thoại';

-- Trigger để tự động tạo conversation_id
DELIMITER //
CREATE TRIGGER create_conversation_id 
BEFORE INSERT ON messages
FOR EACH ROW
BEGIN
    IF NEW.conversation_id IS NULL OR NEW.conversation_id = '' THEN
        SET NEW.conversation_id = CONCAT(
            LEAST(NEW.sender_phone, NEW.receiver_phone),
            '_',
            GREATEST(NEW.sender_phone, NEW.receiver_phone)
        );
    END IF;
END//
DELIMITER ; 