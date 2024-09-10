<?php

namespace Database\Seeders;

use App\Models\HospitalContent;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class HospitalContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        HospitalContent::factory(10)->create();
    }
}
