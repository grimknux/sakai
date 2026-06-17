<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDeviceTypeTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP',
            'deleted_at DATETIME NULL'
        
        ]);

        $this->forge->addKey('id', true);

        $this->forge->createTable('device_types');
    }

    public function down()
    {
        $this->forge->dropTable('device_types');
    }
}