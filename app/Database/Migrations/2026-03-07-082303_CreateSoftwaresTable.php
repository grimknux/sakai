<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSoftwaresTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'software_type_id' => [
                'type' => 'INT',
                'unsigned' => true,
            ],
            'version' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
            ],
            'status' => [
                'type' => 'TINYINT',
                'default' => 1,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP',
            'deleted_at DATETIME NULL'
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('software_type_id');

        $this->forge->addForeignKey(
            'software_type_id',
            'software_types',
            'id',
            'CASCADE',
            'RESTRICT'
        );

        $this->forge->createTable('softwares');
    }

    public function down()
    {
        $this->forge->dropTable('softwares');
    }
}