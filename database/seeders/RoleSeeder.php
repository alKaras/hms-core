<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['title' => 'user', 'priority' => 0],
            ['title' => 'admin', 'priority' => 3],
            ['title' => 'manager', 'priority' => 2],
            ['title' => 'doctor', 'priority' => 1]
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['title' => $role['title']],
                ['priority' => $role['priority']]
            );
        }
    }
}
