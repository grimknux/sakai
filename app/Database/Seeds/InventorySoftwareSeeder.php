<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InventorySoftwareSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            [
                'name'       => 'View Inventory Software',
                'slug'       => 'inventory.software.view',
                'module'     => 'inventory.software',
                'action'     => 'view',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Create Inventory Software',
                'slug'       => 'inventory.software.create',
                'module'     => 'inventory.software',
                'action'     => 'create',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Update Inventory Software',
                'slug'       => 'inventory.software.update',
                'module'     => 'inventory.software',
                'action'     => 'update',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Delete Inventory Software',
                'slug'       => 'inventory.software.delete',
                'module'     => 'inventory.software',
                'action'     => 'delete',
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('permissions')->insertBatch($permissions);
    }
}