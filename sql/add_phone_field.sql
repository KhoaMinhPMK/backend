-- Thêm field phone vào user table
-- Script: add_phone_field.sql

-- Kiểm tra xem column phone đã tồn tại chưa và thêm nếu chưa có
SET @count = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'user' 
    AND COLUMN_NAME = 'phone'
);

SET @sql = IF(@count = 0, 
    'ALTER TABLE user ADD COLUMN phone VARCHAR(15) NULL COMMENT "Số điện thoại của user"',
    'SELECT "Column phone already exists" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Thêm index cho phone field để tăng hiệu suất truy vấn
SET @index_count = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'user' 
    AND INDEX_NAME = 'idx_user_phone'
);

SET @index_sql = IF(@index_count = 0, 
    'ALTER TABLE user ADD INDEX idx_user_phone (phone)',
    'SELECT "Index idx_user_phone already exists" as message'
);

PREPARE stmt FROM @index_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Hiển thị kết quả
SELECT 'Phone field and index added to user table successfully' as result; 