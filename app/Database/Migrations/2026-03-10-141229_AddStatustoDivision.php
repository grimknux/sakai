<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatusToDivision extends Migration
{
    public function up()
    {
        $fields = [
            'status' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
                'after'      => 'code',
            ]
        ];

        $this->forge->addColumn('division', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('division', 'status');
    }
}