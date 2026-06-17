<?php

namespace App\Controllers\Api\Admin;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\RESTful\ResourceController;

class RolePermissionsController extends ResourceController
{
    protected $format = 'json';

    protected BaseConnection $db;

    /**
     * ==========================================
     * Constructor
     * ==========================================
     */
    public function __construct()
    {
        $this->db = \Config\Database::connect();

        helper('security');
    }

    /**
     * ==========================================
     * Get Role Permissions
     * ==========================================
     */
    public function get($roleId = null)
    {
        $roleId = (int) $roleId;

        if ($roleId <= 0) {
            return $this->failValidationErrors('Invalid role ID.');
        }

        $role = $this->db->table('roles')
            ->where('id', $roleId)
            ->get()
            ->getRowArray();

        if (! $role) {
            return $this->failNotFound('Role not found');
        }

        if (($role['slug'] ?? '') === 'super-admin') {
            return $this->failForbidden('Super Admin role permissions are locked');
        }

        $rows = $this->db->table('role_permissions')
            ->select('permission_id')
            ->where('role_id', $roleId)
            ->get()
            ->getResultArray();

        $ids = array_map(fn($r) => (int) $r['permission_id'], $rows);

        return $this->respond([
            'role' => $role,
            'permission_ids' => $ids,
        ]);
    }

    /**
     * ==========================================
     * Set Role Permissions
     * ==========================================
     */
    public function set($roleId = null)
    {
        try {
            $roleId = (int) $roleId;

            if ($roleId <= 0) {
                return $this->failValidationErrors('Invalid role ID.');
            }

            $role = $this->db->table('roles')
                ->where('id', $roleId)
                ->get()
                ->getRowArray();

            if (! $role) {
                return $this->failNotFound('Role not found');
            }

            if (($role['slug'] ?? '') === 'super-admin') {
                return $this->failForbidden('Super Admin role permissions are locked');
            }

            $data = $this->request->getJSON(true) ?? [];
            $permIds = $data['permission_ids'] ?? [];

            if (! is_array($permIds)) {
                return $this->failValidationErrors('permission_ids must be an array');
            }

            $permIds = array_values(array_unique(array_map('intval', $permIds)));

            if (! empty($permIds)) {
                $validPermissionRows = $this->db->table('permissions')
                    ->select('id')
                    ->whereIn('id', $permIds)
                    ->get()
                    ->getResultArray();

                $validPermissionIds = array_map(static fn($row) => (int) $row['id'], $validPermissionRows);

                foreach ($permIds as $permId) {
                    if (! in_array($permId, $validPermissionIds, true)) {
                        return $this->respond([
                            'status'   => 422,
                            'error'    => 422,
                            'messages' => [
                                'error'  => 'Validation failed',
                                'fields' => [
                                    'permission_ids' => 'One or more selected permissions are invalid.',
                                ],
                            ],
                        ], 422);
                    }
                }
            }

            $this->db->transBegin();

            $deleted = $this->db->table('role_permissions')
                ->where('role_id', $roleId)
                ->delete();

            if ($deleted === false) {
                throw new \RuntimeException('Failed to clear existing role permissions.');
            }

            foreach ($permIds as $pid) {
                $inserted = $this->db->table('role_permissions')->insert([
                    'role_id'       => $roleId,
                    'permission_id' => $pid,
                ]);

                if (! $inserted) {
                    throw new \RuntimeException('Failed to assign role permission.');
                }
            }

            service('audit')->log('roles.permissions.set', 'roles', $roleId, [
                'permission_ids' => $permIds,
            ]);

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed while updating role permissions.');
            }

            $this->db->transCommit();

            return $this->respond(['message' => 'Role permissions updated']);
        } catch (\Throwable $e) {
            $this->db->transRollback();

            log_message('error', 'Set Role Permissions Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to update role permissions.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }
}