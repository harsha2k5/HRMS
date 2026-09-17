<?php
/**
 * Database Connection Manager (PDO)
 * Supports MySQL 8+ / MariaDB as Primary with SQLite Local Fallback
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;
    private static string $activeDriver = 'mysql';

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            self::initConnection();
        }
        return self::$instance;
    }

    public static function getDriver(): string {
        return self::$activeDriver;
    }

    private static function initConnection(): void {
        $preferredDriver = env('DB_CONNECTION', 'mysql');

        if ($preferredDriver === 'mysql') {
            try {
                self::connectMySQL();
                self::$activeDriver = 'mysql';
                return;
            } catch (Exception $e) {
                // If MySQL fails (e.g. server down or port closed during local test), fallback to SQLite
                error_log("MySQL connection failed: " . $e->getMessage() . ". Falling back to SQLite local database.");
                self::connectSQLite();
                self::$activeDriver = 'sqlite';
                return;
            }
        }

        self::connectSQLite();
        self::$activeDriver = 'sqlite';
    }

    private static function connectMySQL(): void {
        $host = env('DB_HOST', '127.0.0.1');
        $port = env('DB_PORT', '3306');
        $db   = env('DB_NAME', 'hrms_db');
        $user = env('DB_USER', 'root');
        $pass = env('DB_PASSWORD', '');

        // Attempt initial connection to MySQL server
        $dsnWithoutDb = "mysql:host={$host};port={$port};charset=utf8mb4";
        $tempPdo = new PDO($dsnWithoutDb, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 2
        ]);

        // Ensure database exists
        $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Connect to the specific database
        $dsnWithDb = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
        self::$instance = new PDO($dsnWithDb, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);

        // Check if tables exist, if not run schema and seed
        $stmt = self::$instance->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() === 0) {
            self::runMySQLSchemaAndSeed();
        }
    }

    private static function connectSQLite(): void {
        $sqliteFile = BASE_PATH . DIRECTORY_SEPARATOR . env('SQLITE_PATH', 'database/hrms.sqlite');
        $sqliteDir = dirname($sqliteFile);
        if (!is_dir($sqliteDir)) {
            mkdir($sqliteDir, 0777, true);
        }

        $isNew = !file_exists($sqliteFile) || filesize($sqliteFile) === 0;

        self::$instance = new PDO("sqlite:" . $sqliteFile, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        self::$instance->exec("PRAGMA foreign_keys = ON;");

        if ($isNew) {
            self::runSQLiteSchemaAndSeed();
        }
    }

    private static function runMySQLSchemaAndSeed(): void {
        $schemaFile = BASE_PATH . '/database/schema.sql';
        $seedFile   = BASE_PATH . '/database/seed.sql';

        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            self::$instance->exec($sql);
        }

        if (file_exists($seedFile)) {
            $sql = file_get_contents($seedFile);
            self::$instance->exec($sql);
        }
    }

    private static function runSQLiteSchemaAndSeed(): void {
        // SQLite-compatible schema translation
        $sql = "
        CREATE TABLE IF NOT EXISTS roles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            display_name TEXT NOT NULL,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            module TEXT NOT NULL,
            action TEXT NOT NULL,
            description TEXT,
            UNIQUE(module, action)
        );

        CREATE TABLE IF NOT EXISTS role_permissions (
            role_id INTEGER NOT NULL,
            permission_id INTEGER NOT NULL,
            PRIMARY KEY (role_id, permission_id)
        );

        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role_id INTEGER NOT NULL,
            status TEXT DEFAULT 'active',
            avatar TEXT DEFAULT NULL,
            last_login_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS departments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            code TEXT NOT NULL UNIQUE,
            description TEXT,
            head_id INTEGER DEFAULT NULL,
            status TEXT DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS designations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            department_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            min_salary REAL DEFAULT 0.00,
            max_salary REAL DEFAULT 0.00,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS employees (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL UNIQUE,
            employee_code TEXT NOT NULL UNIQUE,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            phone TEXT,
            date_of_birth TEXT,
            gender TEXT DEFAULT 'male',
            address TEXT,
            emergency_contact TEXT,
            department_id INTEGER,
            designation_id INTEGER,
            manager_id INTEGER DEFAULT NULL,
            joining_date TEXT NOT NULL,
            employment_type TEXT DEFAULT 'full_time',
            employment_status TEXT DEFAULT 'active',
            basic_salary REAL DEFAULT 0.00,
            bank_name TEXT,
            bank_account_no TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS attendance (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employee_id INTEGER NOT NULL,
            date TEXT NOT NULL,
            check_in TEXT DEFAULT NULL,
            check_out TEXT DEFAULT NULL,
            working_hours REAL DEFAULT 0.00,
            overtime_hours REAL DEFAULT 0.00,
            status TEXT DEFAULT 'present',
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(employee_id, date)
        );

        CREATE TABLE IF NOT EXISTS leave_types (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            code TEXT NOT NULL UNIQUE,
            max_days_per_year INTEGER NOT NULL DEFAULT 12,
            is_paid INTEGER DEFAULT 1,
            description TEXT
        );

        CREATE TABLE IF NOT EXISTS leave_balances (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employee_id INTEGER NOT NULL,
            leave_type_id INTEGER NOT NULL,
            year INTEGER NOT NULL,
            total_days INTEGER NOT NULL,
            used_days INTEGER NOT NULL DEFAULT 0,
            pending_days INTEGER NOT NULL DEFAULT 0,
            UNIQUE(employee_id, leave_type_id, year)
        );

        CREATE TABLE IF NOT EXISTS leave_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employee_id INTEGER NOT NULL,
            leave_type_id INTEGER NOT NULL,
            start_date TEXT NOT NULL,
            end_date TEXT NOT NULL,
            total_days INTEGER NOT NULL,
            reason TEXT NOT NULL,
            status TEXT DEFAULT 'pending',
            manager_comment TEXT,
            reviewed_by INTEGER DEFAULT NULL,
            reviewed_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS payroll (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employee_id INTEGER NOT NULL,
            month INTEGER NOT NULL,
            year INTEGER NOT NULL,
            basic_salary REAL NOT NULL,
            total_allowances REAL DEFAULT 0.00,
            total_deductions REAL DEFAULT 0.00,
            net_salary REAL NOT NULL,
            payment_status TEXT DEFAULT 'draft',
            payment_date TEXT DEFAULT NULL,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(employee_id, month, year)
        );

        CREATE TABLE IF NOT EXISTS payroll_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            payroll_id INTEGER NOT NULL,
            item_type TEXT NOT NULL,
            name TEXT NOT NULL,
            amount REAL NOT NULL
        );

        CREATE TABLE IF NOT EXISTS documents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employee_id INTEGER NOT NULL,
            category TEXT DEFAULT 'employee_documents',
            title TEXT NOT NULL,
            file_path TEXT NOT NULL,
            file_size INTEGER DEFAULT 0,
            mime_type TEXT,
            uploaded_by INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS announcements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            target_role TEXT DEFAULT 'all',
            is_pinned INTEGER DEFAULT 0,
            author_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            message TEXT NOT NULL,
            type TEXT DEFAULT 'info',
            link TEXT DEFAULT NULL,
            is_read INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS performance_goals (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employee_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            start_date TEXT NOT NULL,
            target_date TEXT NOT NULL,
            progress INTEGER DEFAULT 0,
            status TEXT DEFAULT 'in_progress',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS performance_reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employee_id INTEGER NOT NULL,
            reviewer_id INTEGER NOT NULL,
            review_period TEXT NOT NULL,
            rating REAL NOT NULL,
            manager_feedback TEXT,
            employee_self_review TEXT,
            status TEXT DEFAULT 'completed',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS job_openings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            department_id INTEGER NOT NULL,
            designation_id INTEGER,
            job_type TEXT DEFAULT 'full_time',
            experience_level TEXT DEFAULT 'Mid-Level',
            vacancies INTEGER DEFAULT 1,
            description TEXT,
            requirements TEXT,
            status TEXT DEFAULT 'open',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS candidates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            job_opening_id INTEGER NOT NULL,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            email TEXT NOT NULL,
            phone TEXT,
            resume_path TEXT,
            status TEXT DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS applications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            candidate_id INTEGER NOT NULL,
            job_opening_id INTEGER NOT NULL,
            stage TEXT DEFAULT 'applied',
            rating INTEGER DEFAULT 3,
            notes TEXT,
            applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS interviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            candidate_id INTEGER NOT NULL,
            job_opening_id INTEGER NOT NULL,
            interviewer_id INTEGER NOT NULL,
            interview_date TEXT NOT NULL,
            interview_time TEXT NOT NULL,
            location_or_link TEXT,
            status TEXT DEFAULT 'scheduled',
            feedback TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS holidays (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            date TEXT NOT NULL UNIQUE,
            is_recurring INTEGER DEFAULT 1,
            description TEXT
        );

        CREATE TABLE IF NOT EXISTS company_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            event_date TEXT NOT NULL,
            start_time TEXT,
            end_time TEXT,
            location TEXT,
            description TEXT,
            department_id INTEGER DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER DEFAULT NULL,
            action TEXT NOT NULL,
            entity_type TEXT NOT NULL,
            entity_id INTEGER DEFAULT NULL,
            details TEXT,
            ip_address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS company_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT NOT NULL UNIQUE,
            setting_value TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        ";

        self::$instance->exec($sql);

        // Read seed data and insert into SQLite
        $seedFile = BASE_PATH . '/database/seed.sql';
        if (file_exists($seedFile)) {
            $seedSql = file_get_contents($seedFile);
            // Strip out MySQL specific statements
            $cleanLines = [];
            foreach (explode("\n", $seedSql) as $line) {
                $trimmed = trim($line);
                if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*') || empty($trimmed)) {
                    continue;
                }
                if (str_contains($trimmed, 'SET FOREIGN_KEY_CHECKS')) {
                    continue;
                }
                $cleanLines[] = $line;
            }
            $cleanSql = implode("\n", $cleanLines);
            try {
                self::$instance->exec($cleanSql);
            } catch (Exception $e) {
                error_log("Seed execution error: " . $e->getMessage());
            }
        }
    }
}
