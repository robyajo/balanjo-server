<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = User::create([
            'uuid' => Str::uuid(),
            'name' => 'Super Admin',
            'email' => 's@s.com',
            'password' => bcrypt('string'),
            // 'avatar' => 'https://cdn-icons-png.flaticon.com/512/149/149071.png',
            'phone' => '6282386825834',
            'city' => 'Pekanbaru',
            'address' => 'Jl. Hangtuah Ujung',
            'active' => 'active',
            'profile' => 'active',
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('Super Admin');

        $rAdmin = User::create([
            'uuid' => Str::uuid(),
            'name' => 'Roby',
            'email' => 'r@r.com',
            'password' => bcrypt('string'),
            // 'avatar' => 'https://cdn-icons-png.flaticon.com/512/149/149071.png',
            'phone' => '6282386825834',
            'city' => 'Pekanbaru',
            'address' => 'Jl. Hangtuah Ujung',
            'active' => 'active',
            'profile' => 'active',
            'email_verified_at' => now(),
        ]);
        $rAdmin->assignRole('Admin');

        $pAdmin = User::create([
            'uuid' => Str::uuid(),
            'name' => 'Putri',
            'email' => 'p@p.com',
            'password' => bcrypt('string'),
            // 'avatar' => 'https://cdn-icons-png.flaticon.com/512/149/149071.png',
            'phone' => '6282172766306',
            'city' => 'Pekanbaru',
            'address' => 'Jl. Hangtuah Ujung',
            'active' => 'active',
            'profile' => 'active',
            'email_verified_at' => now(),
        ]);
        $pAdmin->assignRole('Admin');
    }
}
