-- Update the category column to hold the values from gender
UPDATE `products` SET `category` = `gender`;

-- Drop the gender column as it is merged
ALTER TABLE `products` DROP COLUMN `gender`;
