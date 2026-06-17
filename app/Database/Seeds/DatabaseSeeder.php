<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call('InitialAuthSeeder');
        $this->call('DeviceTypePermissionSeeder');
        $this->call('AssetPermissionSeeder');
        $this->call('SuperadminAssetRolePermissionSeeder');
        $this->call('InventorySoftwareSeeder');
        $this->call('PmsPermissionSeeder');
        $this->call('PmsViewAttachmentPermissionSeeder');
        $this->call('PmsAccessSeeder');
        $this->call('PermissionSeederOrg');
    }
}