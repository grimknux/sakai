<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuthTables extends Migration
{
    public function up()
    {
        // USERS
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'username' => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 150, 'unique' => true],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'firstname' => ['type' => 'VARCHAR', 'constraint' => 100],
            'lastname' => ['type' => 'VARCHAR', 'constraint' => 100],
            'middlename' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'suffix' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'default' => 1],
            'is_superadmin' => ['type' => 'TINYINT', 'default' => 0],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP',
            'deleted_at DATETIME NULL'
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('users');

        // ROLES
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100, 'unique' => true],
            'slug' => ['type' => 'VARCHAR', 'constraint' => 100, 'unique' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP',
            'deleted_at DATETIME NULL'
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('roles');

        // PERMISSIONS
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'slug' => ['type' => 'VARCHAR', 'constraint' => 150, 'unique' => true],
            'module' => ['type' => 'VARCHAR', 'constraint' => 100],
            'action' => ['type' => 'VARCHAR', 'constraint' => 50],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP'
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('permissions');

        // USER_ROLES
        $this->forge->addField([
            'user_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'role_id' => ['type' => 'BIGINT', 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey(['user_id', 'role_id']);
        $this->forge->createTable('user_roles');

        // ROLE_PERMISSIONS
        $this->forge->addField([
            'role_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'permission_id' => ['type' => 'BIGINT', 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey(['role_id', 'permission_id']);
        $this->forge->createTable('role_permissions');
    }

    public function down()
    {
        $this->forge->dropTable('role_permissions');
        $this->forge->dropTable('user_roles');
        $this->forge->dropTable('permissions');
        $this->forge->dropTable('roles');
        $this->forge->dropTable('users');
    }
}