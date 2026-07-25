-- Migration: add the Cashier role and a default cashier account to an
-- EXISTING microfinance_lms database (run this instead of re-importing
-- schema.sql, which would wipe your data).
--
-- Usage:  mysql -u root microfinance_lms < database/migration_add_cashier.sql

USE microfinance_lms;

-- role_id 5 must match ROLE_CASHIER in config/config.php
INSERT INTO roles (role_id, role_name, description) VALUES
(5, 'Cashier', 'Disburses approved loans and collects repayment installments')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Default cashier. Username: cashier1  Password: Cashier@123
INSERT INTO users (role_id, full_name, username, email, phone, password_hash, status, created_by)
SELECT 5, 'Salma Khatun', 'cashier1', 'cashier1@microfinance.local', '01700000003',
       '$2y$10$8TbT5QsCxkxSIv14lLV3Tu99qr8GgIeVJGlRuf2/k4DCs7VZEg436', 'active', 1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'cashier1');

-- Clarify the Loan Officer's narrowed responsibilities
UPDATE roles
SET description = 'Registers customers, reviews their history and files loan applications'
WHERE role_id = 3;
