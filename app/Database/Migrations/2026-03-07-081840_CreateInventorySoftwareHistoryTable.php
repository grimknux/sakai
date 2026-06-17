<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInventorySoftwareHistoryTable extends Migration
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
            'inventory_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'operating_system' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'ms_office_version' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'effective_from' => [
                'type' => 'DATE',
            ],
            'effective_to' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('inventory_id');
        $this->forge->addForeignKey('inventory_id', 'inventory', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('inventory_software_history');
    }

    public function down()
    {
        $this->forge->dropTable('inventory_software_history');
    }
}