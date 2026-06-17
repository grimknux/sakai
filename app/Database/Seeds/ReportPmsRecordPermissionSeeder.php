<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ReportPmsRecordPermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [

            [
                'name'       => 'Access PMS Record Report Page',
                'slug'       => 'pms.record.access.report',
                'module'     => 'pms.report',
                'action'     => 'access',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'View PMS Record Report',
                'slug'       => 'pms.record.view.report',
                'module'     => 'pms.report',
                'action'     => 'view',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Access Inventory Report Page',
                'slug'       => 'inventory.access.report',
                'module'     => 'inventory.report',
                'action'     => 'access',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'View Inventory Report',
                'slug'       => 'inventory.view.report',
                'module'     => 'inventory.report',
                'action'     => 'view',
                'created_at' => date('Y-m-d H:i:s'),
            ]

        ];

        $this->db->table('permissions')->insertBatch($permissions);
    }
}