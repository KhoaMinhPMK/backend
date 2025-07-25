-- Thêm các fields y tế vào user table
-- Script: add_health_fields.sql

-- Thêm field hypertension (tăng huyết áp)
SET @count_hypertension = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'user' 
    AND COLUMN_NAME = 'hypertension'
);

SET @sql_hypertension = IF(@count_hypertension = 0, 
    'ALTER TABLE user ADD COLUMN hypertension TINYINT(1) DEFAULT 0 COMMENT "Hypertension status (0: No, 1: Yes)"',
    'SELECT "Column hypertension already exists" as message'
);

PREPARE stmt FROM @sql_hypertension;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Thêm field heart_disease (bệnh tim)
SET @count_heart = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'user' 
    AND COLUMN_NAME = 'heart_disease'
);

SET @sql_heart = IF(@count_heart = 0, 
    'ALTER TABLE user ADD COLUMN heart_disease TINYINT(1) DEFAULT 0 COMMENT "Heart disease status (0: No, 1: Yes)"',
    'SELECT "Column heart_disease already exists" as message'
);

PREPARE stmt FROM @sql_heart;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Thêm field ever_married (đã kết hôn)
SET @count_married = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'user' 
    AND COLUMN_NAME = 'ever_married'
);

SET @sql_married = IF(@count_married = 0, 
    'ALTER TABLE user ADD COLUMN ever_married ENUM("Yes", "No") DEFAULT "No" COMMENT "Whether the individual has ever been married (Yes or No)"',
    'SELECT "Column ever_married already exists" as message'
);

PREPARE stmt FROM @sql_married;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Thêm field work_type (loại công việc)
SET @count_work = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'user' 
    AND COLUMN_NAME = 'work_type'
);

SET @sql_work = IF(@count_work = 0, 
    'ALTER TABLE user ADD COLUMN work_type ENUM("Private", "Self-employed", "Govt_job", "children", "Never_worked") DEFAULT "Private" COMMENT "The type of work the individual is engaged in (e.g., Private, Self-employed)"',
    'SELECT "Column work_type already exists" as message'
);

PREPARE stmt FROM @sql_work;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Thêm field Residence_type (loại nơi cư trú)
SET @count_residence = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'user' 
    AND COLUMN_NAME = 'residence_type'
);

SET @sql_residence = IF(@count_residence = 0, 
    'ALTER TABLE user ADD COLUMN residence_type ENUM("Urban", "Rural") DEFAULT "Urban" COMMENT "The type of area where the individual resides (Urban or Rural)"',
    'SELECT "Column residence_type already exists" as message'
);

PREPARE stmt FROM @sql_residence;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Thêm field avg_glucose_level (mức glucose trung bình)
SET @count_glucose = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'user' 
    AND COLUMN_NAME = 'avg_glucose_level'
);

SET @sql_glucose = IF(@count_glucose = 0, 
    'ALTER TABLE user ADD COLUMN avg_glucose_level DECIMAL(5,2) DEFAULT NULL COMMENT "The individual''s average glucose level (in mg/dL)"',
    'SELECT "Column avg_glucose_level already exists" as message'
);

PREPARE stmt FROM @sql_glucose;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Thêm field bmi (chỉ số BMI)
SET @count_bmi = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'user' 
    AND COLUMN_NAME = 'bmi'
);

SET @sql_bmi = IF(@count_bmi = 0, 
    'ALTER TABLE user ADD COLUMN bmi DECIMAL(4,2) DEFAULT NULL COMMENT "The individual''s Body Mass Index (BMI)"',
    'SELECT "Column bmi already exists" as message'
);

PREPARE stmt FROM @sql_bmi;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Thêm field smoking_status (tình trạng hút thuốc)
SET @count_smoking = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'user' 
    AND COLUMN_NAME = 'smoking_status'
);

SET @sql_smoking = IF(@count_smoking = 0, 
    'ALTER TABLE user ADD COLUMN smoking_status ENUM("formerly smoked", "never smoked", "smokes", "Unknown") DEFAULT "never smoked" COMMENT "The smoking status of the individual (e.g., never smoked, formerly smoked, smokes)"',
    'SELECT "Column smoking_status already exists" as message'
);

PREPARE stmt FROM @sql_smoking;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Thêm field stroke (đột quỵ)
SET @count_stroke = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'user' 
    AND COLUMN_NAME = 'stroke'
);

SET @sql_stroke = IF(@count_stroke = 0, 
    'ALTER TABLE user ADD COLUMN stroke TINYINT(1) DEFAULT 0 COMMENT "Whether the individual has experienced a stroke (0: No, 1: Yes)"',
    'SELECT "Column stroke already exists" as message'
);

PREPARE stmt FROM @sql_stroke;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Thêm field height (chiều cao)
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

-- Thêm field weight (cân nặng)
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

-- Thêm indexes cho các fields y tế quan trọng
SET @index_health_count = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'user' 
    AND INDEX_NAME = 'idx_user_health_conditions'
);

SET @index_health_sql = IF(@index_health_count = 0, 
    'ALTER TABLE user ADD INDEX idx_user_health_conditions (hypertension, heart_disease, stroke)',
    'SELECT "Index idx_user_health_conditions already exists" as message'
);

PREPARE stmt FROM @index_health_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Hiển thị kết quả
SELECT 'Health fields added to user table successfully' as result; 