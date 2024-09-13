<?php

namespace App\Imports;


use App\Models\Hospital\Hospital;
use Illuminate\Support\Facades\DB;
use App\Models\Department\Department;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DepartmentImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     */
    public function model(array $row)
    {
        if (Department::where("alias", $row['alias'])->get()) {
            return response()->json([
                'status' => 'failure',
                'message' => 'The department for this alias exists',
            ]);
        }

        $hospital = Hospital::find($row['hospital_id']);

        if (!$row['hospital_id'] || !$hospital) {
            return response()->json([
                'status' => 'failure',
                'message' => 'Hospital id not provided or there\'s  no hospitals for provided id',
            ], 404);
        }

        $department = Department::create([
            'alias' => $row['alias'],
            'email' => $row['email'],
            'phone' => $row['phone'],
        ]);

        if ($department) {
            DB::table('department_content')->insert([
                'department_id' => $department->id,
                'title' => $row['title'],
                'description' => $row['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $department->hospitals()->attach($hospital, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return $department;
    }
}
