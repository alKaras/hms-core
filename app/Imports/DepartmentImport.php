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
    public function model(array $row)
    {
        //Skip when row is null
        if (empty(array_filter($row))) {
            \Log::info('Skipping empty row:', $row);
            return null;
        }

        $hospital = Hospital::whereHas('content', function ($query) use ($row) {
            $query->where('title', trim($row['hospital_title']));
        })->first();


        if (!$hospital) {
            throw new \Exception('Hospital not found: ' . $row['hospital_title']);
        }

        $existingDepartment = Department::whereHas("content", function ($query) use ($row) {
            $query->where('title', trim($row['title']));
        })->first();


        if ($existingDepartment) {
            $departmentRelationExists = $existingDepartment->hospitals()
                ->where('hospital_id', $hospital->id)
                ->exists();

            if (!$departmentRelationExists) {
                $existingDepartment->hospitals()->attach($hospital->id, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $existingDepartment;
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

            $department->hospitals()->attach($hospital->id, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return $department;
    }
}
