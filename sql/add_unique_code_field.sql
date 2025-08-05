-- Add unique_code field to user table
-- This field will store the 8-character random string for user identification

ALTER TABLE `user` ADD COLUMN `unique_code` VARCHAR(8) NULL AFTER `role`;

-- Add index for better performance when searching by unique_code
CREATE INDEX `idx_unique_code` ON `user` (`unique_code`);

-- Add unique constraint to ensure no duplicate codes
ALTER TABLE `user` ADD UNIQUE INDEX `uk_unique_code` (`unique_code`); 