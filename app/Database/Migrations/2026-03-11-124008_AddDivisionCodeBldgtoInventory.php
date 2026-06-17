<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDivisionCodeBldgtoInventory extends Migration
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

        $this->forge->addColumn('inventory', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('inventory', ['division_code', 'bldg']);
    }
}
