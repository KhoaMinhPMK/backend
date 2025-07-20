-- =====================================================
-- DỮ LIỆU MẪU-- BƯỚC 9: Thêm payment transactions
-- Chạy phần này sau khi có users, premium_plans và user_subscriptions
INSERT INTO payment_transactions (userId, subscriptionId, planId, transactionCode, amount, currency, status, paymentMethod, type, description, paidAt, created_at, updated_at) VALUES
(2, 1, 2, 'TXN_1702876543_2_2', 199000, 'VND', 'completed', 'momo', 'subscription', 'Thanh toán Gói Nâng Cao - monthly', NOW(), NOW(), NOW()),
(2, 1, 2, 'TXN_1702776543_2_2', 199000, 'VND', 'completed', 'momo', 'renewal', 'Gia hạn Gói Nâng Cao - monthly', DATE_SUB(NOW(), INTERVAL 1 MONTH), DATE_SUB(NOW(), INTERVAL 1 MONTH), DATE_SUB(NOW(), INTERVAL 1 MONTH))
ON DUPLICATE KEY UPDATE updated_at = NOW();OÀN - CHẠY TỪNG PHẦN MỘT
-- =====================================================

-- BƯỚC 1: Thêm users trước với thông tin premium
-- Chạy phần này trước tiên
INSERT INTO users (id, fullName, email, password, phone, role, active, isPremium, premiumTrialUsed, created_at, updated_at) VALUES
(1, 'Nguyễn Văn A', 'user1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0123456789', 'elderly', TRUE, FALSE, FALSE, NOW(), NOW()),
(2, 'Trần Thị B', 'user2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0987654321', 'relative', TRUE, TRUE, TRUE, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- BƯỚC 2: Thêm premium plans
-- Chạy phần này sau khi có users
INSERT INTO premium_plans (name, description, price, duration, type, features, isActive, sortOrder, isRecommended, discountPercent, created_at, updated_at) VALUES
('Gói Cơ Bản', 'Gói premium cơ bản với các tính năng cần thiết', 99000, 30, 'monthly', '["Truy cập không giới hạn", "Hỗ trợ 24/7", "Không quảng cáo"]', TRUE, 1, FALSE, 0, NOW(), NOW()),
('Gói Nâng Cao', 'Gói premium nâng cao với nhiều tính năng hơn', 199000, 30, 'monthly', '["Tất cả tính năng cơ bản", "Tùy chỉnh giao diện", "Sao lưu dữ liệu", "Tích hợp AI"]', TRUE, 2, TRUE, 10, NOW(), NOW()),
('Gói Gia Đình', 'Gói premium cho cả gia đình', 299000, 30, 'monthly', '["Tất cả tính năng nâng cao", "Quản lý nhiều tài khoản", "Báo cáo chi tiết", "Tích hợp IoT"]', TRUE, 3, FALSE, 15, NOW(), NOW()),
('Gói Năm', 'Gói premium trả trước 1 năm', 990000, 365, 'yearly', '["Tất cả tính năng gia đình", "Ưu đãi đặc biệt", "Hỗ trợ ưu tiên"]', TRUE, 4, FALSE, 20, NOW(), NOW()),
('Gói Trọn Đời', 'Gói premium trọn đời', 2990000, 0, 'lifetime', '["Tất cả tính năng", "Cập nhật miễn phí trọn đời", "Hỗ trợ VIP"]', TRUE, 5, FALSE, 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- BƯỚC 3: Thêm user settings
-- Chạy phần này sau khi có users
INSERT INTO user_settings (userId, language, isDarkMode, elderly_notificationsEnabled, elderly_soundEnabled, elderly_vibrationEnabled, relative_appNotificationsEnabled, relative_emailAlertsEnabled, relative_smsAlertsEnabled, created_at, updated_at) VALUES
(1, 'vi', FALSE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, NOW(), NOW()),
(2, 'vi', FALSE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- BƯỚC 4: Thêm emergency contacts
-- Chạy phần này sau khi có users
INSERT INTO emergency_contacts (userId, name, phone, relationship, isPrimary, created_at, updated_at) VALUES
(1, 'Nguyễn Văn B', '0123456788', 'Con trai', TRUE, NOW(), NOW()),
(1, 'Trần Thị C', '0123456787', 'Con gái', FALSE, NOW(), NOW()),
(2, 'Lê Văn D', '0123456786', 'Chồng', TRUE, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- BƯỚC 5: Thêm health records
-- Chạy phần này sau khi có users
INSERT INTO health_records (userId, recordType, title, description, date, severity, created_at, updated_at) VALUES
(1, 'medication', 'Thuốc huyết áp', 'Uống 1 viên mỗi sáng', NOW(), 'medium', NOW(), NOW()),
(1, 'allergy', 'Dị ứng penicillin', 'Không được dùng thuốc có penicillin', NOW(), 'high', NOW(), NOW()),
(2, 'condition', 'Tiểu đường', 'Theo dõi đường huyết hàng ngày', NOW(), 'medium', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- BƯỚC 6: Thêm elderly relatives
-- Chạy phần này sau khi có users
INSERT INTO elderly_relatives (elderlyId, relativeId, relationship, isPrimary, created_at, updated_at) VALUES
(1, 2, 'vợ/chồng', TRUE, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- BƯỚC 7: Thêm notifications
-- Chạy phần này sau khi có users
INSERT INTO notifications (userId, type, title, message, isRead, priority, created_at, updated_at) VALUES
(1, 'reminder', 'Nhắc nhở uống thuốc', 'Đã đến giờ uống thuốc huyết áp', FALSE, 'medium', NOW(), NOW()),
(1, 'alert', 'Cảnh báo sức khỏe', 'Huyết áp cao hơn bình thường', FALSE, 'high', NOW(), NOW()),
(2, 'info', 'Thông báo mới', 'Có tin nhắn mới từ người thân', FALSE, 'low', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- BƯỚC 8: Thêm user subscriptions với premium status
-- Chạy phần này sau khi có users và premium_plans
-- User 2 có premium active
UPDATE users SET 
    isPremium = TRUE, 
    premiumStartDate = NOW(), 
    premiumEndDate = DATE_ADD(NOW(), INTERVAL 1 MONTH),
    premiumPlanId = 2
WHERE id = 2;

INSERT INTO user_subscriptions (userId, planId, status, startDate, endDate, autoRenewal, paidAmount, paymentMethod, created_at, updated_at) VALUES
(2, 2, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH), TRUE, 199000, 'momo', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- BƯỚC 9: Thêm payment transactions
-- Chạy phần này sau khi có users, premium_plans và user_subscriptions
INSERT INTO payment_transactions (userId, subscriptionId, planId, transactionCode, amount, currency, status, paymentMethod, type, description, paidAt, created_at, updated_at) VALUES
(1, 1, 2, 'TXN_123456789', 199000, 'VND', 'completed', 'momo', 'subscription', 'Thanh toán gói Nâng Cao', NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- =====================================================
-- HƯỚNG DẪN SỬ DỤNG:
-- 1. Chạy từng BƯỚC một theo thứ tự
-- 2. Nếu gặp lỗi, kiểm tra xem bảng đã tồn tại chưa
-- 3. Đảm bảo đã import database_schema.sql trước
-- ===================================================== 