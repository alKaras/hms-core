<?php

namespace Database\Seeders;

use App\Models\HServices;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class HospitalServicesDefaultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = HServices::all();

        foreach ($services as $service) {
            DB::table('hospital_services')->insert([
                'hospital_id' => 1,
                'service_id' => $service->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
