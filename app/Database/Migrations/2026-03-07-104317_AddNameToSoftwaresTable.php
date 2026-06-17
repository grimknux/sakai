<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNameToSoftwaresTable extends Migration
{
    public function up()
    {
        $fields = [
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'after'      => 'software_type_id',
            ],
        ];

        $this->forge->addColumn('softwares', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('softwares', 'name');
    }
}