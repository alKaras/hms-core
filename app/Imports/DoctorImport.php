<?php

namespace App\Imports;

use App\Models\Department\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;
use App\Models\Doctor\Doctor;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DoctorImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     */
    public function model(array $row)
    {
        $password = Str::password($length = 12, $letters = true, $numbers = true, $symbols = true);
        $user = User::firstOrCreate([
            ['email' => $row['email']],
            [
                'name' => $row['name'],
                'surname' => $row['surname'],
                'phone' => $row['phone'],
                'password' => bcrypt($password),
            ]
        ]);

        $neccessaryRoles = Role::whereIn('title', ['user', 'doctor'])->get();

        if ($neccessaryRoles->isEmpty()) {
            return response()->json([
                'status' => 'failure',
                'message' => 'No valid rows for provided roles',
            ]);
        }

        if ($user) {
            $now = now();
            $syncData = $neccessaryRoles->pluck('id')->mapWithKeys(function ($roleId) use ($now) {
                return [$roleId => ['created_at' => $now, 'updated_at' => $now]];
            });
            $user->roles()->sync($syncData);
        }

        $doctor = Doctor::create([
            'user_id' => $user->id,
            'specialization' => $row['specialization'],
        ]);

        if (!empty($row['departments'])) {
            $departmentTitles = array_map('trim', explode(',', $row['departments']));

            $departments = Department::whereHas('content', function ($query) use ($departmentTitles) {
                $query->whereIn('title', $departmentTitles);
            })->get();

            if ($departments->isNotEmpty()) {
                $doctor->departments()->attach($departments, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        return $doctor;
    }
}
