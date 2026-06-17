<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddYearPmsSchedule extends Migration
{
    public function up()
    {
        
        $this->forge->addColumn('pms_schedules', [
            'year' => [
                'type'       => 'VARCHAR',
                'constraint' => 4,
                'after'      => 'id', // optional: change position
            ],
        ]);
        
    }

    public function down()
    {
        $this->forge->dropColumn('pms_schedules', 'year');
    }
}
