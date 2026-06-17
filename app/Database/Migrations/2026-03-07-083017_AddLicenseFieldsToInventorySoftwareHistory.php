<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLicenseFieldsToInventorySoftwareHistory extends Migration
{
    public function up()
    {
        $this->forge->addColumn('inventory_software_history', [
            'license_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'software_id',
            ],
            'license_type' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'after'      => 'license_key',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn(
            'inventory_software_history',
            ['license_key', 'license_type']
        );
    }
}