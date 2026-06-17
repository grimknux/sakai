<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DeviceTypePermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            [
                'name'       => 'Access Device Types Page',
                'slug'       => 'devicetypes.access',
                'module'     => 'devicetypes',
                'action'     => 'access',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'View Device Types',
                'slug'       => 'devicetypes.view',
                'module'     => 'devicetypes',
                'action'     => 'view',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Create Device Type',
                'slug'       => 'devicetypes.create',
                'module'     => 'devicetypes',
                'action'     => 'create',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Update Device Type',
                'slug'       => 'devicetypes.update',
                'module'     => 'devicetypes',
                'action'     => 'update',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Delete Device Type',
                'slug'       => 'devicetypes.delete',
                'module'     => 'devicetypes',
                'action'     => 'delete',
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('permissions')->insertBatch($permissions);
    }
}