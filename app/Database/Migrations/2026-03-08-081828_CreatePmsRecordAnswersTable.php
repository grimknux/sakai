<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePmsRecordAnswersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'pms_record_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'question_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'question_text_snapshot' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
            ],
            'answer' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
            ],
            'remarks' => [
                'type' => 'TEXT',
                'null' => true,
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
        $this->forge->addKey('pms_record_id');
        $this->forge->addKey('question_id');

        $this->forge->createTable('pms_record_answers', true);
    }

    public function down()
    {
        $this->forge->dropTable('pms_record_answers', true);
    }
}