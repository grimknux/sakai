<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PmsPermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [

            [
                'name'       => 'View PMS Schedule',
                'slug'       => 'pms.schedule.view',
                'module'     => 'pms.schedule',
                'action'     => 'view',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Create PMS Schedule',
                'slug'       => 'pms.schedule.create',
                'module'     => 'pms.schedule',
                'action'     => 'create',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Update PMS Schedule',
                'slug'       => 'pms.schedule.update',
                'module'     => 'pms.schedule',
                'action'     => 'update',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Delete PMS Schedule',
                'slug'       => 'pms.schedule.delete',
                'module'     => 'pms.schedule',
                'action'     => 'delete',
                'created_at' => date('Y-m-d H:i:s'),
            ],

            [
                'name'       => 'View PMS Record',
                'slug'       => 'pms.record.view',
                'module'     => 'pms.record',
                'action'     => 'view',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'View All PMS Records',
                'slug'       => 'pms.record.view_all',
                'module'     => 'pms.record',
                'action'     => 'view_all',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Create PMS Record',
                'slug'       => 'pms.record.create',
                'module'     => 'pms.record',
                'action'     => 'create',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Update PMS Record',
                'slug'       => 'pms.record.update',
                'module'     => 'pms.record',
                'action'     => 'update',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Delete PMS Record',
                'slug'       => 'pms.record.delete',
                'module'     => 'pms.record',
                'action'     => 'delete',
                'created_at' => date('Y-m-d H:i:s'),
            ],

            [
                'name'       => 'View PMS Questions',
                'slug'       => 'pms.question.view',
                'module'     => 'pms.question',
                'action'     => 'view',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Create PMS Question',
                'slug'       => 'pms.question.create',
                'module'     => 'pms.question',
                'action'     => 'create',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Update PMS Question',
                'slug'       => 'pms.question.update',
                'module'     => 'pms.question',
                'action'     => 'update',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Delete PMS Question',
                'slug'       => 'pms.question.delete',
                'module'     => 'pms.question',
                'action'     => 'delete',
                'created_at' => date('Y-m-d H:i:s'),
            ],

        ];

        $this->db->table('permissions')->insertBatch($permissions);
    }
}