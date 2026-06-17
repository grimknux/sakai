<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSectionCodeColumnPmsRecords extends Migration
{
    public function up()
    {
        $this->forge->addColumn('pms_records', [
            'section_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'conducted_by',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('pms_records', 'section_code');
    }
}
