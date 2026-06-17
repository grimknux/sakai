<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDeletedAtUpdatedAtSection extends Migration
{
    public function up()
    {
        $this->forge->addColumn('section', [
            'updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP',
            'deleted_at DATETIME NULL'
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn(
            'section',
            ['updated_at', 'deleted_at']
        );
    }
}
