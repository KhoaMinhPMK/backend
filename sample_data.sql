-- Dữ liệu mẫu cho bảng premium_plans
INSERT INTO premium_plans (name, description, price, duration, type, features, isActive, sortOrder, isRecommended, discountPercent, created_at, updated_at) VALUES
('Gói Cơ Bản', 'Gói premium cơ bản với các tính năng cần thiết', 99000, 30, 'monthly', '["Truy cập không giới hạn", "Hỗ trợ 24/7", "Không quảng cáo"]', TRUE, 1, FALSE, 0, NOW(), NOW()),
('Gói Nâng Cao', 'Gói premium nâng cao với nhiều tính năng hơn', 199000, 30, 'monthly', '["Tất cả tính năng cơ bản", "Tùy chỉnh giao diện", "Sao lưu dữ liệu", "Tích hợp AI"]', TRUE, 2, TRUE, 10, NOW(), NOW()),
('Gói Gia Đình', 'Gói premium cho cả gia đình', 299000, 30, 'monthly', '["Tất cả tính năng nâng cao", "Quản lý nhiều tài khoản", "Báo cáo chi tiết", "Tích hợp IoT"]', TRUE, 3, FALSE, 15, NOW(), NOW()),
('Gói Năm', 'Gói premium trả trước 1 năm', 990000, 365, 'yearly', '["Tất cả tính năng gia đình", "Ưu đãi đặc biệt", "Hỗ trợ ưu tiên"]', TRUE, 4, FALSE, 20, NOW(), NOW()),
('Gói Trọn Đời', 'Gói premium trọn đời', 2990000, 0, 'lifetime', '["Tất cả tính năng", "Cập nhật miễn phí trọn đời", "Hỗ trợ VIP"]', TRUE, 5, FALSE, 0, NOW(), NOW());

-- Dữ liệu mẫu cho bảng users (nếu chưa có)
INSERT INTO users (id, fullName, email, password, phone, role, active, created_at, updated_at) VALUES
(1, 'Nguyễn Văn A', 'user1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0123456789', 'elderly', TRUE, NOW(), NOW()),
(2, 'Trần Thị B', 'user2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0987654321', 'relative', TRUE, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- Dữ liệu mẫu cho bảng user_settings (nếu chưa có)
INSERT INTO user_settings (userId, language, isDarkMode, elderly_notificationsEnabled, elderly_soundEnabled, elderly_vibrationEnabled, relative_appNotificationsEnabled, relative_emailAlertsEnabled, relative_smsAlertsEnabled, created_at, updated_at) VALUES
(1, 'vi', FALSE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, NOW(), NOW()),
(2, 'vi', FALSE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- Dữ liệu mẫu cho bảng emergency_contacts (nếu chưa có)
INSERT INTO emergency_contacts (userId, name, phone, relationship, isPrimary, created_at, updated_at) VALUES
(1, 'Nguyễn Văn B', '0123456788', 'Con trai', TRUE, NOW(), NOW()),
(1, 'Trần Thị C', '0123456787', 'Con gái', FALSE, NOW(), NOW()),
(2, 'Lê Văn D', '0123456786', 'Chồng', TRUE, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- Dữ liệu mẫu cho bảng health_records (nếu chưa có)
INSERT INTO health_records (userId, recordType, title, description, date, severity, created_at, updated_at) VALUES
(1, 'medication', 'Thuốc huyết áp', 'Uống 1 viên mỗi sáng', NOW(), 'medium', NOW(), NOW()),
(1, 'allergy', 'Dị ứng penicillin', 'Không được dùng thuốc có penicillin', NOW(), 'high', NOW(), NOW()),
(2, 'condition', 'Tiểu đường', 'Theo dõi đường huyết hàng ngày', NOW(), 'medium', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- Dữ liệu mẫu cho bảng elderly_relatives (nếu chưa có)
INSERT INTO elderly_relatives (elderlyId, relativeId, relationship, isPrimary, created_at, updated_at) VALUES
(1, 2, 'vợ/chồng', TRUE, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- Dữ liệu mẫu cho bảng notifications (nếu chưa có)
INSERT INTO notifications (userId, type, title, message, isRead, priority, created_at, updated_at) VALUES
(1, 'reminder', 'Nhắc nhở uống thuốc', 'Đã đến giờ uống thuốc huyết áp', FALSE, 'medium', NOW(), NOW()),
(1, 'alert', 'Cảnh báo sức khỏe', 'Huyết áp cao hơn bình thường', FALSE, 'high', NOW(), NOW()),
(2, 'info', 'Thông báo mới', 'Có tin nhắn mới từ người thân', FALSE, 'low', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- Dữ liệu mẫu cho bảng user_subscriptions (nếu chưa có)
INSERT INTO user_subscriptions (userId, planId, status, startDate, endDate, autoRenewal, paidAmount, paymentMethod, created_at, updated_at) VALUES
(1, 2, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH), TRUE, 199000, 'momo', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- Dữ liệu mẫu cho bảng payment_transactions (nếu chưa có)
INSERT INTO payment_transactions (userId, subscriptionId, planId, transactionCode, amount, currency, status, paymentMethod, type, description, paidAt, created_at, updated_at) VALUES
(1, 1, 2, 'TXN_123456789', 199000, 'VND', 'completed', 'momo', 'subscription', 'Thanh toán gói Nâng Cao', NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW(); 