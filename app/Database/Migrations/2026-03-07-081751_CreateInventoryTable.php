<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInventoryTable extends Migration
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
            'device_type_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'property_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'year' => [
                'type'       => 'VARCHAR',
                'constraint' => 4,
                'null'       => true,
            ],
            'end_user' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'current_user' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'brand_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'serial_num' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'ict_tag' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP',
            'deleted_at DATETIME NULL'
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('device_type_id');
        $this->forge->addUniqueKey('property_number');
        $this->forge->addUniqueKey('serial_num');
        $this->forge->addUniqueKey('ict_tag');

        $this->forge->addForeignKey('device_type_id', 'device_types', 'id', 'CASCADE', 'RESTRICT');

        $this->forge->createTable('inventory');
    }

    public function down()
    {
        $this->forge->dropTable('inventory');
    }
}