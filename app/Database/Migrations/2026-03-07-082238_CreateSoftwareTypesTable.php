<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSoftwareTypesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'software_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('software_type');

        $this->forge->createTable('software_types');
    }

    public function down()
    {
        $this->forge->dropTable('software_types');
    }
}