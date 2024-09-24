<?php

namespace App\Imports;

use Exception;
use App\Models\User;
use App\Models\HServices;
use App\Models\Doctor\Doctor;
use App\Models\Hospital\Hospital;
use App\Models\Department\Department;
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
            $query->where('title', trim($row['department_title']));
        })->first();

        $hospital = Hospital::whereHas('content', function ($query) use ($row) {
            $query->where('title', trim($row['hospital_title']));
        })->first();

        $user = User::where('email', $row['doctor_email'])
            ->first();

        $doctor = Doctor::where('user_id', $user->id)->first();

        if (!$department || !$hospital || !$doctor) {
            throw new Exception('Error occurred while finding hospital doctor or department');
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
