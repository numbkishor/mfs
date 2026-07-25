
DROP DATABASE IF EXISTS microfinance_lms;
CREATE DATABASE microfinance_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE microfinance_lms;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;


-- Table: roles

CREATE TABLE roles (
    role_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_name   VARCHAR(30) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- Table: users  (Administrator, Manager, Loan Officer, Customer login)

CREATE TABLE users (
    user_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id        INT UNSIGNED NOT NULL,
    full_name      VARCHAR(120) NOT NULL,
    username       VARCHAR(60) NOT NULL UNIQUE,
    email          VARCHAR(120) NOT NULL UNIQUE,
    phone          VARCHAR(20) DEFAULT NULL,
    password_hash  VARCHAR(255) NOT NULL,
    status         ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    remember_token VARCHAR(255) DEFAULT NULL,
    last_login_at  DATETIME DEFAULT NULL,
    created_by     INT UNSIGNED DEFAULT NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(role_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_users_created_by FOREIGN KEY (created_by) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_users_role (role_id),
    INDEX idx_users_status (status)
) ENGINE=InnoDB;


-- Table: customers (profile info; linked 1-1 to a users row with the
-- Customer role, created only by a Loan Officer/Manager/Admin)

CREATE TABLE customers (
    customer_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED NOT NULL UNIQUE,
    customer_code    VARCHAR(20) NOT NULL UNIQUE,
    national_id      VARCHAR(40) NOT NULL UNIQUE,
    date_of_birth    DATE NOT NULL,
    gender           ENUM('male','female','other') NOT NULL,
    address_line     VARCHAR(255) NOT NULL,
    city             VARCHAR(100) NOT NULL,
    occupation       VARCHAR(100) DEFAULT NULL,
    monthly_income   DECIMAL(14,2) DEFAULT NULL,
    status           ENUM('active','inactive','blacklisted') NOT NULL DEFAULT 'active',
    registered_by    INT UNSIGNED NOT NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_customers_user FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_customers_registered_by FOREIGN KEY (registered_by) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_customers_income CHECK (monthly_income IS NULL OR monthly_income >= 0),
    INDEX idx_customers_status (status),
    INDEX idx_customers_city (city)
) ENGINE=InnoDB;


-- Table: loan_types
 

CREATE TABLE loan_types (
    loan_type_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type_name        VARCHAR(100) NOT NULL UNIQUE,
    description      VARCHAR(255) DEFAULT NULL,
    interest_rate    DECIMAL(5,2) NOT NULL COMMENT 'Annual % rate',
    min_amount       DECIMAL(14,2) NOT NULL,
    max_amount       DECIMAL(14,2) NOT NULL,
    min_duration_months INT UNSIGNED NOT NULL,
    max_duration_months INT UNSIGNED NOT NULL,
    processing_fee_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    status           ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_loan_types_amounts CHECK (max_amount >= min_amount),
    CONSTRAINT chk_loan_types_duration CHECK (max_duration_months >= min_duration_months),
    CONSTRAINT chk_loan_types_rate CHECK (interest_rate >= 0)
) ENGINE=InnoDB;


-- Table: loan_applications



CREATE TABLE loan_applications (
    application_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_code  VARCHAR(20) NOT NULL UNIQUE,
    customer_id        INT UNSIGNED NOT NULL,
    loan_type_id        INT UNSIGNED NOT NULL,
    requested_amount   DECIMAL(14,2) NOT NULL,
    requested_duration_months INT UNSIGNED NOT NULL,
    purpose             VARCHAR(255) DEFAULT NULL,
    status               ENUM('pending','approved','rejected','returned') NOT NULL DEFAULT 'pending',
    review_notes         VARCHAR(500) DEFAULT NULL,
    submitted_by         INT UNSIGNED NOT NULL COMMENT 'user_id of officer who filed it',
    reviewed_by           INT UNSIGNED DEFAULT NULL,
    reviewed_at            DATETIME DEFAULT NULL,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_apps_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_apps_loan_type FOREIGN KEY (loan_type_id) REFERENCES loan_types(loan_type_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_apps_submitted_by FOREIGN KEY (submitted_by) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_apps_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_apps_amount CHECK (requested_amount > 0),
    CONSTRAINT chk_apps_duration CHECK (requested_duration_months > 0),
    INDEX idx_apps_status (status),
    INDEX idx_apps_customer (customer_id)
) ENGINE=InnoDB;


-- Table: loans (created once an application is approved)


CREATE TABLE loans (
    loan_id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_code          VARCHAR(20) NOT NULL UNIQUE,
    application_id     INT UNSIGNED NOT NULL UNIQUE,
    customer_id        INT UNSIGNED NOT NULL,
    loan_type_id       INT UNSIGNED NOT NULL,
    principal_amount   DECIMAL(14,2) NOT NULL,
    interest_rate      DECIMAL(5,2) NOT NULL COMMENT 'Locked-in annual % at approval time',
    duration_months    INT UNSIGNED NOT NULL,
    emi_amount         DECIMAL(14,2) NOT NULL,
    start_date         DATE NOT NULL,
    end_date           DATE NOT NULL,
    status             ENUM('active','closed','defaulted','cancelled') NOT NULL DEFAULT 'active',
    approved_by        INT UNSIGNED NOT NULL,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_loans_application FOREIGN KEY (application_id) REFERENCES loan_applications(application_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_loans_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_loans_loan_type FOREIGN KEY (loan_type_id) REFERENCES loan_types(loan_type_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_loans_approved_by FOREIGN KEY (approved_by) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_loans_principal CHECK (principal_amount > 0),
    CONSTRAINT chk_loans_dates CHECK (end_date > start_date),
    INDEX idx_loans_status (status),
    INDEX idx_loans_customer (customer_id)
) ENGINE=InnoDB;


-- Table: disbursements (money paid out to the customer for a loan)


CREATE TABLE disbursements (
    disbursement_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id             INT UNSIGNED NOT NULL UNIQUE,
    disbursed_amount    DECIMAL(14,2) NOT NULL,
    disbursement_date   DATE NOT NULL,
    method               ENUM('cash','bank_transfer','mobile_wallet','cheque') NOT NULL DEFAULT 'bank_transfer',
    reference_no          VARCHAR(60) DEFAULT NULL,
    disbursed_by            INT UNSIGNED NOT NULL,
    remarks                  VARCHAR(255) DEFAULT NULL,
    created_at                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_disb_loan FOREIGN KEY (loan_id) REFERENCES loans(loan_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_disb_by FOREIGN KEY (disbursed_by) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_disb_amount CHECK (disbursed_amount > 0)
) ENGINE=InnoDB;


-- Table: repayment_schedules (EMI schedule generated per loan)


CREATE TABLE repayment_schedules (
    schedule_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id           INT UNSIGNED NOT NULL,
    installment_no    INT UNSIGNED NOT NULL,
    due_date          DATE NOT NULL,
    principal_due     DECIMAL(14,2) NOT NULL,
    interest_due      DECIMAL(14,2) NOT NULL,
    total_due         DECIMAL(14,2) NOT NULL,
    amount_paid       DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    status             ENUM('pending','partial','paid','overdue') NOT NULL DEFAULT 'pending',
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sched_loan FOREIGN KEY (loan_id) REFERENCES loans(loan_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT uq_sched_loan_installment UNIQUE (loan_id, installment_no),
    CONSTRAINT chk_sched_total CHECK (total_due = principal_due + interest_due),
    INDEX idx_sched_status (status),
    INDEX idx_sched_due_date (due_date)
) ENGINE=InnoDB;


-- Table: payments (actual money received against a schedule line)



CREATE TABLE payments (
    payment_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_code     VARCHAR(20) NOT NULL UNIQUE,
    loan_id          INT UNSIGNED NOT NULL,
    schedule_id      INT UNSIGNED NOT NULL,
    amount_paid      DECIMAL(14,2) NOT NULL,
    payment_date     DATE NOT NULL,
    payment_method   ENUM('cash','bank_transfer','mobile_wallet','cheque') NOT NULL DEFAULT 'cash',
    reference_no     VARCHAR(60) DEFAULT NULL,
    received_by      INT UNSIGNED NOT NULL,
    remarks          VARCHAR(255) DEFAULT NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pay_loan FOREIGN KEY (loan_id) REFERENCES loans(loan_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pay_schedule FOREIGN KEY (schedule_id) REFERENCES repayment_schedules(schedule_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pay_received_by FOREIGN KEY (received_by) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_pay_amount CHECK (amount_paid > 0),
    INDEX idx_pay_date (payment_date),
    INDEX idx_pay_loan (loan_id)
) ENGINE=InnoDB;


-- SEED DATA


INSERT INTO roles (role_name, description) VALUES
('Administrator', 'Full system access'),
('Manager', 'Branch-level oversight and approvals'),
('Loan Officer', 'Registers customers, reviews their history and files loan applications'),
('Customer', 'Restricted portal for borrowers to view their own data'),
('Cashier', 'Disburses approved loans and collects repayment installments');

-- Default administrator. Username: admin  Password: Admin@123
-- (bcrypt hash below is verifiable with PHP's password_verify())
INSERT INTO users (role_id, full_name, username, email, phone, password_hash, status) VALUES
(1, 'System Administrator', 'admin', 'admin@microfinance.local', '01700000000',
 '$2b$10$56Y6I9bG/ACSWTPAqkiK/OIF4/wISqa0QLxk8g1thvAPPxVD/zE.q', 'active');

-- Sample Manager. Username: manager1  Password: Manager@123
INSERT INTO users (role_id, full_name, username, email, phone, password_hash, status, created_by) VALUES
(2, 'Rahim Chowdhury', 'manager1', 'manager1@microfinance.local', '01700000001',
 '$2b$10$1vKI4JJDz3RrBp6XgNy0nutWyQvMMdj0ju1guIGc6451AIdOYYkeG', 'active', 1);

-- Sample Loan Officer. Username: officer1  Password: Officer@123
INSERT INTO users (role_id, full_name, username, email, phone, password_hash, status, created_by) VALUES
(3, 'Karim Hasan', 'officer1', 'officer1@microfinance.local', '01700000002',
 '$2b$10$PxCz0h.DjqS690xHAk/HPe15GWQPeJtVM53wHEm7StWV5CDq4XQpe', 'active', 1);

-- Sample loan types
INSERT INTO loan_types (type_name, description, interest_rate, min_amount, max_amount, min_duration_months, max_duration_months, processing_fee_pct) VALUES
('Micro Business Loan', 'Working capital for small businesses', 12.00, 5000.00, 200000.00, 3, 24, 1.00),
('Agriculture Loan', 'Seasonal loans for farming inputs and equipment', 9.50, 3000.00, 150000.00, 3, 18, 0.50),
('Emergency Loan', 'Short-term loan for urgent household needs', 15.00, 1000.00, 30000.00, 1, 6, 0.00),
('Home Improvement Loan', 'Renovation and small construction works', 11.00, 10000.00, 300000.00, 6, 36, 1.50);

-- Sample customer user accounts + profiles (created by officer1, user_id 3)
INSERT INTO users (role_id, full_name, username, email, phone, password_hash, status, created_by) VALUES
(4, 'Fatema Begum', 'fatema.begum', 'fatema.begum@example.com', '01711111111',
 '$2b$10$u1F5waIMoEuYL2EOpgxlIerWWt45r6xW7FWwTTOJX/Q2.WliPLaEq', 'active', 3),
(4, 'Abdul Karim', 'abdul.karim', 'abdul.karim@example.com', '01722222222',
 '$2b$10$u1F5waIMoEuYL2EOpgxlIerWWt45r6xW7FWwTTOJX/Q2.WliPLaEq', 'active', 3),
(4, 'Nasrin Akter', 'nasrin.akter', 'nasrin.akter@example.com', '01733333333',
 '$2b$10$u1F5waIMoEuYL2EOpgxlIerWWt45r6xW7FWwTTOJX/Q2.WliPLaEq', 'active', 3);
-- Default password for all sample customers above: Customer@123

INSERT INTO customers (user_id, customer_code, national_id, date_of_birth, gender, address_line, city, occupation, monthly_income, status, registered_by) VALUES
(4, 'CUS-0001', '1990123456789', '1990-04-12', 'female', 'House 12, Road 4, Mirpur', 'Dhaka', 'Tailor', 18000.00, 'active', 3),
(5, 'CUS-0002', '1988987654321', '1988-11-02', 'male', 'Village Bashail, Ward 3', 'Tangail', 'Farmer', 15000.00, 'active', 3),
(6, 'CUS-0003', '1995567891234', '1995-06-25', 'female', 'House 5, Sector 7, Uttara', 'Dhaka', 'Grocery Shop Owner', 22000.00, 'active', 3);

-- Sample Cashier. Username: cashier1  Password: Cashier@123
-- (inserted after the customer users so their hard-coded user_ids 4-6 stay valid)
INSERT INTO users (role_id, full_name, username, email, phone, password_hash, status, created_by) VALUES
(5, 'Salma Khatun', 'cashier1', 'cashier1@microfinance.local', '01700000003',
 '$2y$10$8TbT5QsCxkxSIv14lLV3Tu99qr8GgIeVJGlRuf2/k4DCs7VZEg436', 'active', 1);

-- Sample loan application (approved) with loan, disbursement, schedule, payments
INSERT INTO loan_applications (application_code, customer_id, loan_type_id, requested_amount, requested_duration_months, purpose, status, review_notes, submitted_by, reviewed_by, reviewed_at) VALUES
('APP-000001', 1, 1, 50000.00, 12, 'Purchase of sewing machines and fabric stock', 'approved', 'Verified income and business plan. Approved.', 3, 2, '2026-01-10 10:15:00'),
('APP-000002', 2, 2, 30000.00, 6, 'Purchase of seeds and fertilizer for the season', 'pending', NULL, 3, NULL, NULL),
('APP-000003', 3, 3, 8000.00, 3, 'Emergency medical expense', 'rejected', 'Insufficient repayment capacity at this time.', 3, 2, '2026-01-12 09:00:00');

INSERT INTO loans (loan_code, application_id, customer_id, loan_type_id, principal_amount, interest_rate, duration_months, emi_amount, start_date, end_date, status, approved_by) VALUES
('LN-000001', 1, 1, 1, 50000.00, 12.00, 12, 4443.05, '2026-01-15', '2027-01-15', 'active', 2);

INSERT INTO disbursements (loan_id, disbursed_amount, disbursement_date, method, reference_no, disbursed_by, remarks) VALUES
(1, 50000.00, '2026-01-15', 'bank_transfer', 'TRX-20260115-001', 3, 'Disbursed in full to customer bank account');

-- 12-month EMI schedule for loan 1 (flat + reducing hybrid simplified as equal principal + interest on outstanding)
INSERT INTO repayment_schedules (loan_id, installment_no, due_date, principal_due, interest_due, total_due, amount_paid, status) VALUES
(1, 1,  '2026-02-15', 3833.33, 500.00, 4333.33, 4333.33, 'paid'),
(1, 2,  '2026-03-15', 3833.33, 461.67, 4295.00, 4295.00, 'paid'),
(1, 3,  '2026-04-15', 3833.33, 423.33, 4256.66, 0.00, 'overdue'),
(1, 4,  '2026-05-15', 3833.33, 385.00, 4218.33, 0.00, 'pending'),
(1, 5,  '2026-06-15', 3833.33, 346.67, 4180.00, 0.00, 'pending'),
(1, 6,  '2026-07-15', 3833.33, 308.33, 4141.66, 0.00, 'pending'),
(1, 7,  '2026-08-15', 3833.33, 270.00, 4103.33, 0.00, 'pending'),
(1, 8,  '2026-09-15', 3833.33, 231.67, 4065.00, 0.00, 'pending'),
(1, 9,  '2026-10-15', 3833.33, 193.33, 4026.66, 0.00, 'pending'),
(1, 10, '2026-11-15', 3833.33, 155.00, 3988.33, 0.00, 'pending'),
(1, 11, '2026-12-15', 3833.33, 116.67, 3950.00, 0.00, 'pending'),
(1, 12, '2027-01-15', 3833.37, 78.33, 3911.70, 0.00, 'pending');

INSERT INTO payments (payment_code, loan_id, schedule_id, amount_paid, payment_date, payment_method, reference_no, received_by, remarks) VALUES
('PAY-000001', 1, 1, 4333.33, '2026-02-14', 'mobile_wallet', 'MW-0001', 3, 'Installment 1 paid on time'),
('PAY-000002', 1, 2, 4295.00, '2026-03-13', 'mobile_wallet', 'MW-0002', 3, 'Installment 2 paid on time');
