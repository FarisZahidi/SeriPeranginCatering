-- Modify audit_logs table to store user names instead of user IDs
-- This ensures audit logs remain intact even after users are deleted

-- First, add a new column for user name
ALTER TABLE `audit_logs` ADD COLUMN `user_name` varchar(100) NULL AFTER `user_id`;

-- Update existing audit logs with user names
UPDATE `audit_logs` al 
JOIN `users` u ON al.user_id = u.user_id 
SET al.user_name = u.name;

-- Drop the foreign key constraint since we won't need it anymore
ALTER TABLE `audit_logs` DROP FOREIGN KEY `audit_logs_ibfk_1`;

-- Drop the user_id column since we're replacing it with user_name
ALTER TABLE `audit_logs` DROP COLUMN `user_id`;

-- Add index on user_name for better performance
ALTER TABLE `audit_logs` ADD INDEX `idx_user_name` (`user_name`); 