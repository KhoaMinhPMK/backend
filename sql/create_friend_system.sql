-- HỆ THỐNG KẾT BẠN VÀ TIN NHẮN AN TOÀN CHO NGƯỜI CAO TUỔI

-- Bảng quản lý trạng thái bạn bè
CREATE TABLE friend_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_phone VARCHAR(20) NOT NULL,
    friend_phone VARCHAR(20) NOT NULL,
    status ENUM('pending', 'accepted', 'blocked', 'rejected') DEFAULT 'pending',
    requester_phone VARCHAR(20) NOT NULL, -- Ai là người gửi lời mời
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    notes TEXT DEFAULT NULL, -- Ghi chú về mối quan hệ
    
    -- Index để tối ưu hóa
    INDEX idx_user_phone (user_phone),
    INDEX idx_friend_phone (friend_phone),
    INDEX idx_status (status),
    INDEX idx_requester (requester_phone),
    
    -- Đảm bảo không có duplicate relationship
    UNIQUE KEY unique_friendship (user_phone, friend_phone)
);

-- Bảng lời mời kết bạn
CREATE TABLE friend_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_phone VARCHAR(20) NOT NULL,
    to_phone VARCHAR(20) NOT NULL,
    message TEXT DEFAULT NULL, -- Tin nhắn kèm lời mời
    status ENUM('pending', 'accepted', 'rejected', 'expired') DEFAULT 'pending',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    expires_at TIMESTAMP DEFAULT (DATE_ADD(NOW(), INTERVAL 7 DAY)), -- Hết hạn sau 7 ngày
    
    -- Index
    INDEX idx_from_phone (from_phone),
    INDEX idx_to_phone (to_phone),
    INDEX idx_status (status),
    INDEX idx_expires (expires_at),
    
    -- Đảm bảo không spam request
    UNIQUE KEY unique_active_request (from_phone, to_phone, status)
);

-- Bảng tin nhắn từ stranger (chưa là bạn bè)
CREATE TABLE stranger_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_phone VARCHAR(20) NOT NULL,
    to_phone VARCHAR(20) NOT NULL,
    message_text TEXT NOT NULL,
    message_type ENUM('text', 'image', 'voice') DEFAULT 'text',
    file_url VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'allowed', 'blocked') DEFAULT 'pending',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    auto_blocked BOOLEAN DEFAULT FALSE, -- Tự động block nếu phát hiện spam
    
    -- Index
    INDEX idx_from_phone (from_phone),
    INDEX idx_to_phone (to_phone),
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at)
);

-- Bảng blacklist để block số spam
CREATE TABLE blocked_numbers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_phone VARCHAR(20) NOT NULL,
    blocked_phone VARCHAR(20) NOT NULL,
    reason VARCHAR(100) DEFAULT NULL,
    blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    blocked_by ENUM('user', 'system', 'admin') DEFAULT 'user',
    
    -- Index
    INDEX idx_user_phone (user_phone),
    INDEX idx_blocked_phone (blocked_phone),
    
    -- Đảm bảo không duplicate block
    UNIQUE KEY unique_block (user_phone, blocked_phone)
);

-- Bảng cài đặt bảo mật tin nhắn
CREATE TABLE message_security_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_phone VARCHAR(20) NOT NULL UNIQUE,
    allow_stranger_messages BOOLEAN DEFAULT FALSE, -- Cho phép tin nhắn từ người lạ
    auto_block_spam BOOLEAN DEFAULT TRUE, -- Tự động block spam
    require_friend_request BOOLEAN DEFAULT TRUE, -- Bắt buộc gửi lời mời kết bạn
    notification_new_request BOOLEAN DEFAULT TRUE, -- Thông báo lời mời mới
    notification_stranger_message BOOLEAN DEFAULT TRUE, -- Thông báo tin nhắn từ người lạ
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Index
    INDEX idx_user_phone (user_phone)
);

-- Cập nhật bảng messages để track friendship status
ALTER TABLE messages ADD COLUMN requires_friendship BOOLEAN DEFAULT FALSE;
ALTER TABLE messages ADD COLUMN friendship_status ENUM('friends', 'pending', 'stranger', 'blocked') DEFAULT 'stranger';

-- Insert default security settings cho user hiện có
INSERT INTO message_security_settings (user_phone, allow_stranger_messages, auto_block_spam, require_friend_request)
SELECT DISTINCT phone, FALSE, TRUE, TRUE FROM user WHERE phone IS NOT NULL;

-- Trigger để tự động tạo friendship record khi accept friend request
DELIMITER //
CREATE TRIGGER accept_friend_request
AFTER UPDATE ON friend_requests
FOR EACH ROW
BEGIN
    IF NEW.status = 'accepted' AND OLD.status = 'pending' THEN
        -- Tạo friendship cho cả 2 người
        INSERT IGNORE INTO friend_status (user_phone, friend_phone, status, requester_phone, responded_at)
        VALUES 
            (NEW.to_phone, NEW.from_phone, 'accepted', NEW.from_phone, NOW()),
            (NEW.from_phone, NEW.to_phone, 'accepted', NEW.from_phone, NOW());
        
        -- Chuyển stranger messages thành normal messages
        UPDATE stranger_messages 
        SET status = 'allowed', reviewed_at = NOW()
        WHERE (from_phone = NEW.from_phone AND to_phone = NEW.to_phone)
           OR (from_phone = NEW.to_phone AND to_phone = NEW.from_phone);
    END IF;
END//
DELIMITER ;

-- Function kiểm tra 2 người có phải bạn bè không
DELIMITER //
CREATE FUNCTION are_friends(phone1 VARCHAR(20), phone2 VARCHAR(20)) 
RETURNS BOOLEAN
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE friendship_count INT DEFAULT 0;
    
    SELECT COUNT(*) INTO friendship_count
    FROM friend_status 
    WHERE ((user_phone = phone1 AND friend_phone = phone2) 
           OR (user_phone = phone2 AND friend_phone = phone1))
      AND status = 'accepted';
    
    RETURN friendship_count > 0;
END//
DELIMITER ;

-- View để lấy danh sách bạn bè
CREATE VIEW user_friends AS
SELECT 
    fs.user_phone,
    c.id as contact_id,
    c.name,
    c.phone as friend_phone,
    c.avatar_url,
    c.relationship,
    fs.requested_at as became_friends_at,
    uc.is_favorite,
    uc.last_contacted,
    uc.contact_frequency
FROM friend_status fs
JOIN contacts c ON fs.friend_phone = c.phone
LEFT JOIN user_contacts uc ON fs.user_phone = uc.user_phone AND c.id = uc.contact_id
WHERE fs.status = 'accepted';

-- View để lấy pending friend requests
CREATE VIEW pending_friend_requests AS
SELECT 
    fr.id,
    fr.from_phone,
    fr.to_phone,
    fr.message,
    fr.sent_at,
    fr.expires_at,
    c.name as sender_name,
    c.avatar_url as sender_avatar
FROM friend_requests fr
LEFT JOIN contacts c ON fr.from_phone = c.phone
WHERE fr.status = 'pending' 
  AND fr.expires_at > NOW();

-- Sample data
INSERT INTO friend_requests (from_phone, to_phone, message) VALUES
('0987654321', '0901234567', 'Chào bác, cháu là Ngọc Anh. Bác có thể kết bạn với cháu không ạ?'),
('0123456789', '0901234567', 'Ba ơi, con là Tuấn đây. Kết bạn với con nhé ba!');

-- Comments
ALTER TABLE friend_status COMMENT = 'Bảng quản lý trạng thái bạn bè';
ALTER TABLE friend_requests COMMENT = 'Bảng lời mời kết bạn';
ALTER TABLE stranger_messages COMMENT = 'Bảng tin nhắn từ người lạ chưa là bạn bè';
ALTER TABLE blocked_numbers COMMENT = 'Bảng danh sách số bị chặn';
ALTER TABLE message_security_settings COMMENT = 'Bảng cài đặt bảo mật tin nhắn'; 