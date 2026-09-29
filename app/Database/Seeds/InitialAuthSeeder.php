<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitialAuthSeeder extends Seeder
{
    public function run()
    {
        // Initial superadmin credentials come from .env; there are no defaults.
        $adminUsername = strtolower(trim((string) env('DEFAULT_SUPERADMIN_USERNAME', '')));
        $adminEmail    = trim((string) env('DEFAULT_SUPERADMIN_EMAIL', ''));
        $adminPassword = (string) env('DEFAULT_SUPERADMIN_PASSWORD', '');

        $problems = [];

        if (strlen($adminUsername) < 3 || strlen($adminUsername) > 50) {
            $problems[] = 'DEFAULT_SUPERADMIN_USERNAME must be 3-50 characters';
        }
        if (! filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $problems[] = 'DEFAULT_SUPERADMIN_EMAIL must be a valid email address';
        }
        if (
            strlen($adminPassword) < 8 || strlen($adminPassword) > 72
            || ! preg_match('/[A-Za-z]/', $adminPassword) || ! preg_match('/\d/', $adminPassword)
        ) {
            $problems[] = 'DEFAULT_SUPERADMIN_PASSWORD must be 8-72 characters with a letter and a number';
        }

        if ($problems !== []) {
            throw new \RuntimeException('Cannot seed the superadmin. Set these in .env: ' . implode('; ', $problems) . '.');
        }

        $db = \Config\Database::connect();

        $db->transStart();

        /*
        |--------------------------------------------------------------------------
        | 1. Insert Role
        |--------------------------------------------------------------------------
        */
        $role = [
            'name'        => 'Superadmin',
            'slug'        => 'super-admin',
            'description' => 'Full system access',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => null,
            'deleted_at'  => null,
        ];

        $db->table('roles')->insert($role);

        $superadminRoleId = $db->insertID();

        /*
        |--------------------------------------------------------------------------
        | 2. Insert Permissions
        |--------------------------------------------------------------------------
        */
        $permissions = [
            // dashboard
            [
                'name'       => 'Access Dashboard',
                'slug'       => 'dashboard.access',
                'module'     => 'dashboard',
                'action'     => 'access',
                'created_at' => date('Y-m-d H:i:s'),
            ],

            // users
            [
                'name'       => 'Access Users Page',
                'slug'       => 'users.access',
                'module'     => 'users',
                'action'     => 'access',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'View Users',
                'slug'       => 'users.view',
                'module'     => 'users',
                'action'     => 'view',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Create User',
                'slug'       => 'users.create',
                'module'     => 'users',
                'action'     => 'create',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Update User',
                'slug'       => 'users.update',
                'module'     => 'users',
                'action'     => 'update',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Delete User',
                'slug'       => 'users.delete',
                'module'     => 'users',
                'action'     => 'delete',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Assign User Roles',
                'slug'       => 'users.roles.assign',
                'module'     => 'users',
                'action'     => 'assign_roles',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Toggle Superadmin',
                'slug'       => 'users.superadmin.toggle',
                'module'     => 'users',
                'action'     => 'toggle_superadmin',
                'created_at' => date('Y-m-d H:i:s'),
            ],

            // roles
            [
                'name'       => 'Access Roles Page',
                'slug'       => 'roles.access',
                'module'     => 'roles',
                'action'     => 'access',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'View Roles',
                'slug'       => 'roles.view',
                'module'     => 'roles',
                'action'     => 'view',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Create Roles',
                'slug'       => 'roles.create',
                'module'     => 'roles',
                'action'     => 'create',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Update Roles',
                'slug'       => 'roles.update',
                'module'     => 'roles',
                'action'     => 'update',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Delete Roles',
                'slug'       => 'roles.delete',
                'module'     => 'roles',
                'action'     => 'delete',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Assign Role Permissions',
                'slug'       => 'rolepermissions.assign',
                'module'     => 'roles',
                'action'     => 'assign_permissions',
                'created_at' => date('Y-m-d H:i:s'),
            ],

            // permissions
            [
                'name'       => 'View Permissions',
                'slug'       => 'permissions.view',
                'module'     => 'permissions',
                'action'     => 'view',
                'created_at' => date('Y-m-d H:i:s'),
            ],

            // audit
            [
                'name'       => 'View Audit Logs',
                'slug'       => 'audit.view',
                'module'     => 'audit',
                'action'     => 'view',
                'created_at' => date('Y-m-d H:i:s'),
            ],

            // security
            [
                'name'       => 'Unlock User Account',
                'slug'       => 'security.unlock',
                'module'     => 'security',
                'action'     => 'unlock',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'View Login Attempts',
                'slug'       => 'security.view',
                'module'     => 'security',
                'action'     => 'view',
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $db->table('permissions')->insertBatch($permissions);

        /*
        |--------------------------------------------------------------------------
        | 3. Insert Superadmin User
        |--------------------------------------------------------------------------
        */
        $user = [
            'username'      => $adminUsername,
            'email'         => $adminEmail,
            'password_hash' => password_hash($adminPassword, PASSWORD_ARGON2ID),
            'firstname'     => 'System',
            'lastname'      => 'Administrator',
            'middlename'    => null,
            'suffix'        => null,
            'is_active'     => 1,
            'is_superadmin' => 1,
            'locked_until'  => null,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => null,
            'deleted_at'    => null,
        ];

        $db->table('users')->insert($user);

        $superadminUserId = $db->insertID();

        /*
        |--------------------------------------------------------------------------
        | 4. Assign User → Role
        |--------------------------------------------------------------------------
        */
        $db->table('user_roles')->insert([
            'user_id' => $superadminUserId,
            'role_id' => $superadminRoleId
        ]);

        /*
        |--------------------------------------------------------------------------
        | 5. Assign ALL permissions → Superadmin role
        |--------------------------------------------------------------------------
        */
        $permissions = $db->table('permissions')->get()->getResultArray();

        $rolePermissions = [];

        foreach ($permissions as $permission) {
            $rolePermissions[] = [
                'role_id' => $superadminRoleId,
                'permission_id' => $permission['id']
            ];
        }

        $db->table('role_permissions')->insertBatch($rolePermissions);

        $db->transComplete();
    }
}
