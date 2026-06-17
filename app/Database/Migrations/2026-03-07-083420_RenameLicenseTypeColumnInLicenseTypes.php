<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameLicenseTypeColumnInLicenseTypes extends Migration
{
    public function up()
    {
        $fields = [
            'license_type' => [
                'name'       => 'name',
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
        ];

        $this->forge->modifyColumn('license_types', $fields);
    }

    public function down()
    {
        $fields = [
            'name' => [
                'name'       => 'license_type',
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
        ];

        $this->forge->modifyColumn('license_types', $fields);
    }
}