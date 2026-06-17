<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateInventorySoftwareHistoryTable extends Migration
{
    public function up()
    {
        // 1. Drop ms_office_version
        $this->forge->dropColumn('inventory_software_history', 'ms_office_version');

        // 2. Rename operating_system → software_id
        $fields = [
            'operating_system' => [
                'name'       => 'software_id',
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
        ];

        $this->forge->modifyColumn('inventory_software_history', $fields);

        // 3. Add foreign key
        $this->forge->addForeignKey(
            'software_id',
            'softwares',
            'id',
            'CASCADE',
            'RESTRICT'
        );
    }

    public function down()
    {
        // revert rename
        $fields = [
            'software_id' => [
                'name'       => 'operating_system',
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
        ];

        $this->forge->modifyColumn('inventory_software_history', $fields);

        // restore removed column
        $this->forge->addColumn('inventory_software_history', [
            'ms_office_version' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'operating_system',
            ],
        ]);
    }
}