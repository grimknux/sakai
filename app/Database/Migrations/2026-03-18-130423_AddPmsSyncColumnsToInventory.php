<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPmsSyncColumnsToInventory extends Migration
{
    public function up()
    {
        $fields = [
            'last_pms_schedule_synced_id' => [
                'type'       => 'INT',
                'null'       => true,
            ],
            'last_pms_synced_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
            ],
        ];

        $this->forge->addColumn('inventory', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('inventory', [
            'last_pms_schedule_synced_id',
            'last_pms_synced_at',
        ]);
    }
}