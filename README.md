# Apex Global HRMS — Enterprise Workforce & HR Management System

A complete, production-grade Human Resource Management System (HRMS) architected for modern enterprises. Built with a robust **PHP 8.2+ PDO backend**, **MySQL 8+ / MariaDB normalized relational database**, strict **Role-Based Access Control (RBAC)**, REST-style APIs, and a sleek, responsive **Vanilla ES6+ Enterprise SaaS frontend**.

---

## 🌟 Key Capabilities & Modules

| Module | Core Functionality |
| :--- | :--- |
| **Authentication & RBAC** | Secure session management, bcrypt hashing, timeout protection, strict role enforcement across 4 tiers (*Super Admin, HR Admin, Manager, Employee*). |
| **Executive Dashboard** | Role-tailored KPI widgets, quick check-in punch clock with live timer, 7-day attendance trend, department distribution, and audit feeds. |
| **Employee Directory** | Complete CRUD, profile drawers with 6 sub-tabs (Personal, Employment, Attendance, Leave, Payroll, Documents, Performance), advanced search & filters. |
| **Time & Attendance** | Live punch in/out widget, auto late-arrival & overtime calculation, monthly calendar view, and historical logs. |
| **Leave Management** | Multi-category leave balances (Annual, Sick, Casual, etc.), application submission with automated balance deduction, and Manager/HR approval workflows. |
| **Payroll & Payslips** | Salary structures, automated batch payroll processing (basic, HRA, stipends, tax, health insurance, 401k), and interactive printable payslips. |
| **Departments & Designations** | Business unit hierarchy, department heads, active headcount, and designation bands with salary brackets. |
| **Recruitment Pipeline** | Job openings publisher, candidate tracking, and an interactive Kanban pipeline (*Applied → Screening → Interview → Selected → Hired / Rejected*). |
| **Performance Reviews** | Goal/OKR tracking with interactive completion sliders, manager ratings (1-5 stars), feedback notes, and self-review submissions. |
| **Document Vault** | Secure categorical file repository (Contracts, Identity, Policies, etc.) with strict MIME type & executable file exclusion. |
| **Announcements & Notifications** | Targeted role broadcasts, pinned executive updates, persistent in-app notifications, and unread badges. |
| **Company Calendar** | Unified calendar tracking national holidays, approved leaves, townhalls, and scheduled interviews. |
| **Reports & CSV Export** | Dynamic reporting on workforce metrics, attendance, leaves, and compensation with 1-click CSV download. |
| **System Settings & Audit** | Organization profile configuration, user account permission manager, and immutable audit logs with IP & timestamp tracking. |

---

## 🏗️ Architecture & Technology Stack

```text
┌─────────────────────────────────────────────────────────────┐
│                    ENTERPRISE FRONTEND                      │
│      (HTML5 + Vanilla CSS3 SaaS Design System + ES6+ SPA)   │
└──────────────────────────────┬──────────────────────────────┘
                               │ REST JSON / Cookie Sessions
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                      PHP 8.2+ BACKEND                       │
│    • Session Auth & RBAC Middleware                         │
│    • Prepared PDO Statements & JSON Responses               │
│    • Document MIME & File Security Filter                   │
│    • Audit Logging Service & Request Validator              │
└──────────────────────────────┬──────────────────────────────┘
                               │ PDO Prepared Statements
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                      MYSQL 8+ / MARIADB                     │
│    • 26 Normalized Tables with Foreign Keys & Indexes       │
│    • Comprehensive Demo Seed Dataset for all 4 Roles        │
│    • Dual-Driver SQLite Fallback for Instant Local Testing  │
└─────────────────────────────────────────────────────────────┘
```

* **Backend:** PHP 8.2+ (ZTS Visual C++ 2019 x64 compatible), PHP PDO, Native Sessions, Bcrypt.
* **Database:** MySQL 8+ / MariaDB (Primary) with automatic fallback to SQLite for zero-friction local developer testing.
* **Frontend:** HTML5, Modern Vanilla CSS3 (Custom Slate/Charcoal SaaS Design System), ES6+ JavaScript.
* **Web Server Support:** Apache (`.htaccess` included) and PHP CLI Server (`router.php` included).

---

## 👥 Demo Accounts & Pre-Configured Credentials

All seed accounts are initialized with the password: **`password123`**

| Role | Name | Email | Password | Permitted Scope |
| :--- | :--- | :--- | :--- | :--- |
| **Super Admin** | Alexander Stone | `admin@company.com` | `password123` | Full system governance, all settings, users, and audit logs |
| **HR Admin** | Victoria Vance | `hr@company.com` | `password123` | Employee lifecycle, payroll, recruitment, leave, documents |
| **Department Manager** | Marcus Brody | `manager@company.com` | `password123` | Team attendance, review team leaves, performance appraisals |
| **Standard Employee** | Elena Rostova | `employee@company.com` | `password123` | Self-service profile, attendance, leave, payslips, goals |

> 💡 *The login screen includes a 1-click Quick Demo Account switcher to instantly sign in as any of the 4 roles.*

---

## 🚀 Installation & Local Setup

### 1. Requirements
* PHP 8.2 or higher
* PDO extension enabled (`pdo_mysql` and `pdo_sqlite`)
* MySQL 8+ / MariaDB (or use the built-in SQLite auto-runner)

### 2. Clone or Extract Project
Place the project directory in your desired path:
```bash
cd e:\InSpark\HRMS
```

### 3. Environment Configuration
Copy `.env.example` to `.env` (already created by default):
```ini
APP_NAME="Apex Global HRMS"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# MySQL Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=hrms_db
DB_USER=root
DB_PASSWORD=
```

### 4. Database Setup (MySQL)
If running a local MySQL server:
```bash
mysql -u root -p -e "CREATE DATABASE hrms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p hrms_db < database/schema.sql
mysql -u root -p hrms_db < database/seed.sql
```
*Note: If MySQL is not running locally, the application automatically initializes and seeds `database/hrms.sqlite` with identical schemas and tables, allowing zero-friction instant evaluation.*

### 5. Launch Application
Start the application using the included built-in server router:
```bash
php -S 127.0.0.1:8000 router.php
```
Open your browser and navigate to: **`http://127.0.0.1:8000/`**

---

## 🔒 Security Architecture

* **No Plaintext Passwords:** Passwords hashed with `PASSWORD_DEFAULT` (Bcrypt).
* **Prepared Statements:** 100% of database queries use PDO parameterized queries to eliminate SQL injection.
* **Server-side Authorization (RBAC):** Backend verifies role and scope on every request. Managers cannot query non-team subordinates; employees cannot query other users' payroll or documents.
* **File Upload Safeguards:** Executable extensions (`.php`, `.exe`, `.sh`, `.bat`, `.js`) are strictly prohibited. Validates MIME type using `finfo` and restricts uploads to 10MB.
* **Anti-Session Hijacking:** Session IDs regenerated on authentication (`session_regenerate_id(true)`), HTTP-only cookies, and automatic 2-hour inactivity timeouts.
* **Immutable Audit Trail:** Critical events (logins, employee mutations, payroll disbursements, leave actions) are permanently logged in `audit_logs` with IP and initiator ID.

---

## 📁 Project Directory Structure

```text
hrms/
├── api/
│   ├── auth/              # Login, Logout, Register, Me, Password Management
│   ├── dashboard/         # Aggregated role-tailored KPIs and charts
│   ├── employees/         # Full CRUD, Profile View with sub-tabs, filters
│   ├── attendance/        # Check-in, check-out, monthly history logs
│   ├── leave/             # Balances, apply, manager approve/reject
│   ├── payroll/           # Batch processing, payslip generator
│   ├── departments/       # Department management & headcount
│   ├── designations/      # Titles, salary range bands
│   ├── recruitment/       # Job openings, candidates, Kanban pipeline
│   ├── performance/       # Goal progress tracking, appraisal reviews
│   ├── documents/         # Secure category vault, upload, download, delete
│   ├── announcements/     # Company-wide and role-targeted broadcasts
│   ├── notifications/     # In-app notifications & read markers
│   ├── calendar/          # Unified events, leaves, and holidays
│   ├── reports/           # Analytics generation and CSV export
│   ├── settings/          # Company profile, user role administration, audit
│   └── search.php         # Global unified search endpoint
├── config/
│   ├── config.php         # Environment loader, constants, sessions
│   └── database.php       # PDO connection manager & auto-migration engine
├── database/
│   ├── schema.sql         # 26-Table Normalized MySQL 8+ Schema
│   └── seed.sql           # Realistic enterprise demo seed dataset
├── helpers/
│   ├── response.php       # Standardized JSON response handler
│   ├── validator.php      # Input sanitization & validation rules
│   └── audit.php          # Audit logging helper
├── middleware/
│   ├── auth.php           # Authentication verification & session timeouts
│   └── rbac.php           # Role-based access control & scope authorization
├── public/
│   ├── assets/
│   │   ├── css/style.css  # Enterprise SaaS custom design system
│   │   └── js/            # Client SPA controllers, API client, module views
│   └── index.php          # Main HTML5 application shell
├── storage/
│   └── uploads/           # Categorized file storage
├── test_suite.php         # Automated API test runner
├── router.php             # Built-in PHP server router
├── .htaccess              # Apache rewrite configuration
├── .env.example           # Environment template
└── README.md              # Documentation
```

---

## 🧪 Automated Testing
Run the automated test suite from the terminal:
```bash
php test_suite.php
```
All 13 assertions covering database connectivity, password verification, seed counts, role scope authorization, and payroll arithmetic are evaluated and passed.

---

## ☁️ Cloud & Production Deployment

For deploying to cloud platforms (**Render**, **Railway**, **Fly.io**, or **Docker**), refer to the step-by-step guide:
👉 **[DEPLOYMENT.md](DEPLOYMENT.md)**

### Quick Summary:
* **Render.com:** 1-Click deploy from GitHub via Docker (Free tier). Ready-to-go `render.yaml` Blueprint included.
* **Railway.app:** 1-Click deploy from GitHub + instant managed cloud MySQL database.
* **Fly.io:** Run `fly launch` and `fly deploy`.
* **Docker Compose:** Run `docker compose up --build` for full local multi-container staging with MySQL 8.

