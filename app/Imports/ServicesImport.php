<?php

namespace App\Imports;

use App\Models\Department\Department;
use App\Models\Doctor\Doctor;
use App\Models\Hospital\Hospital;
use App\Models\HServices;
use App\Models\User\User;
use Exception;
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
        //Skip when row is null
        if (empty(array_filter($row))) {
            \Log::info('Skipping empty row:', $row);
            return null;
        }

        $hospital = Hospital::whereHas('content', function ($query) use ($row) {
            $query->where('title', trim($row['hospital_title']));
        })->first();


        $dp = Department::whereHas("content", function ($query) use ($row) {
            $query->where('title', trim($row['department_title']));
        })->first();

        $department = $dp->hospitals()->where('hospital_id', $hospital->id)->first();


        $user = User::where('email', $row['doctor_email'])
            ->first();

        // $doctor = Doctor::where('user_id', $user->id)->where('hospital_id', '=', $hospital->id)->first();
        $doctor = Doctor::where('user_id', $user->id)
            ->whereHas('user', function ($query) use ($hospital) {
                $query->where('hospital_id', $hospital->id);
            })
            ->first();

        if (!$department || !$hospital || !$doctor) {
            throw new Exception('Error occurred while finding hospital doctor or department');
        }

        // $existedHospitalService = HServices::where('name', $row['name'])->first();
        $existedHospitalService = HServices::with('hospitals')->where('name', $row['name'])->first();
        $excludeHospitalId = $hospital->id;
        $excludeDoctorId = $doctor->id;

        if ($existedHospitalService) {

            $existingHospitalRelation = $existedHospitalService->hospitals()->where('hospital_id', $excludeHospitalId)->exists();
            $existingDoctorRelation = $existedHospitalService->doctors()->where('doctor_id', $excludeDoctorId)->exists();

            if ($existingHospitalRelation || $existingDoctorRelation) {
                // If the hospital or doctor is already linked, throw an exception
                throw new \Exception("This service is already linked to the provided hospital or doctor.");
            }

            $existedHospitalService->hospitals()->attach($hospital->id, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $existedHospitalService->doctors()->attach($doctor->id, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        } else {
            $newService = HServices::create([
                'name' => $row['name'],
                'description' => $row['description'],
                'department_id' => $dp->id,
            ]);

            $newService->hospitals()->attach($hospital->id, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $newService->doctors()->attach($doctor->id, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $newService;
        }
    }
}
