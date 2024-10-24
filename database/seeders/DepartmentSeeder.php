<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'alias' => 'cardiology',
                'email' => 'cardiology@hospital.com',
                'phone' => '+380441234567',
            ],
            [
                'alias' => 'neurology',
                'email' => 'neurology@hospital.com',
                'phone' => '+380442345678',
            ],
            [
                'alias' => 'orthopedics',
                'email' => 'orthopedics@hospital.com',
                'phone' => '+380443456789',
            ],
            [
                'alias' => 'pediatrics',
                'email' => 'pediatrics@hospital.com',
                'phone' => '+380444567890',
            ],
            [
                'alias' => 'oncology',
                'email' => 'oncology@hospital.com',
                'phone' => '+380445678901',
            ],
            [
                'alias' => 'gastroenterology',
                'email' => 'gastroenterology@hospital.com',
                'phone' => '+380446789012',
            ],
            [
                'alias' => 'dermatology',
                'email' => 'dermatology@hospital.com',
                'phone' => '+380447890123',
            ],
            [
                'alias' => 'endocrinology',
                'email' => 'endocrinology@hospital.com',
                'phone' => '+380448901234',
            ],
            [
                'alias' => 'radiology',
                'email' => 'radiology@hospital.com',
                'phone' => '+380449012345',
            ],
            [
                'alias' => 'urology',
                'email' => 'urology@hospital.com',
                'phone' => '+380440123456',
            ],
        ];

        foreach ($departments as $department) {
            Department::create($department);
        }

    }
}
