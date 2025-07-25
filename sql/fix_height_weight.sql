-- Fix height and weight columns in user table
-- Script: fix_height_weight.sql

-- Thêm field height
SET @count_height = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'user' 
    AND COLUMN_NAME = 'height'
);

SET @sql_height = IF(@count_height = 0, 
    'ALTER TABLE user ADD COLUMN height DECIMAL(5,2) DEFAULT NULL COMMENT "Height in centimeters"',
    'SELECT "Column height already exists" as message'
);

PREPARE stmt FROM @sql_height;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Thêm field weight
SET @count_weight = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'user' 
    AND COLUMN_NAME = 'weight'
);

SET @sql_weight = IF(@count_weight = 0, 
    'ALTER TABLE user ADD COLUMN weight DECIMAL(5,2) DEFAULT NULL COMMENT "Weight in kilograms"',
    'SELECT "Column weight already exists" as message'
);

PREPARE stmt FROM @sql_weight;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Height and weight columns added successfully' as result; 