<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsActiveInventory extends Migration
{
    public function up()
    {
        $fields = [
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
                'after'      => 'status',
            ]
        ];

        $this->forge->addColumn('inventory', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('inventory', 'status');
    }
}
