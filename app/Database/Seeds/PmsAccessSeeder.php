<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PmsAccessSeeder extends Seeder
{
    public function run()
    {
        $permissions = [

            [
                'name'       => 'Access PMS Schedules',
                'slug'       => 'pms.schedule.access',
                'module'     => 'pms.schedule',
                'action'     => 'access',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Access PMS Questions',
                'slug'       => 'pms.question.access',
                'module'     => 'pms.question',
                'action'     => 'access',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Access PMS Records',
                'slug'       => 'pms.record.access',
                'module'     => 'pms.record',
                'action'     => 'access',
                'created_at' => date('Y-m-d H:i:s'),
            ],

        ];

        $this->db->table('permissions')->insertBatch($permissions);
    }
}