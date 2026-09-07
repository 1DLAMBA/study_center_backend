<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\StaffPermissions;
use Illuminate\Database\Seeder;

class StaffUserSeeder extends Seeder
{
    /**
     * Upsert staff logins from env. Safe to re-run. Do not call DatabaseSeeder
     * on production — that seeder creates fake students/clearances.
     */
    public function run(): void
    {
        $adminEmail = trim((string) env('STAFF_ADMIN_EMAIL', 'admin@coestudycenter.com.ng'));
        $adminPassword = (string) env('STAFF_ADMIN_PASSWORD', 'changeme');

        if ($adminEmail !== '' && $adminPassword !== '') {
            User::updateOrCreate(
                ['email' => $adminEmail],
                [
                    'name' => env('STAFF_ADMIN_NAME', 'Super Admin'),
                    'password' => $adminPassword,
                    'role' => StaffPermissions::SUPER_ADMIN,
                    'study_centre' => null,
                    'is_active' => true,
                ]
            );
        }

        $bursarEmail = trim((string) env('STAFF_BURSAR_EMAIL', 'bursar@coestudycenter.com.ng'));
        $bursarPassword = (string) env('STAFF_BURSAR_PASSWORD', 'changeme');

        if ($bursarEmail !== '' && $bursarPassword !== '') {
            User::updateOrCreate(
                ['email' => $bursarEmail],
                [
                    'name' => env('STAFF_BURSAR_NAME', 'Bursar'),
                    'password' => $bursarPassword,
                    'role' => StaffPermissions::BURSAR,
                    'study_centre' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}
