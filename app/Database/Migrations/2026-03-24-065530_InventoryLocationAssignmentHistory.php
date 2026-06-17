<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class inventoryLocationAssignmentHistory extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'inventory_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'source_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'source_reference_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'pms_schedule_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'changed_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],

            'old_section_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'old_division_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'old_bldg' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'old_end_user' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'old_current_user' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],

            'new_section_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'new_division_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'new_bldg' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'new_end_user' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'new_current_user' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],

            'remarks' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            
            'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('inventory_id');
        $this->forge->addKey('source_type');
        $this->forge->addKey('source_reference_id');
        $this->forge->addKey('pms_schedule_id');
        $this->forge->addKey('changed_by');
        $this->forge->addKey('changed_at');
        $this->forge->addForeignKey('inventory_id', 'inventory', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('inventory_location_assignment_history', true);
    }

    public function down()
    {
        $this->forge->dropTable('inventory_location_assignment_history', true);
    }
}