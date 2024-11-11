<?php

namespace App\Imports;

use App\Models\Department\Department;
use App\Models\Doctor\Doctor;
use App\Models\Role;
use App\Models\User\User;
use App\Notifications\DoctorCredentialsNotification;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DoctorImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     */
    public function model(array $row)
    {
        $password = Str::password($length = 12, $letters = true, $numbers = true, $symbols = true);

        $user = User::where("email", $row["email"])->first();
        if (!$user) {
            $user = User::create([
                'email' => $row['email'],
                'name' => $row['name'],
                'surname' => $row['surname'],
                'phone' => $row['phone'],
                'password' => bcrypt($password),
            ]);

            $userRole = Role::where('title', 'user')->value('id');
            $doctorRole = Role::where('title', 'doctor')->value('id');

            if ($user) {
                $user->roles()->attach($userRole, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $user->roles()->attach($doctorRole, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $existedDoctor = Doctor::where('user_id', $user->id)->first();
        if ($existedDoctor) {
            return response()->json([
                'status' => 'error',
                'message' => "The doctor for this user id {$user->id} has already been created"
            ], 500);
        }

        if ($user->hospital_id !== $row['hospital_id']) {
            return response()->json([
                'status' => 'error',
                'message' => 'The user has already been linked to another hospital'
            ], 500);
        }

        $doctor = Doctor::create([
            'user_id' => $user->id,
            'specialization' => $row['specialization']
        ]);

        $user->update([
            'hospital_id' => $row['hospital_id']
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
        $user->notify(new DoctorCredentialsNotification($user->email, $password));
        return $doctor;
    }
}
