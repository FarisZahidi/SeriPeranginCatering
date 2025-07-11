ALTER TABLE audit_logs MODIFY item_id INT NULL;

ALTER TABLE audit_logs
  ADD CONSTRAINT YOUR_CONSTRAINT_NAME
  FOREIGN KEY (item_id) REFERENCES inventory(item_id) ON DELETE SET NULL;

ALTER TABLE `audit_logs` MODIFY `user_id` int NULL;

ALTER TABLE `audit_logs` 
ADD CONSTRAINT `audit_logs_ibfk_1` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
ON DELETE SET NULL; 