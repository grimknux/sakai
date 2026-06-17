<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AssetPermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [

            /*
            |--------------------------------------------------------------------------
            | LICENSE TYPES
            |--------------------------------------------------------------------------
            */
            [
                'name' => 'Access License Types Page',
                'slug' => 'license_types.access',
                'module' => 'license_types',
                'action' => 'access',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'View License Types',
                'slug' => 'license_types.view',
                'module' => 'license_types',
                'action' => 'view',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Create License Type',
                'slug' => 'license_types.create',
                'module' => 'license_types',
                'action' => 'create',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Update License Type',
                'slug' => 'license_types.update',
                'module' => 'license_types',
                'action' => 'update',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Delete License Type',
                'slug' => 'license_types.delete',
                'module' => 'license_types',
                'action' => 'delete',
                'created_at' => date('Y-m-d H:i:s')
            ],

            /*
            |--------------------------------------------------------------------------
            | SOFTWARE TYPES
            |--------------------------------------------------------------------------
            */
            [
                'name' => 'Access Software Types Page',
                'slug' => 'software_types.access',
                'module' => 'software_types',
                'action' => 'access',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'View Software Types',
                'slug' => 'software_types.view',
                'module' => 'software_types',
                'action' => 'view',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Create Software Type',
                'slug' => 'software_types.create',
                'module' => 'software_types',
                'action' => 'create',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Update Software Type',
                'slug' => 'software_types.update',
                'module' => 'software_types',
                'action' => 'update',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Delete Software Type',
                'slug' => 'software_types.delete',
                'module' => 'software_types',
                'action' => 'delete',
                'created_at' => date('Y-m-d H:i:s')
            ],

            /*
            |--------------------------------------------------------------------------
            | SOFTWARES
            |--------------------------------------------------------------------------
            */
            [
                'name' => 'Access Softwares Page',
                'slug' => 'softwares.access',
                'module' => 'softwares',
                'action' => 'access',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'View Softwares',
                'slug' => 'softwares.view',
                'module' => 'softwares',
                'action' => 'view',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Create Software',
                'slug' => 'softwares.create',
                'module' => 'softwares',
                'action' => 'create',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Update Software',
                'slug' => 'softwares.update',
                'module' => 'softwares',
                'action' => 'update',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Delete Software',
                'slug' => 'softwares.delete',
                'module' => 'softwares',
                'action' => 'delete',
                'created_at' => date('Y-m-d H:i:s')
            ],

            /*
            |--------------------------------------------------------------------------
            | INVENTORY
            |--------------------------------------------------------------------------
            */
            [
                'name' => 'Access Inventory Page',
                'slug' => 'inventory.access',
                'module' => 'inventory',
                'action' => 'access',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'View Inventory',
                'slug' => 'inventory.view',
                'module' => 'inventory',
                'action' => 'view',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Create Inventory',
                'slug' => 'inventory.create',
                'module' => 'inventory',
                'action' => 'create',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Update Inventory',
                'slug' => 'inventory.update',
                'module' => 'inventory',
                'action' => 'update',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Delete Inventory',
                'slug' => 'inventory.delete',
                'module' => 'inventory',
                'action' => 'delete',
                'created_at' => date('Y-m-d H:i:s')
            ],

        ];

        $this->db->table('permissions')->insertBatch($permissions);
    }
}