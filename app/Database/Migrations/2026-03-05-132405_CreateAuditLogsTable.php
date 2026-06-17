<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditLogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],

            // who did it
            'actor_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'actor_username' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],

            // what happened
            'action' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false], // e.g. users.update, roles.delete
            'entity' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],   // users, roles, permissions
            'entity_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true], // store as string for flexibility

            // extra info
            'meta' => ['type' => 'TEXT', 'null' => true], // JSON string

            'created_at' => ['type' => 'DATETIME', 'null' => false],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('actor_user_id');
        $this->forge->addKey('action');
        $this->forge->addKey(['entity', 'entity_id']);
        $this->forge->addKey('created_at');

        // Optional FK
        // $this->forge->addForeignKey('actor_user_id', 'users', 'id', 'SET NULL', 'CASCADE');

        $this->forge->createTable('audit_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('audit_logs', true);
    }
}