-- Tạo 10 user elderly với tuổi từ 5 đến 15
-- Sử dụng private key ngẫu nhiên cho mỗi user

INSERT INTO user (userName, email, phone, password, role, age, gender, private_key) VALUES
('Nguyễn Văn An', 'an.nguyen@test.com', '1111111111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'elderly', 5, 'male', 'abc123def'),
('Trần Thị Bình', 'binh.tran@test.com', '1111111112', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'elderly', 6, 'female', 'def456ghi'),
('Lê Văn Cường', 'cuong.le@test.com', '1111111113', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'elderly', 7, 'male', 'ghi789jkl'),
('Phạm Thị Dung', 'dung.pham@test.com', '1111111114', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'elderly', 8, 'female', 'jkl012mno'),
('Hoàng Văn Em', 'em.hoang@test.com', '1111111115', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'elderly', 9, 'male', 'mno345pqr'),
('Vũ Thị Phương', 'phuong.vu@test.com', '1111111116', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'elderly', 10, 'female', 'pqr678stu'),
('Đặng Văn Giang', 'giang.dang@test.com', '1111111117', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'elderly', 11, 'male', 'stu901vwx'),
('Ngô Thị Hoa', 'hoa.ngo@test.com', '1111111118', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'elderly', 12, 'female', 'vwx234yza'),
('Lý Văn Ích', 'ich.ly@test.com', '1111111119', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'elderly', 13, 'male', 'yza567bcd'),
('Hồ Thị Kim', 'kim.ho@test.com', '1111111120', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'elderly', 14, 'female', 'bcd890efg');

-- Tạo thêm 5 user relative để test
INSERT INTO user (userName, email, phone, password, role, age, gender, private_key) VALUES
('Nguyễn Văn Relative1', 'relative1@test.com', '2222222221', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'relative', 25, 'male', 'rel123abc'),
('Trần Thị Relative2', 'relative2@test.com', '2222222222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'relative', 30, 'female', 'rel456def'),
('Lê Văn Relative3', 'relative3@test.com', '2222222223', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'relative', 35, 'male', 'rel789ghi'),
('Phạm Thị Relative4', 'relative4@test.com', '2222222224', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'relative', 28, 'female', 'rel012jkl'),
('Hoàng Văn Relative5', 'relative5@test.com', '2222222225', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'relative', 32, 'male', 'rel345mno');

-- Tạo premium subscription cho relative1
INSERT INTO premium_subscriptions (user_id, premium_key, start_date, end_date, status, note) VALUES
(11, 'PREMIUM001', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 'active', 'Test premium subscription');

-- Tạo premium subscription cho relative2  
INSERT INTO premium_subscriptions (user_id, premium_key, start_date, end_date, status, note) VALUES
(12, 'PREMIUM002', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 'active', 'Test premium subscription');

-- Tạo premium subscription cho relative3
INSERT INTO premium_subscriptions (user_id, premium_key, start_date, end_date, status, note) VALUES
(13, 'PREMIUM003', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 'active', 'Test premium subscription');

-- Tạo premium subscription cho relative4
INSERT INTO premium_subscriptions (user_id, premium_key, start_date, end_date, status, note) VALUES
(14, 'PREMIUM004', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 'active', 'Test premium subscription');

-- Tạo premium subscription cho relative5
INSERT INTO premium_subscriptions (user_id, premium_key, start_date, end_date, status, note) VALUES
(15, 'PREMIUM005', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 'active', 'Test premium subscription');
