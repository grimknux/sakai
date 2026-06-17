<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddShortnameToSoftwareTypes extends Migration
{
    public function up()
    {
        $fields = [
            'shortname' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'name',
            ],
        ];

        $this->forge->addColumn('software_types', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('software_types', 'shortname');
    }
}