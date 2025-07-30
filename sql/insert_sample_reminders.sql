-- Thêm dữ liệu mẫu vào bảng nhắc nhở
INSERT INTO `nhac_nho` (
    `email_nguoi_dung`,
    `ten_nguoi_dung`,
    `thoi_gian`,
    `ngay_gio`,
    `noi_dung`,
    `trang_thai`,
    `ngay_tao`,
    `ngay_cap_nhat`
) VALUES 
-- Nhắc nhở hôm nay
('pmkkhoaminh@gmail.com', 'Bà Nguyễn Thị Mai', '08:00:00', NOW() + INTERVAL 2 HOUR, 'Uống thuốc huyết áp trước khi ăn sáng', 'chua_thuc_hien', NOW(), NOW()),
('pmkkhoaminh@gmail.com', 'Bà Nguyễn Thị Mai', '06:30:00', NOW() - INTERVAL 1 HOUR, 'Tập thể dục buổi sáng - đi bộ 30 phút', 'da_thuc_hien', NOW(), NOW()),
('pmkkhoaminh@gmail.com', 'Bà Nguyễn Thị Mai', '19:00:00', NOW() + INTERVAL 8 HOUR, 'Gọi điện hỏi thăm con gái', 'chua_thuc_hien', NOW(), NOW()),
('pmkkhoaminh@gmail.com', 'Bà Nguyễn Thị Mai', '12:00:00', NOW() + INTERVAL 1 HOUR, 'Uống thuốc tiểu đường sau khi ăn trưa', 'chua_thuc_hien', NOW(), NOW()),

-- Nhắc nhở ngày mai
('pmkkhoaminh@gmail.com', 'Bà Nguyễn Thị Mai', '14:00:00', NOW() + INTERVAL 1 DAY + INTERVAL 2 HOUR, 'Khám bệnh định kỳ tại bệnh viện Bạch Mai', 'chua_thuc_hien', NOW(), NOW()),
('pmkkhoaminh@gmail.com', 'Bà Nguyễn Thị Mai', '08:30:00', NOW() + INTERVAL 1 DAY, 'Uống thuốc tim mạch', 'chua_thuc_hien', NOW(), NOW()),
('pmkkhoaminh@gmail.com', 'Bà Nguyễn Thị Mai', '17:00:00', NOW() + INTERVAL 1 DAY + INTERVAL 5 HOUR, 'Tập yoga nhẹ nhàng', 'chua_thuc_hien', NOW(), NOW()),

-- Nhắc nhở đã hoàn thành
('pmkkhoaminh@gmail.com', 'Bà Nguyễn Thị Mai', '07:00:00', NOW() - INTERVAL 2 DAY, 'Đo huyết áp buổi sáng', 'da_thuc_hien', NOW(), NOW()),
('pmkkhoaminh@gmail.com', 'Bà Nguyễn Thị Mai', '20:00:00', NOW() - INTERVAL 1 DAY, 'Uống thuốc trước khi đi ngủ', 'da_thuc_hien', NOW(), NOW()),

-- Nhắc nhở cho người dùng khác
('user2@example.com', 'Ông Trần Văn Nam', '09:00:00', NOW() + INTERVAL 3 HOUR, 'Uống thuốc huyết áp', 'chua_thuc_hien', NOW(), NOW()),
('user2@example.com', 'Ông Trần Văn Nam', '16:00:00', NOW() + INTERVAL 1 DAY, 'Khám răng định kỳ', 'chua_thuc_hien', NOW(), NOW()); 