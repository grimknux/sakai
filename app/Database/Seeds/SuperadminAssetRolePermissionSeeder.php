<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SuperadminAssetRolePermissionSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        $role = $db->table('roles')
            ->where('slug', 'super-admin')
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if (! $role) {
            echo "Superadmin role not found.\n";
            return;
        }

        $permissions = $db->table('permissions')
            ->select('id')
            ->whereIn('module', [
                'device_types',
                'software_types',
                'license_types',
                'softwares',
                'inventory',
            ])
            ->get()
            ->getResultArray();

        if (empty($permissions)) {
            echo "No matching permissions found.\n";
            return;
        }

        $rows = [];

        foreach ($permissions as $permission) {
            $exists = $db->table('role_permissions')
                ->where('role_id', $role['id'])
                ->where('permission_id', $permission['id'])
                ->countAllResults();

            if (! $exists) {
                $rows[] = [
                    'role_id'       => $role['id'],
                    'permission_id' => $permission['id'],
                ];
            }
        }

        if (! empty($rows)) {
            $db->table('role_permissions')->insertBatch($rows);
            echo count($rows) . " role permissions inserted.\n";
            return;
        }

        echo "No new role permissions to insert.\n";
    }
}