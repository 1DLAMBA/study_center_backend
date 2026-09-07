<?php

namespace App\Support;

use App\Models\User;

class StaffPermissions
{
    public const SUPER_ADMIN = 'super_admin';
    public const BURSAR = 'bursar';
    public const CENTRE_COORDINATOR = 'centre_coordinator';

    public const ALL = '*';

    /**
     * @var array<string, list<string>>
     */
    public const ROLE_PERMISSIONS = [
        self::SUPER_ADMIN => [self::ALL],
        self::BURSAR => [
            'students.view',
            'students.manage',
            'applications.view',
            'applications.manage',
            'graduation.view',
            'graduation.upload',
            'clearance.view',
            'clearance.start',
            'clearance.approve',
            'staff.manage',
            'stats.view',
            'payments.view',
        ],
        self::CENTRE_COORDINATOR => [
            'students.view',
            'students.manage',
            'graduation.view',
            'graduation.upload',
            'clearance.view',
            'clearance.start',
        ],
    ];

    public static function roles(): array
    {
        return array_keys(self::ROLE_PERMISSIONS);
    }

    public static function forRole(?string $role): array
    {
        return self::ROLE_PERMISSIONS[$role] ?? [];
    }

    public static function allows(?User $user, string $permission): bool
    {
        if (! $user || ! $user->is_active) {
            return false;
        }

        $granted = self::forRole($user->role);

        if (in_array(self::ALL, $granted, true)) {
            return true;
        }

        return in_array($permission, $granted, true);
    }

    public static function isCoordinator(?User $user): bool
    {
        return $user && $user->role === self::CENTRE_COORDINATOR;
    }

    public static function payload(User $user): array
    {
        $permissions = self::forRole($user->role);
        if (in_array(self::ALL, $permissions, true)) {
            $permissions = array_values(array_unique(array_merge(
                ...array_values(self::ROLE_PERMISSIONS)
            )));
            $permissions = array_values(array_filter(
                $permissions,
                fn ($item) => $item !== self::ALL
            ));
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'study_centre' => $user->study_centre,
            'is_active' => (bool) $user->is_active,
            'permissions' => $permissions,
        ];
    }
}
