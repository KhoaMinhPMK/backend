-- SAMPLE DATA for VieGrand Chat System

-- Insert sample users
INSERT INTO user (userName, email, phone, age, gender) VALUES
('Nguyễn Văn An', 'nguyenvanan@gmail.com', '0901234567', 68, 'Nam'),
('Trần Thị Bình', 'tranthibinh@gmail.com', '0987654321', 65, 'Nữ'),
('Lê Văn Cường', 'levancuong@gmail.com', '0123456789', 70, 'Nam'),
('Phạm Thị Dung', 'phamthidung@gmail.com', '0345678901', 62, 'Nữ'),
('Hoàng Văn Em', 'hoangvanem@gmail.com', '0567890123', 67, 'Nam'),
('Ngọc Anh', 'ngocanh@gmail.com', '0789012345', 35, 'Nữ'),
('Minh Tuấn', 'minhtuan@gmail.com', '0890123456', 40, 'Nam'),
('Lan Hương', 'lanhuong@gmail.com', '0912345678', 38, 'Nữ'),
('Đức Minh', 'ducminh@gmail.com', '0934567890', 42, 'Nam'),
('Hồng Nhung', 'hongnhung@gmail.com', '0956789012', 36, 'Nữ');

-- Insert contacts
INSERT INTO contacts (name, phone, relationship, is_emergency_contact) VALUES
('Ngọc Anh', '0789012345', 'con gái', TRUE),
('Minh Tuấn', '0890123456', 'con trai', TRUE),
('Lan Hương', '0912345678', 'bạn bè', FALSE),
('Đức Minh', '0934567890', 'bạn bè', FALSE),
('Hồng Nhung', '0956789012', 'hàng xóm', FALSE),
('Trần Thị Bình', '0987654321', 'bạn bè', FALSE),
('Lê Văn Cường', '0123456789', 'bạn bè', FALSE),
('Phạm Thị Dung', '0345678901', 'bạn bè', FALSE),
('Hoàng Văn Em', '0567890123', 'bạn bè', FALSE);

-- Insert user_contacts relationships (for user 0901234567 - Nguyễn Văn An)
INSERT INTO user_contacts (user_phone, contact_id, is_favorite, contact_order) VALUES
('0901234567', 1, TRUE, 1),  -- Ngọc Anh (con gái)
('0901234567', 2, TRUE, 2),  -- Minh Tuấn (con trai)
('0901234567', 3, FALSE, 3), -- Lan Hương (bạn bè)
('0901234567', 6, FALSE, 4), -- Trần Thị Bình (bạn bè)
('0901234567', 7, FALSE, 5); -- Lê Văn Cường (bạn bè)

-- Insert friend status (all are accepted friends)
INSERT INTO friend_status (user_phone, friend_phone, status, requester_phone, responded_at) VALUES
('0901234567', '0789012345', 'accepted', '0789012345', NOW()),
('0789012345', '0901234567', 'accepted', '0789012345', NOW()),
('0901234567', '0890123456', 'accepted', '0890123456', NOW()),
('0890123456', '0901234567', 'accepted', '0890123456', NOW()),
('0901234567', '0912345678', 'accepted', '0901234567', NOW()),
('0912345678', '0901234567', 'accepted', '0901234567', NOW()),
('0901234567', '0987654321', 'accepted', '0987654321', NOW()),
('0987654321', '0901234567', 'accepted', '0987654321', NOW());

-- Insert sample messages between users
-- Conversation 1: 0901234567 ↔ 0789012345 (Nguyễn Văn An ↔ Ngọc Anh)
INSERT INTO messages (conversation_id, sender_phone, receiver_phone, message_text, message_type, sent_at) VALUES
('0789012345_0901234567', '0789012345', '0901234567', 'Chào ba! Con đang ở văn phòng.', 'text', '2024-01-15 09:30:00'),
('0789012345_0901234567', '0901234567', '0789012345', 'Chào con! Ba đang ở nhà nhé.', 'text', '2024-01-15 09:31:00'),
('0789012345_0901234567', '0789012345', '0901234567', 'Trưa nay con về ăn cơm với ba mẹ nhé!', 'text', '2024-01-15 09:32:00'),
('0789012345_0901234567', '0901234567', '0789012345', 'Được rồi con, ba mẹ chờ con.', 'text', '2024-01-15 09:33:00'),
('0789012345_0901234567', '0789012345', '0901234567', 'Ba có nhớ uống thuốc không ạ?', 'text', '2024-01-15 10:15:00'),
('0789012345_0901234567', '0901234567', '0789012345', 'Ba nhớ rồi con, cảm ơn con nhé!', 'text', '2024-01-15 10:16:00');

-- Conversation 2: 0901234567 ↔ 0890123456 (Nguyễn Văn An ↔ Minh Tuấn)
INSERT INTO messages (conversation_id, sender_phone, receiver_phone, message_text, message_type, sent_at) VALUES
('0890123456_0901234567', '0890123456', '0901234567', 'Ba khỏe không ạ?', 'text', '2024-01-15 08:00:00'),
('0890123456_0901234567', '0901234567', '0890123456', 'Ba khỏe lắm con trai!', 'text', '2024-01-15 08:01:00'),
('0890123456_0901234567', '0890123456', '0901234567', 'Tuần này con sẽ về thăm ba mẹ nhé!', 'text', '2024-01-15 08:02:00'),
('0890123456_0901234567', '0901234567', '0890123456', 'Tốt quá! Ba mẹ nhớ con lắm.', 'text', '2024-01-15 08:03:00');

-- Conversation 3: 0901234567 ↔ 0912345678 (Nguyễn Văn An ↔ Lan Hương)
INSERT INTO messages (conversation_id, sender_phone, receiver_phone, message_text, message_type, sent_at) VALUES
('0901234567_0912345678', '0912345678', '0901234567', 'Chú An có rảnh không ạ?', 'text', '2024-01-15 14:00:00'),
('0901234567_0912345678', '0901234567', '0912345678', 'Có chú rảnh, sao thế Hương?', 'text', '2024-01-15 14:01:00'),
('0901234567_0912345678', '0912345678', '0901234567', 'Chiều nay em muốn mời chú đi uống trà.', 'text', '2024-01-15 14:02:00'),
('0901234567_0912345678', '0901234567', '0912345678', 'Được chứ! Chú đi ngay.', 'text', '2024-01-15 14:03:00');

-- Insert conversations
INSERT INTO conversations (id, participant1_phone, participant2_phone, last_activity) VALUES
('0789012345_0901234567', '0789012345', '0901234567', '2024-01-15 10:16:00'),
('0890123456_0901234567', '0890123456', '0901234567', '2024-01-15 08:03:00'),
('0901234567_0912345678', '0901234567', '0912345678', '2024-01-15 14:03:00');

-- Insert message security settings (default safe settings)
INSERT INTO message_security_settings (user_phone, allow_stranger_messages, auto_block_spam, require_friend_request) VALUES
('0901234567', FALSE, TRUE, TRUE),
('0789012345', FALSE, TRUE, TRUE),
('0890123456', FALSE, TRUE, TRUE),
('0912345678', FALSE, TRUE, TRUE),
('0987654321', FALSE, TRUE, TRUE),
('0123456789', TRUE, TRUE, FALSE),
('0345678901', FALSE, TRUE, TRUE),
('0567890123', FALSE, TRUE, TRUE),
('0934567890', TRUE, FALSE, FALSE),
('0956789012', FALSE, TRUE, TRUE);

-- Insert some sample friend requests
INSERT INTO friend_requests (from_phone, to_phone, message, sent_at) VALUES
('0934567890', '0901234567', 'Chào chú An, tôi là Đức Minh. Chú có thể kết bạn với tôi không ạ?', '2024-01-15 16:00:00'),
('0956789012', '0901234567', 'Chào chú, em là Hồng Nhung ở khu phố bên cạnh. Em muốn kết bạn với chú ạ.', '2024-01-15 17:00:00');

-- Insert some stranger messages
INSERT INTO stranger_messages (from_phone, to_phone, message_text, sent_at) VALUES
('0999888777', '0901234567', 'Chào anh, tôi có món hàng tốt muốn giới thiệu...', '2024-01-15 18:00:00'),
('0888777666', '0901234567', 'Xin chào, bạn có cần vay tiền không?', '2024-01-15 19:00:00'); 