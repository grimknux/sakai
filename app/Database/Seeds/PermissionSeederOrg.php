<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PermissionSeederOrg extends Seeder
{
    public function run()
    {
        $data = [

            // SECTIONS
            [
                'name' => 'Access Sections Page',
                'slug' => 'sections.access',
                'module' => 'sections',
                'action' => 'access',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'View Sections',
                'slug' => 'sections.view',
                'module' => 'sections',
                'action' => 'view',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Create Section',
                'slug' => 'sections.create',
                'module' => 'sections',
                'action' => 'create',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Update Section',
                'slug' => 'sections.update',
                'module' => 'sections',
                'action' => 'update',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Delete Section',
                'slug' => 'sections.delete',
                'module' => 'sections',
                'action' => 'delete',
                'created_at' => date('Y-m-d H:i:s')
            ],

            // DIVISIONS
            [
                'name' => 'Access Divisions Page',
                'slug' => 'divisions.access',
                'module' => 'divisions',
                'action' => 'access',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'View Divisions',
                'slug' => 'divisions.view',
                'module' => 'divisions',
                'action' => 'view',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Create Division',
                'slug' => 'divisions.create',
                'module' => 'divisions',
                'action' => 'create',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Update Division',
                'slug' => 'divisions.update',
                'module' => 'divisions',
                'action' => 'update',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Delete Division',
                'slug' => 'divisions.delete',
                'module' => 'divisions',
                'action' => 'delete',
                'created_at' => date('Y-m-d H:i:s')
            ],

            // BUILDINGS
            [
                'name' => 'Access Buildings Page',
                'slug' => 'buildings.access',
                'module' => 'buildings',
                'action' => 'access',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'View Buildings',
                'slug' => 'buildings.view',
                'module' => 'buildings',
                'action' => 'view',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Create Building',
                'slug' => 'buildings.create',
                'module' => 'buildings',
                'action' => 'create',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Update Building',
                'slug' => 'buildings.update',
                'module' => 'buildings',
                'action' => 'update',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Delete Building',
                'slug' => 'buildings.delete',
                'module' => 'buildings',
                'action' => 'delete',
                'created_at' => date('Y-m-d H:i:s')
            ],

        ];

        $this->db->table('permissions')->insertBatch($data);
    }
}