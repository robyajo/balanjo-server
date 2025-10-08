<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'get-role',
            'create-role',
            'show-role',
            'edit-role',
            'delete-role',

            'get-permission',
            'create-permission',
            'show-permission',
            'edit-permission',
            'delete-permission',

            'get-user',
            'create-user',
            'show-user',
            'edit-user',
            'delete-user',
        ];

        // Looping and Inserting Array's Permissions into Permission Table
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    }
}
