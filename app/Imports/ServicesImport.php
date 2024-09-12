<?php

namespace App\Imports;

use App\Models\Department\Department;
use App\Models\Doctor\Doctor;
use App\Models\Hospital\Hospital;
use App\Models\HServices;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ServicesImport implements ToModel, WithHeadingRow
{
    /**
     * Importing service method
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $department = Department::whereHas("content", function ($query) use ($row) {
            $query->where('title', $row['department_title']);
        })->first();

        $hospital = Hospital::whereHas('content', function ($query) use ($row) {
            $query->where('title', $row['hospital_title']);
        })->first();

        $user = User::where('name', $row['doctor_name'])
            ->where('surname', $row['doctor_surname'])
            ->first();

        $doctor = Doctor::where('user_id', $user->id)->first();

        if (!$department || !$hospital || !$doctor) {
            return null;
        }

        $service = HServices::create([
            'name' => $row['name'],
            'description' => $row['description'],
            'department_id' => $department->id
        ]);

        $service->hospitals()->attach($hospital->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service->doctors()->attach($doctor->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $service;
    }
}
