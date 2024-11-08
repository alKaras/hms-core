<?php

namespace App\Imports;


use Carbon\Carbon;
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
        $existedDepartment = Department::where("alias", $row['alias'])->first();

        $hospital = Hospital::find($row['hospital_id']);

        if (!$hospital) {
            throw ValidationException::withMessages([
                'status' => 'error',
                'message' => "An error occurred importing department: Hospital does not exist"
            ]);
        }


        if ($existedDepartment) {
            if (!$existedDepartment->hospitals->contains($hospital->id)) {
                DB::table('hospital_departments')->insert([
                    'department_id' => $existedDepartment->id,
                    'hospital_id' => $hospital->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            }

            return $existedDepartment;
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
