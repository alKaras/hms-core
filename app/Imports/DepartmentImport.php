<?php

namespace App\Imports;


use App\Models\Hospital\Hospital;
use Illuminate\Support\Facades\DB;
use App\Models\Department\Department;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DepartmentImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     */
    public function model(array $row)
    {
        $existedDepartment = Department::where("alias", $row['alias'])->exists();

        $hospital = Hospital::find($row['hospital_id']);

        if (!$hospital || $existedDepartment) {
            throw ValidationException::withMessages([
                'status' => 'failure',
                'message' => "An error occurred importing department: Hospital does not exist or department already exists."
            ]);
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
