<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDivisionCodeAndBldgToPmsRecordTable extends Migration
{
    public function up()
    {
        $fields = [
            'division_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'after'      => 'section_code'
            ],
            'bldg' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'after'      => 'division_code'
            ]
        ];

        $this->forge->addColumn('pms_records', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('pms_records', ['division_code', 'bldg']);
    }
}
