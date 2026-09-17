<?php
/**
 * Role-Based Access Control (RBAC) & Scope Authorization Middleware
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../config/database.php';

class RBAC {
    /**
     * Require one of the specified roles
     */
    public static function requireRoles(array $allowedRoles): void {
        Auth::requireAuth();
        $userRole = Auth::role();

        if (!in_array($userRole, $allowedRoles, true)) {
            Response::forbidden("Access denied: You do not have permission for this action ({$userRole}).");
        }
    }

    /**
     * Verify if current user can view/manage target employee
     */
    public static function canAccessEmployee(int $targetEmployeeId): bool {
        Auth::requireAuth();
        $role = Auth::role();

        // Super Admin and HR Admin can access all employees
        if (in_array($role, ['super_admin', 'hr_admin'], true)) {
            return true;
        }

        $currentEmployee = Auth::employee();
        if (!$currentEmployee) {
            return false;
        }

        // Employee can only access themselves
        if ($role === 'employee') {
            return (int)$currentEmployee['id'] === $targetEmployeeId;
        }

        // Manager can access themselves AND their direct reports
        if ($role === 'manager') {
            if ((int)$currentEmployee['id'] === $targetEmployeeId) {
                return true;
            }

            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                SELECT id FROM employees
                WHERE id = :target_id AND (manager_id = :manager_id OR department_id = :dept_id)
            ");
            $stmt->execute([
                ':target_id'  => $targetEmployeeId,
                ':manager_id' => $currentEmployee['id'],
                ':dept_id'    => $currentEmployee['department_id']
            ]);
            return $stmt->fetch() !== false;
        }

        return false;
    }

    /**
     * Return SQL WHERE clause condition and params to scope employee queries by role
     */
    public static function getEmployeeScopeCondition(string $tableAlias = 'e'): array {
        Auth::requireAuth();
        $role = Auth::role();
        $currentEmployee = Auth::employee();

        if (in_array($role, ['super_admin', 'hr_admin'], true)) {
            return ['where' => '1=1', 'params' => []];
        }

        if ($role === 'manager' && $currentEmployee) {
            return [
                'where' => "({$tableAlias}.id = :rbac_emp_id OR {$tableAlias}.manager_id = :rbac_mgr_id OR {$tableAlias}.department_id = :rbac_dept_id)",
                'params' => [
                    ':rbac_emp_id'  => $currentEmployee['id'],
                    ':rbac_mgr_id'  => $currentEmployee['id'],
                    ':rbac_dept_id' => $currentEmployee['department_id']
                ]
            ];
        }

        // Standard employee can only see self
        $empId = $currentEmployee ? $currentEmployee['id'] : -1;
        return [
            'where' => "{$tableAlias}.id = :rbac_self_emp_id",
            'params' => [':rbac_self_emp_id' => $empId]
        ];
    }
}
