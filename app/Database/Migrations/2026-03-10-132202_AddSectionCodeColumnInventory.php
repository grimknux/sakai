<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSectionCodeColumnInventory extends Migration
{
    public function up()
    {
        $this->forge->addColumn('inventory', [
            'section_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'year',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('inventory', 'section_code');
    }
}
