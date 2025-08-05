USE viegrand;

ALTER TABLE user ADD COLUMN private_key VARCHAR(8) NULL AFTER role; 