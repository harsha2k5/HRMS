-- ==========================================================
-- HRMS DATABASE SCHEMA (MySQL 8+ / MariaDB)
-- Normalized 26-Table Production Database Schema
-- ==========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Roles Table
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `display_name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Permissions Table
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `module` VARCHAR(50) NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255),
    UNIQUE KEY `unique_module_action` (`module`, `action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Role Permissions Mapping Table
DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
    `role_id` INT NOT NULL,
    `permission_id` INT NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Users Table
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role_id` INT NOT NULL,
    `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    `avatar` VARCHAR(255) DEFAULT NULL,
    `last_login_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Departments Table
DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `description` TEXT,
    `head_id` INT DEFAULT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Designations Table
DROP TABLE IF EXISTS `designations`;
CREATE TABLE `designations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `department_id` INT NOT NULL,
    `title` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `min_salary` DECIMAL(12,2) DEFAULT 0.00,
    `max_salary` DECIMAL(12,2) DEFAULT 0.00,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_desig_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Employees Table
DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `employee_code` VARCHAR(30) NOT NULL UNIQUE,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `phone` VARCHAR(25),
    `date_of_birth` DATE,
    `gender` ENUM('male', 'female', 'other') DEFAULT 'male',
    `address` TEXT,
    `emergency_contact` VARCHAR(100),
    `department_id` INT,
    `designation_id` INT,
    `manager_id` INT DEFAULT NULL,
    `joining_date` DATE NOT NULL,
    `employment_type` ENUM('full_time', 'part_time', 'contract', 'intern') DEFAULT 'full_time',
    `employment_status` ENUM('active', 'on_leave', 'probation', 'terminated', 'resigned') DEFAULT 'active',
    `basic_salary` DECIMAL(12,2) DEFAULT 0.00,
    `bank_name` VARCHAR(100),
    `bank_account_no` VARCHAR(50),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_emp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_emp_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_emp_desig` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_emp_manager` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add Department Head Foreign Key now that employees table exists
ALTER TABLE `departments` ADD CONSTRAINT `fk_dept_head` FOREIGN KEY (`head_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

-- 8. Attendance Table
DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT NOT NULL,
    `date` DATE NOT NULL,
    `check_in` TIME DEFAULT NULL,
    `check_out` TIME DEFAULT NULL,
    `working_hours` DECIMAL(5,2) DEFAULT 0.00,
    `overtime_hours` DECIMAL(5,2) DEFAULT 0.00,
    `status` ENUM('present', 'absent', 'late', 'half_day', 'on_leave') DEFAULT 'present',
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_emp_date` (`employee_id`, `date`),
    CONSTRAINT `fk_att_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Leave Types Table
DROP TABLE IF EXISTS `leave_types`;
CREATE TABLE `leave_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `max_days_per_year` INT NOT NULL DEFAULT 12,
    `is_paid` TINYINT(1) DEFAULT 1,
    `description` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Leave Balances Table
DROP TABLE IF EXISTS `leave_balances`;
CREATE TABLE `leave_balances` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT NOT NULL,
    `leave_type_id` INT NOT NULL,
    `year` INT NOT NULL,
    `total_days` INT NOT NULL,
    `used_days` INT NOT NULL DEFAULT 0,
    `pending_days` INT NOT NULL DEFAULT 0,
    UNIQUE KEY `unique_emp_leave_year` (`employee_id`, `leave_type_id`, `year`),
    CONSTRAINT `fk_lb_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_lb_lt` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Leave Requests Table
DROP TABLE IF EXISTS `leave_requests`;
CREATE TABLE `leave_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT NOT NULL,
    `leave_type_id` INT NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `total_days` INT NOT NULL,
    `reason` TEXT NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    `manager_comment` TEXT,
    `reviewed_by` INT DEFAULT NULL,
    `reviewed_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_lr_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_lr_lt` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_lr_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Payroll Table
DROP TABLE IF EXISTS `payroll`;
CREATE TABLE `payroll` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT NOT NULL,
    `month` INT NOT NULL,
    `year` INT NOT NULL,
    `basic_salary` DECIMAL(12,2) NOT NULL,
    `total_allowances` DECIMAL(12,2) DEFAULT 0.00,
    `total_deductions` DECIMAL(12,2) DEFAULT 0.00,
    `net_salary` DECIMAL(12,2) NOT NULL,
    `payment_status` ENUM('draft', 'processed', 'paid') DEFAULT 'draft',
    `payment_date` DATE DEFAULT NULL,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_emp_month_year` (`employee_id`, `month`, `year`),
    CONSTRAINT `fk_payroll_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Payroll Items Table
DROP TABLE IF EXISTS `payroll_items`;
CREATE TABLE `payroll_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `payroll_id` INT NOT NULL,
    `item_type` ENUM('allowance', 'deduction') NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    CONSTRAINT `fk_pi_payroll` FOREIGN KEY (`payroll_id`) REFERENCES `payroll` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Documents Table
DROP TABLE IF EXISTS `documents`;
CREATE TABLE `documents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT NOT NULL,
    `category` ENUM('contracts', 'certificates', 'identity', 'employee_documents', 'policies', 'payslips', 'other') DEFAULT 'employee_documents',
    `title` VARCHAR(150) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_size` INT DEFAULT 0,
    `mime_type` VARCHAR(100),
    `uploaded_by` INT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_doc_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_doc_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Announcements Table
DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `content` TEXT NOT NULL,
    `target_role` VARCHAR(50) DEFAULT 'all',
    `is_pinned` TINYINT(1) DEFAULT 0,
    `author_id` INT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_anno_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Notifications Table
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `type` VARCHAR(50) DEFAULT 'info',
    `link` VARCHAR(255) DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Performance Goals Table
DROP TABLE IF EXISTS `performance_goals`;
CREATE TABLE `performance_goals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT,
    `start_date` DATE NOT NULL,
    `target_date` DATE NOT NULL,
    `progress` INT DEFAULT 0,
    `status` ENUM('not_started', 'in_progress', 'completed', 'behind') DEFAULT 'in_progress',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pg_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Performance Reviews Table
DROP TABLE IF EXISTS `performance_reviews`;
CREATE TABLE `performance_reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT NOT NULL,
    `reviewer_id` INT NOT NULL,
    `review_period` VARCHAR(50) NOT NULL,
    `rating` DECIMAL(3,1) NOT NULL,
    `manager_feedback` TEXT,
    `employee_self_review` TEXT,
    `status` ENUM('draft', 'submitted', 'completed') DEFAULT 'completed',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pr_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pr_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Job Openings Table
DROP TABLE IF EXISTS `job_openings`;
CREATE TABLE `job_openings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `department_id` INT NOT NULL,
    `designation_id` INT,
    `job_type` ENUM('full_time', 'part_time', 'contract', 'remote') DEFAULT 'full_time',
    `experience_level` VARCHAR(50) DEFAULT 'Mid-Level',
    `vacancies` INT DEFAULT 1,
    `description` TEXT,
    `requirements` TEXT,
    `status` ENUM('open', 'paused', 'closed') DEFAULT 'open',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_jo_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_jo_desig` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Candidates Table
DROP TABLE IF EXISTS `candidates`;
CREATE TABLE `candidates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `job_opening_id` INT NOT NULL,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(25),
    `resume_path` VARCHAR(255),
    `status` ENUM('active', 'hired', 'rejected') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_cand_job` FOREIGN KEY (`job_opening_id`) REFERENCES `job_openings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Applications (Recruitment Pipeline) Table
DROP TABLE IF EXISTS `applications`;
CREATE TABLE `applications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `candidate_id` INT NOT NULL,
    `job_opening_id` INT NOT NULL,
    `stage` ENUM('applied', 'screening', 'interview', 'selected', 'hired', 'rejected') DEFAULT 'applied',
    `rating` INT DEFAULT 3,
    `notes` TEXT,
    `applied_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_app_cand` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_app_job` FOREIGN KEY (`job_opening_id`) REFERENCES `job_openings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Interviews Table
DROP TABLE IF EXISTS `interviews`;
CREATE TABLE `interviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `candidate_id` INT NOT NULL,
    `job_opening_id` INT NOT NULL,
    `interviewer_id` INT NOT NULL,
    `interview_date` DATE NOT NULL,
    `interview_time` TIME NOT NULL,
    `location_or_link` VARCHAR(255),
    `status` ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    `feedback` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_int_cand` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_int_job` FOREIGN KEY (`job_opening_id`) REFERENCES `job_openings` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_int_user` FOREIGN KEY (`interviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. Holidays Table
DROP TABLE IF EXISTS `holidays`;
CREATE TABLE `holidays` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `date` DATE NOT NULL UNIQUE,
    `is_recurring` TINYINT(1) DEFAULT 1,
    `description` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. Company Events Table
DROP TABLE IF EXISTS `company_events`;
CREATE TABLE `company_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `event_date` DATE NOT NULL,
    `start_time` TIME,
    `end_time` TIME,
    `location` VARCHAR(150),
    `description` TEXT,
    `department_id` INT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ce_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 25. Audit Logs Table
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` INT DEFAULT NULL,
    `details` TEXT,
    `ip_address` VARCHAR(45),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 26. Company Settings Table
DROP TABLE IF EXISTS `company_settings`;
CREATE TABLE `company_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
