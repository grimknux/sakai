<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDivisionTable extends Migration
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
                'constraint' => 255,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'unique'     => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true); // primary key
        $this->forge->createTable('division');

        // Insert default values
        $data = [
            [
                'name' => 'Regional and Assistant Regional Director Division',
                'code' => 'RDARD',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Local Health Support Division',
                'code' => 'LHSD',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Regulations, Licensing and Enforcement Division',
                'code' => 'RLED',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Management Support Division',
                'code' => 'MSD',
                'created_at' => date('Y-m-d H:i:s')
            ],
        ];

        $this->db->table('division')->insertBatch($data);
    }

    public function down()
    {
        $this->forge->dropTable('division');
    }
}