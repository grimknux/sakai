<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePmsSchedulesTable extends Migration
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
            'semester' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'schedule_start' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'schedule_end' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'attachment_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'attachment_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'remarks' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
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
        $this->forge->addKey('semester');
        $this->forge->addKey('schedule_start');
        $this->forge->addKey('schedule_end');
        $this->forge->addKey('created_by');

        $this->forge->createTable('pms_schedules', true);
    }

    public function down()
    {
        $this->forge->dropTable('pms_schedules', true);
    }
}