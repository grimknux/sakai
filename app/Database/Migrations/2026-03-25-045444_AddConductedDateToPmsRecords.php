<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddConductedDateToPmsRecords extends Migration
{
    public function up()
    {
        $this->forge->addColumn('pms_records', [
            'conducted_date' => [
                'type'       => 'DATE',
                'null'       => true, // allow null if optional
                'after'      => 'conducted_by', // optional: change position
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('pms_records', 'conducted_date');
    }
}