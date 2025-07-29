-- Thêm các trường thông tin huyết áp vào bảng user
ALTER TABLE user 
ADD COLUMN blood_pressure_systolic INT NULL,
ADD COLUMN blood_pressure_diastolic INT NULL,
ADD COLUMN heart_rate INT NULL,
ADD COLUMN last_health_check TIMESTAMP NULL;

-- Tạo index để tối ưu truy vấn
CREATE INDEX idx_user_health_check ON user(last_health_check);
CREATE INDEX idx_user_blood_pressure ON user(blood_pressure_systolic, blood_pressure_diastolic); 