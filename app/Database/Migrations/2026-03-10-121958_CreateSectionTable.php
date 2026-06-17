<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSectionTable extends Migration
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
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'shortname' => [ // ✅ NEW COLUMN
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'name',
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'division_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'bldg' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('section');

        $data = [
            ['name'=>'Office of Regional Director','shortname'=>'RDs Office','code'=>'RD','division_code'=>'RDARD', 'bldg' => ''],
            ['name'=>'Office of Assistant Regional Director','shortname'=>'ARDs Office','code'=>'ARD','division_code'=>'RDARD', 'bldg' => ''],
            ['name'=>'Office of Chief Administrative Officer','shortname'=>'OCAO','code'=>'OCAO','division_code'=>'MSD', 'bldg' => ''],
            ['name'=>'Office of Supervising Administrative Officer','shortname'=>'SAO','code'=>'SAO','division_code'=>'MSD', 'bldg' => ''],
            ['name'=>'Budget Section','shortname'=>'BUDGET','code'=>'BUDGET','division_code'=>'MSD', 'bldg' => ''],
            ['name'=>'Cashier Unit','shortname'=>'CASHIER','code'=>'CASHIER','division_code'=>'MSD', 'bldg' => ''],
            ['name'=>'Accounting Unit','shortname'=>'ACCOUNTING','code'=>'ACCOUNTING','division_code'=>'MSD', 'bldg' => ''],
            ['name'=>'Records Section','shortname'=>'RECORDS','code'=>'RECORDS','division_code'=>'MSD', 'bldg' => ''],
            ['name'=>'Procurement Section','shortname'=>'PROCUREMENT','code'=>'PROCUREMENT','division_code'=>'MSD', 'bldg' => ''],
            ['name'=>'General Services Section','shortname'=>'GSS','code'=>'GSS','division_code'=>'MSD', 'bldg' => ''],
            ['name'=>'Public Health Information and Referral Unit','shortname'=>'PHIARU','code'=>'PHIARU','division_code'=>'MSD', 'bldg' => ''],
            ['name'=>'Personnel Section','shortname'=>'PERSONNEL','code'=>'PERSONNEL','division_code'=>'MSD', 'bldg' => ''],
            ['name'=>'Human Resource Development Unit','shortname'=>'HRDU','code'=>'HRDU','division_code'=>'MSD', 'bldg' => ''],
            ['name'=>'Warehouse Unit','shortname'=>'WAREHOUSE','code'=>'WAREHOUSE','division_code'=>'MSD', 'bldg' => ''],
            ['name'=>'Information and Communications Technology Unit','shortname'=>'ICTU','code'=>'ICTU','division_code'=>'MSD', 'bldg' => ''],
            ['name'=>'Regulations, Licensing, and Enforcement Division','shortname'=>'RLED','code'=>'RLED','division_code'=>'RLED', 'bldg' => ''],
            ['name'=>'Local Health Support System','shortname'=>'LHSS','code'=>'LHSS','division_code'=>'LHSD', 'bldg' => ''],
            ['name'=>'Regional Epidemiology and Statistics Unit','shortname'=>'RESU','code'=>'RESU','division_code'=>'LHSD', 'bldg' => ''],
            ['name'=>'Environmental and Occupational Health Unit','shortname'=>'EOH','code'=>'EOH','division_code'=>'LHSD', 'bldg' => ''],
            ['name'=>'Bids and Awards Committee','shortname'=>'BAC','code'=>'BAC','division_code'=>'MSD', 'bldg' => ''],
            ['name'=>'Medical Assistance IP','shortname'=>'MAIP','code'=>'MAIP','division_code'=>'MSD', 'bldg' => ''],
            ['name'=>'Legal Office','shortname'=>'LEGAL','code'=>'LEGAL','division_code'=>'MSD', 'bldg' => ''],
            ['name'=>'Planning Office','shortname'=>'PLANNING','code'=>'PLANNING','division_code'=>'RDARD', 'bldg' => ''],
            ['name'=>'Family Health Unit','shortname'=>'FHU','code'=>'FHU','division_code'=>'LHSD', 'bldg' => ''],
            ['name'=>'Health Facilities and Environmental Program - Equipment','shortname'=>'HFEP-Eqp','code'=>'HFEP-Eqp','division_code'=>'LHSD', 'bldg' => ''],
            ['name'=>'Office of Local Health Support Division Chief','shortname'=>'LHSDCHIEF','code'=>'LHSDCHIEF','division_code'=>'LHSD', 'bldg' => ''],
            ['name'=>'Health Promotion Unit','shortname'=>'HPU','code'=>'HPU','division_code'=>'LHSD', 'bldg' => ''],
            ['name'=>'Non-Communicable Diseases Cluster','shortname'=>'NCD','code'=>'NCD','division_code'=>'LHSD', 'bldg' => ''],
            ['name'=>'Communicable Diseases Cluster','shortname'=>'CDU','code'=>'CDU','division_code'=>'LHSD', 'bldg' => ''],
            ['name'=>'Commission on Audit','shortname'=>'COA','code'=>'COA','division_code'=>'MSD', 'bldg' => ''],
            ['name'=>'National Voluntary Blood Services Program','shortname'=>'NVBSP','code'=>'NVBSP','division_code'=>'LHSD', 'bldg' => ''],
            ['name'=>'Cold Chain Management','shortname'=>'COLDCHAIN','code'=>'COLDCHAIN','division_code'=>'MSD', 'bldg' => ''],
            ['name'=>'Pharmacy Division','shortname'=>'PHARMA','code'=>'PHARMA','division_code'=>'LHSD', 'bldg' => ''],
            ['name'=>'Health Facility Development Unit','shortname'=>'HFDU','code'=>'HFDU','division_code'=>'LHSD', 'bldg' => ''],
            ['name'=>'Heath Emergency Management Section','shortname'=>'HEMS','code'=>'HEMS','division_code'=>'LHSD', 'bldg' => ''],
            ['name'=>'Field Health Services Information Systen','shortname'=>'FHSIS','code'=>'FHSIS','division_code'=>'LHSD', 'bldg' => ''],
            ['name'=>'Dangerous Drug Abuse Prevention and Treatment Program','shortname'=>'DDAPTP','code'=>'DDAPTP','division_code'=>'LHSD', 'bldg' => ''],
            ['name'=>'PDOHO - Ilocos Norte','shortname'=>'PDOHO-IN','code'=>'PDOHO-IN','division_code'=>'RDARD', 'bldg' => ''],
            ['name'=>'PDOHO - Ilocos Sur','shortname'=>'PDOHO-IS','code'=>'PDOHO-IS','division_code'=>'RDARD', 'bldg' => ''],
            ['name'=>'PDOHO - La Union','shortname'=>'PDOHO-LU','code'=>'PDOHO-LU','division_code'=>'RDARD', 'bldg' => ''],
            ['name'=>'PDOHO - Pangasinan','shortname'=>'PDOHO-PANG','code'=>'PDOHO-PANG','division_code'=>'RDARD', 'bldg' => ''],
            ['name'=>'Health Facilities and Enhancement Program - Infrastructure','shortname'=>'HFEP-Infra','code'=>'HFEP-Infra','division_code'=>'RDARD', 'bldg' => ''],
            ['name'=>'Communications Management Unit','shortname'=>'CMU','code'=>'CMU','division_code'=>'RDARD', 'bldg' => ''],
            ['name'=>'Ilocos Sur Medical Center','shortname'=>'ISMC','code'=>'ISMC','division_code'=>'RDARD', 'bldg' => ''],
            ['name'=>'Promotion Selection Board','shortname'=>'PSB','code'=>'PSB','division_code'=>'RDARD', 'bldg' => ''],
            ['name'=>'Disease Prevention and Control Section','shortname'=>'DPCS','code'=>'DPCS','division_code'=>'LHSD', 'bldg' => ''],
        ];

        foreach ($data as &$row) {
            $row['created_at'] = date('Y-m-d H:i:s');
        }

        $this->db->table('section')->insertBatch($data);
    }

    public function down()
    {
        $this->forge->dropTable('section');
    }
}