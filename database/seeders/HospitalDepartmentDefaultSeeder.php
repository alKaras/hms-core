<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class HospitalDepartmentDefaultSeeder extends Seeder
{
    public static $defaultHospitalId = 1;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = Department::all();

        foreach ($departments as $department) {
            DB::table('hospital_department')->insert([
                'hospital_id' => self::$defaultHospitalId,
                'department_id' => $department->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
