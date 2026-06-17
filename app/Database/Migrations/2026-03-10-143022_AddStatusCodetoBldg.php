<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatusCodetoBldg extends Migration
{
    public function up()
    {
        $fields = [
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
                'after'      => 'name'
            ],
            'status' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
                'after'      => 'code',
            ]
        ];

        $this->forge->addColumn('bldg', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('bldg', ['code', 'status']);
    }
}
