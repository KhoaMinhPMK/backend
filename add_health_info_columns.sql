-- SQL script để thêm các cột thông tin y tế vào bảng users
-- Chạy script này nếu bạn muốn lưu trữ thông tin y tế trong database

ALTER TABLE users 
ADD COLUMN bloodType VARCHAR(5) NULL COMMENT 'Nhóm máu (A+, A-, B+, B-, AB+, AB-, O+, O-)',
ADD COLUMN allergies TEXT NULL COMMENT 'Danh sách các loại dị ứng',
ADD COLUMN chronicDiseases TEXT NULL COMMENT 'Danh sách các bệnh mãn tính';

-- Thêm index cho bloodType để tìm kiếm nhanh hơn
ALTER TABLE users ADD INDEX idx_bloodType (bloodType);

-- Kiểm tra cấu trúc bảng sau khi thêm cột
DESCRIBE users;
