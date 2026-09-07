<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\StaffPermissions;
use PHPUnit\Framework\TestCase;

class StaffPermissionsTest extends TestCase
{
    public function test_super_admin_allows_any_permission(): void
    {
        $user = new User([
            'role' => StaffPermissions::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->assertTrue(StaffPermissions::allows($user, 'clearance.approve'));
        $this->assertTrue(StaffPermissions::allows($user, 'staff.manage'));
    }

    public function test_coordinator_cannot_approve_clearance(): void
    {
        $user = new User([
            'role' => StaffPermissions::CENTRE_COORDINATOR,
            'is_active' => true,
            'study_centre' => 'Mokwa',
        ]);

        $this->assertTrue(StaffPermissions::allows($user, 'clearance.start'));
        $this->assertFalse(StaffPermissions::allows($user, 'clearance.approve'));
        $this->assertTrue(StaffPermissions::isCoordinator($user));
    }

    public function test_inactive_staff_is_denied(): void
    {
        $user = new User([
            'role' => StaffPermissions::BURSAR,
            'is_active' => false,
        ]);

        $this->assertFalse(StaffPermissions::allows($user, 'students.view'));
    }
}
