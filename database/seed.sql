-- ==========================================================
-- HRMS DATABASE SEED DATA (MySQL 8+ / MariaDB)
-- Realistic Demonstration Data for All Modules & Roles
-- Default Password for all seed users: password123
-- Hash: $2y$10$fpW8feHIkKx/13WojYmK5undLR4Xb82J8DtchdFI6Y.gKXXUIegfS
-- ==========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Roles
INSERT INTO `roles` (`id`, `name`, `display_name`, `description`) VALUES
(1, 'super_admin', 'Super Administrator', 'Full system access and authority over all settings and modules'),
(2, 'hr_admin', 'HR Administrator', 'Manages employee lifecycles, payroll, attendance, leave and recruiting'),
(3, 'manager', 'Department Manager', 'Manages assigned team members, reviews leave, and provides feedback'),
(4, 'employee', 'Standard Employee', 'Self-service access to profile, attendance, leave, payslips, and documents');

-- 2. Permissions
INSERT INTO `permissions` (`id`, `module`, `action`, `description`) VALUES
(1, 'employees', 'view_all', 'View all employee directories and profiles'),
(2, 'employees', 'view_team', 'View assigned team members only'),
(3, 'employees', 'view_own', 'View self profile only'),
(4, 'employees', 'create', 'Register new employee records'),
(5, 'employees', 'edit', 'Modify existing employee records'),
(6, 'employees', 'delete', 'Deactivate employee records'),
(7, 'attendance', 'manage_all', 'View and adjust company-wide attendance'),
(8, 'attendance', 'clock', 'Clock in and out for self'),
(9, 'leave', 'manage_all', 'Review, approve, and reject any leave request'),
(10, 'leave', 'manage_team', 'Review team member leave requests'),
(11, 'leave', 'apply', 'Submit leave requests for self'),
(12, 'payroll', 'manage_all', 'Process payroll and view all compensation details'),
(13, 'payroll', 'view_own', 'View self payslips only'),
(14, 'recruitment', 'manage', 'Post jobs and evaluate candidates'),
(15, 'performance', 'manage_all', 'Manage company appraisals and goal frameworks'),
(16, 'performance', 'manage_team', 'Submit feedback and ratings for team members'),
(17, 'documents', 'manage', 'Upload and administer organizational documents'),
(18, 'settings', 'manage', 'Configure company settings and system users');

-- 3. Role Permissions
-- Super Admin gets everything (1 through 18)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9), (1, 11), (1, 12), (1, 13), (1, 14), (1, 15), (1, 16), (1, 17), (1, 18),
-- HR Admin
(2, 1), (2, 4), (2, 5), (2, 6), (2, 7), (2, 8), (2, 9), (2, 11), (2, 12), (2, 13), (2, 14), (2, 15), (2, 16), (2, 17),
-- Manager
(3, 2), (3, 8), (3, 10), (3, 11), (3, 13), (3, 16),
-- Employee
(4, 3), (4, 8), (4, 11), (4, 13);

-- 4. Users
-- password123
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role_id`, `status`, `avatar`) VALUES
(1, 'superadmin', 'admin@company.com', '$2y$10$fpW8feHIkKx/13WojYmK5undLR4Xb82J8DtchdFI6Y.gKXXUIegfS', 1, 'active', 'avatar-admin.png'),
(2, 'hradmin', 'hr@company.com', '$2y$10$fpW8feHIkKx/13WojYmK5undLR4Xb82J8DtchdFI6Y.gKXXUIegfS', 2, 'active', 'avatar-hr.png'),
(3, 'manager', 'manager@company.com', '$2y$10$fpW8feHIkKx/13WojYmK5undLR4Xb82J8DtchdFI6Y.gKXXUIegfS', 3, 'active', 'avatar-manager.png'),
(4, 'employee', 'employee@company.com', '$2y$10$fpW8feHIkKx/13WojYmK5undLR4Xb82J8DtchdFI6Y.gKXXUIegfS', 4, 'active', 'avatar-emp.png'),
(5, 'davidc', 'david.chen@company.com', '$2y$10$fpW8feHIkKx/13WojYmK5undLR4Xb82J8DtchdFI6Y.gKXXUIegfS', 4, 'active', 'avatar-5.png'),
(6, 'sarahj', 'sarah.j@company.com', '$2y$10$fpW8feHIkKx/13WojYmK5undLR4Xb82J8DtchdFI6Y.gKXXUIegfS', 4, 'active', 'avatar-6.png'),
(7, 'michaelt', 'michael.t@company.com', '$2y$10$fpW8feHIkKx/13WojYmK5undLR4Xb82J8DtchdFI6Y.gKXXUIegfS', 4, 'active', 'avatar-7.png'),
(8, 'sophiaw', 'sophia.w@company.com', '$2y$10$fpW8feHIkKx/13WojYmK5undLR4Xb82J8DtchdFI6Y.gKXXUIegfS', 4, 'active', 'avatar-8.png');

-- 5. Departments
INSERT INTO `departments` (`id`, `name`, `code`, `description`, `head_id`, `status`) VALUES
(1, 'Engineering & Tech', 'ENG', 'Product engineering, architecture, and technology systems', 3, 'active'),
(2, 'Human Resources', 'HR', 'Talent acquisition, employee welfare, and company culture', 2, 'active'),
(3, 'Finance & Accounting', 'FIN', 'Financial planning, accounting, audits, and compliance', 7, 'active'),
(4, 'Marketing & Growth', 'MKT', 'Brand presence, growth marketing, PR, and lead acquisition', 8, 'active'),
(5, 'Operations & Legal', 'OPS', 'Enterprise logistics, company compliance, and facilities', 1, 'active');

-- 6. Designations
INSERT INTO `designations` (`id`, `department_id`, `title`, `description`, `min_salary`, `max_salary`) VALUES
(1, 1, 'Senior Technical Lead', 'Technical leadership, architecture, and engineering management', 9000.00, 15000.00),
(2, 1, 'Software Engineer', 'Full-stack software engineering and application delivery', 5000.00, 9000.00),
(3, 1, 'QA Automation Engineer', 'Automated test suite maintenance and quality engineering', 4500.00, 8000.00),
(4, 2, 'HR Operations Manager', 'Head of people operations, compliance, and hiring', 6500.00, 11000.00),
(5, 2, 'HR Specialist', 'Recruitment screening, onboarding, and documentation', 4000.00, 7000.00),
(6, 3, 'Senior Financial Analyst', 'Financial models, quarterly forecasting, and payroll review', 5500.00, 9500.00),
(7, 4, 'Product Marketing Lead', 'Strategic positioning, campaign management, and content', 6000.00, 10000.00),
(8, 5, 'Chief Operating Officer', 'Executive operations, cross-department alignment, and strategy', 12000.00, 20000.00);

-- 7. Employees
INSERT INTO `employees` (`id`, `user_id`, `employee_code`, `first_name`, `last_name`, `email`, `phone`, `date_of_birth`, `gender`, `address`, `emergency_contact`, `department_id`, `designation_id`, `manager_id`, `joining_date`, `employment_type`, `employment_status`, `basic_salary`, `bank_name`, `bank_account_no`) VALUES
(1, 1, 'EMP-001', 'Alexander', 'Stone', 'admin@company.com', '+1 (555) 019-2831', '1985-04-12', 'male', '100 Executive Way, Suite 400, New York, NY', 'Rachel Stone (+1 555-019-9911)', 5, 8, NULL, '2021-01-15', 'full_time', 'active', 16500.00, 'JPMorgan Chase', 'CHAS9028172635'),
(2, 2, 'EMP-002', 'Victoria', 'Vance', 'hr@company.com', '+1 (555) 019-3742', '1990-08-23', 'female', '742 Evergreen Terrace, Brooklyn, NY', 'Mark Vance (+1 555-019-4422)', 2, 4, 1, '2021-03-01', 'full_time', 'active', 8500.00, 'Citibank N.A.', 'CITI8839201928'),
(3, 3, 'EMP-003', 'Marcus', 'Brody', 'manager@company.com', '+1 (555) 019-8472', '1988-11-05', 'male', '350 Silicon Blvd, Jersey City, NJ', 'Diana Brody (+1 555-019-5533)', 1, 1, 1, '2021-06-15', 'full_time', 'active', 11500.00, 'Wells Fargo', 'WELL7483920192'),
(4, 4, 'EMP-004', 'Elena', 'Rostova', 'employee@company.com', '+1 (555) 019-1029', '1994-02-17', 'female', '120 West 86th St, Apt 4B, New York, NY', 'Anna Rostova (+1 555-019-6644)', 1, 2, 3, '2022-02-10', 'full_time', 'active', 7200.00, 'Bank of America', 'BOFA1928374650'),
(5, 5, 'EMP-005', 'David', 'Chen', 'david.chen@company.com', '+1 (555) 019-5561', '1995-07-30', 'male', '88 Battery Park Pl, New York, NY', 'Ken Chen (+1 555-019-7755)', 1, 3, 3, '2022-05-20', 'full_time', 'active', 6200.00, 'JPMorgan Chase', 'CHAS4483920193'),
(6, 6, 'EMP-006', 'Sarah', 'Jenkins', 'sarah.j@company.com', '+1 (555) 019-6672', '1996-09-14', 'female', '45 Grand Army Plaza, Brooklyn, NY', 'Thomas Jenkins (+1 555-019-8866)', 2, 5, 2, '2023-01-09', 'full_time', 'active', 5400.00, 'Citibank N.A.', 'CITI3321984756'),
(7, 7, 'EMP-007', 'Michael', 'Torres', 'michael.t@company.com', '+1 (555) 019-7783', '1991-12-03', 'male', '512 Greenwich St, New York, NY', 'Maria Torres (+1 555-019-9977)', 3, 6, 1, '2022-09-01', 'full_time', 'active', 7800.00, 'Wells Fargo', 'WELL9928174635'),
(8, 8, 'EMP-008', 'Sophia', 'Williams', 'sophia.w@company.com', '+1 (555) 019-8894', '1993-05-28', 'female', '204 Madison Ave, New York, NY', 'Liam Williams (+1 555-019-1188)', 4, 7, 1, '2022-11-15', 'full_time', 'active', 7500.00, 'Bank of America', 'BOFA8837461920');

-- 8. Attendance (Realistic recent 7-day snapshot)
INSERT INTO `attendance` (`employee_id`, `date`, `check_in`, `check_out`, `working_hours`, `overtime_hours`, `status`, `notes`) VALUES
(4, '2026-09-10', '08:58:00', '17:32:00', 8.57, 0.57, 'present', 'On-time arrival'),
(4, '2026-09-11', '09:02:00', '18:05:00', 9.05, 1.05, 'present', 'Code sprint delivery'),
(4, '2026-09-14', '09:18:00', '17:45:00', 8.45, 0.00, 'late', 'Subway delays'),
(4, '2026-09-15', '08:55:00', '17:30:00', 8.58, 0.00, 'present', 'Standard day'),
(4, '2026-09-16', '08:50:00', '17:35:00', 8.75, 0.00, 'present', 'Deployment completed'),
(4, '2026-09-17', '08:54:00', NULL, 0.00, 0.00, 'present', 'Checked in today'),
(3, '2026-09-16', '08:45:00', '18:15:00', 9.50, 1.50, 'present', 'Team sprint planning'),
(3, '2026-09-17', '08:42:00', NULL, 0.00, 0.00, 'present', 'Active in office'),
(5, '2026-09-16', '09:00:00', '17:30:00', 8.50, 0.00, 'present', 'Test suite regression'),
(5, '2026-09-17', '08:58:00', NULL, 0.00, 0.00, 'present', 'Active in office'),
(2, '2026-09-17', '08:50:00', NULL, 0.00, 0.00, 'present', 'HR morning standup');

-- 9. Leave Types
INSERT INTO `leave_types` (`id`, `name`, `code`, `max_days_per_year`, `is_paid`, `description`) VALUES
(1, 'Annual Paid Leave', 'AL', 18, 1, 'Standard accrued personal annual vacation leave'),
(2, 'Medical / Sick Leave', 'SL', 12, 1, 'Leave taken for documented health or illness recovery'),
(3, 'Casual Leave', 'CL', 8, 1, 'Short unplanned personal matters or urgent appointments'),
(4, 'Parental / Maternity Leave', 'PL', 60, 1, 'Maternity or paternity parental bonding leave'),
(5, 'Unpaid Leave of Absence', 'UL', 30, 0, 'Approved non-compensable leave for personal sabbaticals');

-- 10. Leave Balances (Year 2026)
INSERT INTO `leave_balances` (`employee_id`, `leave_type_id`, `year`, `total_days`, `used_days`, `pending_days`) VALUES
(4, 1, 2026, 18, 4, 2),
(4, 2, 2026, 12, 1, 0),
(4, 3, 2026, 8, 2, 0),
(3, 1, 2026, 18, 3, 0),
(3, 2, 2026, 12, 0, 0),
(5, 1, 2026, 18, 5, 0),
(6, 1, 2026, 18, 2, 0);

-- 11. Leave Requests
INSERT INTO `leave_requests` (`id`, `employee_id`, `leave_type_id`, `start_date`, `end_date`, `total_days`, `reason`, `status`, `manager_comment`, `reviewed_by`, `reviewed_at`) VALUES
(1, 4, 1, '2026-10-05', '2026-10-06', 2, 'Attending family anniversary celebration', 'pending', NULL, NULL, NULL),
(2, 4, 2, '2026-08-12', '2026-08-12', 1, 'Severe seasonal viral flu', 'approved', 'Approved. Hope you feel better soon!', 3, '2026-08-12 10:15:00'),
(3, 5, 1, '2026-09-21', '2026-09-25', 5, 'Annual fall family road trip', 'approved', 'Handover tasks are mapped to Elena.', 3, '2026-09-14 11:30:00'),
(4, 6, 3, '2026-09-28', '2026-09-28', 1, 'Relocation apartment key handover', 'pending', NULL, NULL, NULL);

-- 12. Payroll (August 2026)
INSERT INTO `payroll` (`id`, `employee_id`, `month`, `year`, `basic_salary`, `total_allowances`, `total_deductions`, `net_salary`, `payment_status`, `payment_date`, `notes`) VALUES
(1, 4, 8, 2026, 7200.00, 1150.00, 890.00, 7460.00, 'paid', '2026-08-31', 'Direct deposit August cycle'),
(2, 3, 8, 2026, 11500.00, 1800.00, 1620.00, 11680.00, 'paid', '2026-08-31', 'Direct deposit August cycle'),
(3, 5, 8, 2026, 6200.00, 950.00, 780.00, 6370.00, 'paid', '2026-08-31', 'Direct deposit August cycle'),
(4, 2, 8, 2026, 8500.00, 1300.00, 1100.00, 8700.00, 'paid', '2026-08-31', 'Direct deposit August cycle');

-- 13. Payroll Items
INSERT INTO `payroll_items` (`payroll_id`, `item_type`, `name`, `amount`) VALUES
(1, 'allowance', 'Housing Allowance (HRA)', 600.00),
(1, 'allowance', 'Medical Care Allowance', 350.00),
(1, 'allowance', 'Internet & Work Perks', 200.00),
(1, 'deduction', 'Federal Withholding Tax', 550.00),
(1, 'deduction', 'Health Insurance Contribution', 240.00),
(1, 'deduction', 'Retirement 401(k)', 100.00),
(2, 'allowance', 'Housing Allowance (HRA)', 1000.00),
(2, 'allowance', 'Leadership & Executive Allowance', 800.00),
(2, 'deduction', 'Federal Withholding Tax', 1100.00),
(2, 'deduction', 'Health Insurance Contribution', 320.00),
(2, 'deduction', 'Retirement 401(k)', 200.00);

-- 14. Documents
INSERT INTO `documents` (`id`, `employee_id`, `category`, `title`, `file_path`, `file_size`, `mime_type`, `uploaded_by`) VALUES
(1, 4, 'contracts', 'Employment Offer & NDA - Elena Rostova.pdf', 'uploads/contracts/elena_offer.pdf', 1048576, 'application/pdf', 2),
(2, 4, 'identity', 'Government Passport Scan.pdf', 'uploads/identity/elena_passport.pdf', 849201, 'application/pdf', 4),
(3, 4, 'policies', 'Apex Global Employee Handbook 2026.pdf', 'uploads/policies/handbook_2026.pdf', 3145728, 'application/pdf', 1),
(4, 3, 'contracts', 'Executive Employment Agreement - Marcus Brody.pdf', 'uploads/contracts/marcus_agreement.pdf', 1248576, 'application/pdf', 1);

-- 15. Announcements
INSERT INTO `announcements` (`id`, `title`, `content`, `target_role`, `is_pinned`, `author_id`) VALUES
(1, '🚀 Q4 Strategic Company All-Hands Meeting', 'Please join us this Thursday at 3:00 PM EST in the Main Auditorium or via the company video stream for our Q4 product roadmap rollout and executive updates.', 'all', 1, 1),
(2, '🏥 Annual Health & Wellness Benefits Enrollment Window', 'The open enrollment period for corporate medical, dental, and vision insurance policies runs through October 15th. Check the documents portal for tier breakdowns.', 'all', 0, 2),
(3, '💻 High-Performance Engineering Infrastructure Migration', 'The DevOps squad will perform server and CI/CD upgrades on Saturday from 02:00 AM to 05:00 AM UTC. Please ensure all staging branches are pushed beforehand.', 'all', 0, 3);

-- 16. Notifications
INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `link`, `is_read`) VALUES
(4, 'Leave Request Update', 'Your leave request for Aug 12 was approved by Marcus Brody.', 'success', '#/leave', 1),
(4, 'New Announcement', 'Alexander Stone posted: Q4 Strategic Company All-Hands Meeting', 'info', '#/announcements', 0),
(3, 'Pending Team Leave', 'Elena Rostova submitted a new leave request awaiting your review.', 'warning', '#/leave', 0),
(1, 'Payroll Execution Complete', 'August 2026 company payroll has been finalized and processed.', 'success', '#/payroll', 1);

-- 17. Performance Goals
INSERT INTO `performance_goals` (`id`, `employee_id`, `title`, `description`, `start_date`, `target_date`, `progress`, `status`) VALUES
(1, 4, 'Deliver Cloud API Gateway Refactor', 'Migrate high-frequency endpoint cluster to async architecture with <50ms p99 latency target.', '2026-07-01', '2026-09-30', 85, 'in_progress'),
(2, 4, 'Achieve 90% Unit & Integration Test Coverage', 'Expand automated test suites across core billing and checkout domain packages.', '2026-08-01', '2026-10-31', 65, 'in_progress'),
(3, 3, 'Scale Engineering Team by 4 Senior ICs', 'Source, interview, and onboard 4 senior systems and backend engineers for Q3/Q4.', '2026-07-01', '2026-12-31', 50, 'in_progress'),
(4, 5, 'Implement End-to-End Cypress Automation Matrix', 'Automate smoke and regression flows for enterprise customer onboarding journeys.', '2026-08-01', '2026-10-15', 90, 'in_progress');

-- 18. Performance Reviews
INSERT INTO `performance_reviews` (`id`, `employee_id`, `reviewer_id`, `review_period`, `rating`, `manager_feedback`, `employee_self_review`, `status`) VALUES
(1, 4, 3, '2026 H1 Mid-Year Appraisal', 4.8, 'Elena has shown remarkable initiative, ownership over microservice migrations, and technical excellence.', 'Delivered the auth microservice migration on schedule and mentored junior teammates.', 'completed'),
(2, 5, 3, '2026 H1 Mid-Year Appraisal', 4.4, 'David maintained impeccable QA release cadences and prevented critical production regressions.', 'Refactored flaky test suites and reduced automated CI runtime by 35%.', 'completed');

-- 19. Job Openings
INSERT INTO `job_openings` (`id`, `title`, `department_id`, `designation_id`, `job_type`, `experience_level`, `vacancies`, `description`, `requirements`, `status`) VALUES
(1, 'Lead Cloud Architect & Staff Engineer', 1, 1, 'full_time', 'Senior (7+ years)', 2, 'Lead our distributed systems architecture, cloud infrastructure, and technical design.', 'Deep mastery of distributed PHP, Go, MySQL, Docker, and Kubernetes.', 'open'),
(2, 'Senior Talent Acquisition Partner', 2, 5, 'full_time', 'Mid-Senior (4+ years)', 1, 'Drive full-lifecycle technical and corporate recruiting across North America and Europe.', 'Proven track record scaling tech teams from Series B to IPO.', 'open'),
(3, 'Product Marketing Specialist', 4, 7, 'full_time', 'Mid-Level (3+ years)', 1, 'Own product launch messaging, competitive battlecards, and sales enablement collateral.', 'Experience in enterprise B2B SaaS growth and customer marketing.', 'open');

-- 20. Candidates
INSERT INTO `candidates` (`id`, `job_opening_id`, `first_name`, `last_name`, `email`, `phone`, `resume_path`, `status`) VALUES
(1, 1, 'Jonathan', 'Cross', 'j.cross@example.com', '+1 (555) 301-4499', 'resumes/j_cross.pdf', 'active'),
(2, 1, 'Samantha', 'Miller', 's.miller@example.com', '+1 (555) 482-1928', 'resumes/s_miller.pdf', 'active'),
(3, 2, 'Brian', 'O''Connor', 'brian.oc@example.com', '+1 (555) 883-9201', 'resumes/brian_oc.pdf', 'active'),
(4, 3, 'Jessica', 'Alba', 'jess.alba@example.com', '+1 (555) 918-2831', 'resumes/j_alba.pdf', 'active'),
(5, 1, 'Tariq', 'Mansoor', 'tariq.m@example.com', '+1 (555) 129-4820', 'resumes/tariq_m.pdf', 'active');

-- 21. Applications (Recruitment Pipeline)
INSERT INTO `applications` (`id`, `candidate_id`, `job_opening_id`, `stage`, `rating`, `notes`) VALUES
(1, 1, 1, 'interview', 5, 'Exceptional deep dive on distributed lock primitives and MySQL connection pooling.'),
(2, 2, 1, 'selected', 5, 'Unanimous positive feedback from Marcus and team. Preparing competitive offer packet.'),
(3, 3, 2, 'screening', 4, 'Solid agency experience; Victoria conducted introductory culture screening.'),
(4, 4, 3, 'applied', 3, 'Resume received via LinkedIn corporate careers portal. Portfolio looks compelling.'),
(5, 5, 1, 'rejected', 2, 'Lacks hands-on experience with high-scale RDBMS architectures.');

-- 22. Interviews
INSERT INTO `interviews` (`id`, `candidate_id`, `job_opening_id`, `interviewer_id`, `interview_date`, `interview_time`, `location_or_link`, `status`, `feedback`) VALUES
(1, 1, 1, 3, '2026-09-22', '14:00:00', 'https://meet.company.com/tech-round-cross', 'scheduled', 'Architecture and systems design round'),
(2, 3, 2, 2, '2026-09-23', '11:00:00', 'https://meet.company.com/hr-screening-brian', 'scheduled', 'Hiring manager screening');

-- 23. Holidays
INSERT INTO `holidays` (`id`, `name`, `date`, `is_recurring`, `description`) VALUES
(1, 'New Year''s Day', '2026-01-01', 1, 'Official global company holiday'),
(2, 'Memorial Day', '2026-05-25', 1, 'National federal memorial day'),
(3, 'Independence Day', '2026-07-03', 1, 'Observed Independence Day holiday'),
(4, 'Labor Day', '2026-09-07', 1, 'Honoring the labor movement and workers'),
(5, 'Thanksgiving Day', '2026-11-26', 1, 'Corporate holiday with family and friends'),
(6, 'Day After Thanksgiving', '2026-11-27', 1, 'Extended holiday weekend'),
(7, 'Christmas Day', '2026-12-25', 1, 'Global Christmas holiday celebration');

-- 24. Company Events
INSERT INTO `company_events` (`id`, `title`, `event_date`, `start_time`, `end_time`, `location`, `description`, `department_id`) VALUES
(1, 'All-Hands Product Showcase & Town Hall', '2026-09-24', '15:00:00', '16:30:00', 'Main Auditorium & Zoom Live', 'Quarterly roadmap presentations and executive AMA', NULL),
(2, 'Tech Innovation Brown Bag Lunch', '2026-10-02', '12:00:00', '13:00:00', 'Room 402 / Silicon Hub', 'Engineering presentation on Redis vs Memcached performance', 1),
(3, 'Annual Autumn Company Hackathon', '2026-10-22', '09:00:00', '20:00:00', 'HQ Innovation Floor', '48-hour team hackathon with prizes and angel demos', NULL);

-- 25. Audit Logs
INSERT INTO `audit_logs` (`user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`) VALUES
(1, 'system_seed_init', 'system', NULL, 'Database schema seeded with enterprise demonstration data', '127.0.0.1'),
(1, 'user_login', 'auth', 1, 'Super Administrator authenticated via corporate session', '127.0.0.1'),
(2, 'payroll_processed', 'payroll', 1, 'August 2026 payroll generated and approved for 4 employees', '127.0.0.1');

-- 26. Company Settings
INSERT INTO `company_settings` (`setting_key`, `setting_value`) VALUES
('company_name', 'Apex Global Technologies Inc.'),
('company_tagline', 'Enterprise Cloud & Workforce Intelligence'),
('company_email', 'contact@apexglobal.tech'),
('company_phone', '+1 (800) 555-0199'),
('company_address', '100 Executive Way, Suite 400, New York, NY 10001, USA'),
('company_currency', 'USD'),
('currency_symbol', '$'),
('timezone', 'America/New_York'),
('date_format', 'Y-m-d'),
('working_hours_start', '09:00'),
('working_hours_end', '18:00'),
('standard_work_hours', '8.0'),
('fiscal_year_start', '01-01');

SET FOREIGN_KEY_CHECKS = 1;
