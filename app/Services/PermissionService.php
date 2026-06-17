<?php

namespace App\Services;

class PermissionService
{
    protected $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function userHasPermission(int $userId, string $permissionSlug): bool
    {
        // Superadmin bypass
        $user = $this->db->table('users')->select('is_superadmin')->where('id', $userId)->get()->getRowArray();
        if ($user && (int)$user['is_superadmin'] === 1) {
            return true;
        }

        $builder = $this->db->table('user_roles ur');
        $builder->select('p.slug')
            ->join('role_permissions rp', 'rp.role_id = ur.role_id')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->where('ur.user_id', $userId)
            ->where('p.slug', $permissionSlug);

        return $builder->countAllResults() > 0;
    }

    public function getUserPermissions(int $userId): array
    {
        // Superadmin gets all
        $user = $this->db->table('users')->select('is_superadmin')->where('id', $userId)->get()->getRowArray();
        if ($user && (int)$user['is_superadmin'] === 1) {
            $rows = $this->db->table('permissions')->select('slug')->get()->getResultArray();
            return array_map(fn($r) => $r['slug'], $rows);
        }

        $rows = $this->db->table('user_roles ur')
            ->select('p.slug')
            ->join('role_permissions rp', 'rp.role_id = ur.role_id')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->where('ur.user_id', $userId)
            ->get()->getResultArray();

        return array_values(array_unique(array_map(fn($r) => $r['slug'], $rows)));
    }
}