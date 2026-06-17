<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePasswordResets extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],

            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],

            // Store only HASH of the token for security
            'token_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => false], // sha256 hex

            'expires_at' => ['type' => 'DATETIME', 'null' => false],
            'used_at'    => ['type' => 'DATETIME', 'null' => true],

            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],

            'created_at' => ['type' => 'DATETIME', 'null' => false],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('token_hash', false, true); // unique
        $this->forge->addKey(['user_id', 'created_at']);
        $this->forge->addKey('expires_at');

        // Optional FK (enable if you want strict integrity)
        // $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('password_resets', true);
    }

    public function down()
    {
        $this->forge->dropTable('password_resets', true);
    }
}