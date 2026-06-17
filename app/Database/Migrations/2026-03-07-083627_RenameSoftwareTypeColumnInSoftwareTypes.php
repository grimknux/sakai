<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameSoftwareTypeColumnInSoftwareTypes extends Migration
{
    public function up()
    {
        $fields = [
            'software_type' => [
                'name'       => 'name',
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
        ];

        $this->forge->modifyColumn('software_types', $fields);
    }

    public function down()
    {
        $fields = [
            'name' => [
                'name'       => 'software_type',
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
        ];

        $this->forge->modifyColumn('software_types', $fields);
    }
}