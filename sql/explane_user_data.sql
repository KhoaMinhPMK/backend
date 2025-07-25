-- Mở rộng bảng user thêm thông tin ngày bắt đầu và kết thúc gói Premium
ALTER TABLE user 
ADD COLUMN premium_start_date DATETIME NULL COMMENT 'Ngày bắt đầu gói Premium',
ADD COLUMN premium_end_date DATETIME NULL COMMENT 'Ngày kết thúc gói Premium';

-- Thêm index cho performance khi query theo premium dates
CREATE INDEX idx_user_premium_dates ON user(premium_start_date, premium_end_date);

-- Thêm index cho premium_status để optimize queries
CREATE INDEX idx_user_premium_status ON user(premium_status);

-- Update existing premium users với default dates (nếu có)
-- Giả sử user có premium_status = 1 thì set start_date = created_at, end_date = created_at + 30 days
UPDATE user 
SET 
    premium_start_date = created_at,
    premium_end_date = DATE_ADD(created_at, INTERVAL 30 DAY)
WHERE premium_status = 1 AND premium_start_date IS NULL;

-- Thêm comment cho bảng để documenting
ALTER TABLE user COMMENT = 'Bảng thông tin người dùng với hỗ trợ gói Premium có thời hạn';
