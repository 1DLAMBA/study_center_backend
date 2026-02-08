<?php

namespace Database\Seeders;

use App\Models\ClearanceDepartment;
use Illuminate\Database\Seeder;

class ClearanceRequestSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            'Library',
            'Bursary',
            'Department',
            'Registry',
        ])->each(function ($name) {
            ClearanceDepartment::firstOrCreate(['name' => $name], ['is_active' => true]);
        });
    }
}
