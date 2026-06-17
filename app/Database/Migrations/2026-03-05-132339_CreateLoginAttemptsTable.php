<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLoginAttemptsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'username' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true], // supports IPv6
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'success' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true], // invalid_password, locked, etc
            'attempted_at' => ['type' => 'DATETIME', 'null' => false],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('username');
        $this->forge->addKey('ip_address');
        $this->forge->addKey('attempted_at');

        // Optional FK: if you want strict integrity
        // $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');

        $this->forge->createTable('login_attempts', true);
    }

    public function down()
    {
        $this->forge->dropTable('login_attempts', true);
    }
}