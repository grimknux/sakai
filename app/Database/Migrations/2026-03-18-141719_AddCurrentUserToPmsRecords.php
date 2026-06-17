<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCurrentUserToPmsRecords extends Migration
{
    public function up()
    {
        $this->forge->addColumn('pms_records', [
            'current_user' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'end_user', // places column after end_user
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('pms_records', 'current_user');
    }
}