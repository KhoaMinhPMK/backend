USE viegrand;

UPDATE user SET private_key = LPAD(FLOOR(RAND() * 100000000), 8, '0') WHERE private_key IS NULL; 