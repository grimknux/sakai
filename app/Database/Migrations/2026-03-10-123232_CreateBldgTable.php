<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBldgTable extends Migration
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
                'constraint' => 50,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('bldg');

        $data = [
            ['name' => 'A', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'B', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'C', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'D', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'E', 'created_at' => date('Y-m-d H:i:s')],
        ];

        $this->db->table('bldg')->insertBatch($data);
    }

    public function down()
    {
        $this->forge->dropTable('bldg');
    }
}