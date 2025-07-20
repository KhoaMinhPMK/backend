---
noteId: "0f0bfd50656d11f0b6b4355d6b743456"
tags: []

---

# Khắc phục lỗi "Không thể tải các gói premium"

## Vấn đề
Khi vào màn hình Premium, app hiển thị lỗi "Không thể tải các gói premium"

## Nguyên nhân
1. Database chưa có dữ liệu premium plans
2. API endpoint không đúng
3. Database schema chưa đầy đủ

## Cách khắc phục

### Bước 1: Kiểm tra database
```sql
-- Kiểm tra xem có bảng premium_plans không
SHOW TABLES LIKE 'premium_plans';

-- Kiểm tra dữ liệu trong bảng
SELECT * FROM premium_plans;
```

### Bước 2: Chạy lại database schema
```bash
# Import lại database schema
mysql -u username -p database_name < database_schema.sql
```

### Bước 3: Khởi tạo dữ liệu premium
```bash
# Chạy file khởi tạo dữ liệu
curl -X POST http://localhost/backendphp/init_premium_data.php \
  -H "Content-Type: application/json"
```

### Bước 4: Test API
```bash
# Test API lấy danh sách plans
curl -X GET http://localhost/backendphp/premium_api.php/plans

# Test API payment methods
curl -X GET http://localhost/backendphp/premium_api.php/payment-methods
```

### Bước 5: Kiểm tra trong app
1. Mở app
2. Vào màn hình Premium
3. Kiểm tra xem có hiển thị danh sách gói không

## Cấu trúc dữ liệu cần có

### Bảng premium_plans
```sql
CREATE TABLE premium_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration INT NOT NULL,
    type ENUM('monthly', 'yearly', 'lifetime') NOT NULL,
    features JSON,
    isActive BOOLEAN DEFAULT TRUE,
    sortOrder INT DEFAULT 0,
    isRecommended BOOLEAN DEFAULT FALSE,
    discountPercent DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Dữ liệu mẫu cần có
```sql
INSERT INTO premium_plans (name, description, price, duration, type, features, isActive, sortOrder, isRecommended) VALUES
('Gói Cơ Bản', 'Gói premium cơ bản với các tính năng cần thiết', 99000, 30, 'monthly', '["Truy cập không giới hạn", "Hỗ trợ 24/7", "Không quảng cáo", "Thông báo cơ bản"]', TRUE, 1, FALSE),
('Gói Nâng Cao', 'Gói premium nâng cao với nhiều tính năng hơn', 199000, 30, 'monthly', '["Tất cả tính năng cơ bản", "Tùy chỉnh giao diện", "Sao lưu dữ liệu", "Tích hợp AI", "Báo cáo chi tiết", "Liên hệ khẩn cấp"]', TRUE, 2, TRUE),
('Gói Gia Đình', 'Gói premium cho cả gia đình', 299000, 30, 'monthly', '["Tất cả tính năng nâng cao", "Quản lý nhiều tài khoản", "Báo cáo chi tiết", "Tích hợp IoT", "Hỗ trợ ưu tiên", "Tính năng độc quyền"]', TRUE, 3, FALSE),
('Gói Năm', 'Tiết kiệm với gói dài hạn', 990000, 365, 'yearly', '["Tất cả tính năng gia đình", "Ưu đãi đặc biệt", "Hỗ trợ ưu tiên", "Tiết kiệm 20%", "Cập nhật miễn phí"]', TRUE, 4, FALSE),
('Gói Trọn Đời', 'Sử dụng vĩnh viễn', 2990000, 0, 'lifetime', '["Tất cả tính năng", "Cập nhật miễn phí trọn đời", "Hỗ trợ VIP", "Tính năng độc quyền cao cấp", "Không giới hạn thời gian"]', TRUE, 5, FALSE);
```

## API Endpoints cần kiểm tra

### 1. GET /premium_api.php/plans
Trả về danh sách các gói premium

### 2. GET /premium_api.php/payment-methods
Trả về danh sách phương thức thanh toán

### 3. GET /premium_api.php/my-status
Trả về trạng thái premium của user

## Debug

### Kiểm tra log
```bash
# Kiểm tra log PHP
tail -f /var/log/apache2/error.log

# Hoặc log nginx
tail -f /var/log/nginx/error.log
```

### Test API trực tiếp
```bash
# Test với curl
curl -v -X GET http://localhost/backendphp/premium_api.php/plans

# Test với Postman
GET http://localhost/backendphp/premium_api.php/plans
```

### Kiểm tra network trong app
1. Mở Developer Tools
2. Vào tab Network
3. Thử load màn hình Premium
4. Xem request nào bị lỗi

## Lưu ý
- Đảm bảo database đã được tạo và có dữ liệu
- Kiểm tra kết nối database trong config.php
- Đảm bảo API endpoint đúng trong app
- Kiểm tra CORS headers nếu cần

## Nếu vẫn lỗi
1. Kiểm tra console log trong app
2. Kiểm tra network requests
3. Test API trực tiếp với curl/Postman
4. Kiểm tra database connection
5. Kiểm tra PHP error logs 