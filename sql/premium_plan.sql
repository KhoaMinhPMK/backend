-- Tạo bảng premium_plan để chứa các gói Premium
CREATE TABLE IF NOT EXISTS premium_plan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    price DECIMAL(10,0) NOT NULL,
    currency VARCHAR(10) DEFAULT 'VND',
    duration_type ENUM('monthly', 'yearly') NOT NULL,
    duration_value INT NOT NULL,
    description TEXT,
    is_recommended BOOLEAN DEFAULT FALSE,
    discount_percentage INT DEFAULT 0,
    savings_text VARCHAR(50),
    features JSON,

    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Insert dữ liệu các gói Premium từ UI
INSERT INTO premium_plan (
    name, 
    display_name, 
    price, 
    currency, 
    duration_type, 
    duration_value, 
    description, 
    is_recommended, 
    discount_percentage, 
    savings_text,
    features
) VALUES 
(
    'premium_monthly',
    'Premium Monthly', 
    99000, 
    'VND', 
    'monthly', 
    1, 
    'Hàng tháng • Hủy bất kỳ lúc nào',
    FALSE,
    0,
    NULL,
    JSON_ARRAY(
        'Gọi video không giới hạn',
        'Theo dõi sức khỏe AI', 
        'Hỗ trợ 24/7'
    )
),
(
    'premium_yearly',
    'Premium Yearly',
    990000,
    'VND', 
    'yearly',
    1,
    'Hàng năm • Thanh toán một lần',
    TRUE,
    20,
    'Tiết kiệm 20%',
    JSON_ARRAY(
        'Tất cả tính năng Premium',
        'Báo cáo sức khỏe chi tiết',
        'Ưu tiên hỗ trợ',
        'Tính năng độc quyền'
    )
);
