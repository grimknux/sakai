<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatusColumnSection extends Migration
{
    public function up()
    {
        $this->forge->addColumn('section', [
            'status' => [
                'type'       => 'INT',
                'after'      => 'bldg',
                'default'    => 1
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('section', 'status');
    }
}
