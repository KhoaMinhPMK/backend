USE viegrand;

-- Add private_key field to user table
-- This field will store the 8-character random string for user identification

ALTER TABLE `user` ADD COLUMN `private_key` VARCHAR(8) NULL AFTER `role`;

-- Add index for better performance when searching by private_key
CREATE INDEX `idx_private_key` ON `user` (`private_key`);

-- Add unique constraint to ensure no duplicate keys
ALTER TABLE `user` ADD UNIQUE INDEX `uk_private_key` (`private_key`);
