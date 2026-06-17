<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePmsQuestionsTable extends Migration
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
            'question_text' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
            ],
            'question_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'yes_no',
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
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
        $this->forge->addKey('question_type');
        $this->forge->addKey('sort_order');
        $this->forge->addKey('is_active');

        $this->forge->createTable('pms_questions', true);
    }

    public function down()
    {
        $this->forge->dropTable('pms_questions', true);
    }
}