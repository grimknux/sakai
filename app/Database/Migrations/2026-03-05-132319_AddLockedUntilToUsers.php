<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLockedUntilToUsers extends Migration
{
    public function up()
    {
        $fields = [
            'locked_until' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'is_superadmin',
            ],
        ];

        $this->forge->addColumn('users', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'locked_until');
    }
}