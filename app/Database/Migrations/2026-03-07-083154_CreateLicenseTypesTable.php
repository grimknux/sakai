<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLicenseTypesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'license_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('license_type');

        $this->forge->createTable('license_types');

        // Insert default values
        $data = [
            ['license_type' => 'OEM'],
            ['license_type' => 'Volume'],
            ['license_type' => 'Retail'],
            ['license_type' => 'Subscription'],
            ['license_type' => 'Trial'],
        ];

        $this->db->table('license_types')->insertBatch($data);
    }

    public function down()
    {
        $this->forge->dropTable('license_types');
    }
}