<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDeletedAtToInventorySoftwareHistory extends Migration
{
    public function up()
    {
        $fields = [
            'deleted_at DATETIME NULL'
        ];

        $this->forge->addColumn('inventory_software_history', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('inventory_software_history', 'deleted_at');
    }
}