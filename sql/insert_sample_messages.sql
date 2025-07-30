-- Thêm dữ liệu mẫu cho conversations và messages
-- Đảm bảo rằng các user với số điện thoại này đã tồn tại trong bảng user

-- Tạo conversation mẫu
INSERT INTO `conversations` (
    `id`,
    `participant1_phone`,
    `participant2_phone`,
    `last_activity`,
    `created_at`
) VALUES
('conv_sample_1', '0123456789', '0987654321', NOW(), NOW()),
('conv_sample_2', '0123456789', '0555666777', NOW(), NOW()),
('conv_sample_3', '0123456789', '0333444555', NOW(), NOW());

-- Thêm messages mẫu cho conversation 1
INSERT INTO `messages` (
    `conversation_id`,
    `sender_phone`,
    `receiver_phone`,
    `message_text`,
    `message_type`,
    `is_read`,
    `sent_at`
) VALUES
-- Conversation 1: 0123456789 <-> 0987654321
('conv_sample_1', '0123456789', '0987654321', 'Chào bạn!', 'text', 1, NOW() - INTERVAL 2 HOUR),
('conv_sample_1', '0987654321', '0123456789', 'Chào! Bạn khỏe không?', 'text', 1, NOW() - INTERVAL 1 HOUR + INTERVAL 30 MINUTE),
('conv_sample_1', '0123456789', '0987654321', 'Tôi khỏe, cảm ơn bạn!', 'text', 1, NOW() - INTERVAL 1 HOUR),
('conv_sample_1', '0987654321', '0123456789', 'Tối nay có rảnh không?', 'text', 0, NOW() - INTERVAL 30 MINUTE),
('conv_sample_1', '0123456789', '0987654321', 'Có rảnh, bạn muốn làm gì?', 'text', 0, NOW() - INTERVAL 15 MINUTE),

-- Conversation 2: 0123456789 <-> 0555666777
('conv_sample_2', '0123456789', '0555666777', 'Ba ơi, con về nhà rồi', 'text', 1, NOW() - INTERVAL 3 HOUR),
('conv_sample_2', '0555666777', '0123456789', 'Con về sớm thế, có gì không?', 'text', 1, NOW() - INTERVAL 2 HOUR + INTERVAL 45 MINUTE),
('conv_sample_2', '0123456789', '0555666777', 'Con mua đồ ăn về cho ba mẹ', 'text', 1, NOW() - INTERVAL 2 HOUR + INTERVAL 30 MINUTE),
('conv_sample_2', '0555666777', '0123456789', 'Con ngoan quá, cảm ơn con!', 'text', 0, NOW() - INTERVAL 1 HOUR),

-- Conversation 3: 0123456789 <-> 0333444555
('conv_sample_3', '0333444555', '0123456789', 'Bác ơi, sáng mai có đi bộ không?', 'text', 1, NOW() - INTERVAL 4 HOUR),
('conv_sample_3', '0123456789', '0333444555', 'Có đi bác ạ, mấy giờ?', 'text', 1, NOW() - INTERVAL 3 HOUR + INTERVAL 30 MINUTE),
('conv_sample_3', '0333444555', '0123456789', '6 giờ sáng nhé bác', 'text', 1, NOW() - INTERVAL 3 HOUR),
('conv_sample_3', '0123456789', '0333444555', 'Được rồi, sáng mai gặp nhau nhé!', 'text', 0, NOW() - INTERVAL 2 HOUR);

-- Cập nhật last_message_id cho các conversations
UPDATE `conversations` 
SET `last_message_id` = (
    SELECT `id` FROM `messages` 
    WHERE `conversation_id` = `conversations`.`id` 
    ORDER BY `sent_at` DESC 
    LIMIT 1
)
WHERE `id` IN ('conv_sample_1', 'conv_sample_2', 'conv_sample_3'); 