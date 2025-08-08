-- Add restricted_contents field to user table
-- This script adds the restricted_contents JSON field to store personalized content filtering keywords

ALTER TABLE `user` 
ADD COLUMN `restricted_contents` json DEFAULT NULL 
COMMENT 'Array of keywords that this elderly user should not watch' 
AFTER `last_health_check`; 