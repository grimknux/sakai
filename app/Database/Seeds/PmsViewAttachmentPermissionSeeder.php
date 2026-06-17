<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PmsViewAttachmentPermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [

            [
                'name'       => 'View PMS Schedule Attachment',
                'slug'       => 'pms.schedule.view.attachment',
                'module'     => 'pms.schedule',
                'action'     => 'view_attachment',
                'created_at' => date('Y-m-d H:i:s'),
            ],

        ];

        $this->db->table('permissions')->insertBatch($permissions);
    }
}