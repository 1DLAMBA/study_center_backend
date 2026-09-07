<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CentreScope
{
    /**
     * Canonical centre labels used across admin UI and imports.
     *
     * @return list<string>
     */
    public static function catalogue(): array
    {
        return [
            'Salka',
            'Mokwa',
            'suleja',
            'Kagara',
            'New Bussa',
            'Gulu',
            'Gawu',
            'Doko',
            'Katcha',
            'Rijau',
            'Kontogora',
            'Bida',
            'Patigi',
            'Pandogari',
            'Agaie',
        ];
    }

    public static function normalize(?string $centre): string
    {
        return strtolower(trim((string) $centre));
    }

    public static function matches(?string $a, ?string $b): bool
    {
        $left = self::normalize($a);
        $right = self::normalize($b);

        return $left !== '' && $left === $right;
    }

    public static function applyStudents(Builder $query, ?User $user, ?string $requestedCentre = null): Builder
    {
        $centre = self::effectiveCentre($user, $requestedCentre);

        if ($centre !== null) {
            $query->whereRaw('LOWER(TRIM(desired_study_cent)) = ?', [self::normalize($centre)]);
        }

        return $query;
    }

    public static function applyGraduation(Builder $query, ?User $user, ?string $requestedCentre = null): Builder
    {
        $centre = self::effectiveCentre($user, $requestedCentre);

        if ($centre !== null) {
            $query->whereRaw('LOWER(TRIM(centre)) = ?', [self::normalize($centre)]);
        }

        return $query;
    }

    public static function applyClearances(Builder $query, ?User $user, ?string $requestedCentre = null): Builder
    {
        $centre = self::effectiveCentre($user, $requestedCentre);

        if ($centre !== null) {
            $normalized = self::normalize($centre);
            $query->whereHas('personalDetail', function (Builder $student) use ($normalized) {
                $student->whereRaw('LOWER(TRIM(desired_study_cent)) = ?', [$normalized]);
            });
        }

        return $query;
    }

    /**
     * Coordinators are always locked to their centre. College-wide roles
     * may optionally filter by a requested centre.
     */
    public static function effectiveCentre(?User $user, ?string $requestedCentre = null): ?string
    {
        if (StaffPermissions::isCoordinator($user) && $user?->study_centre) {
            return $user->study_centre;
        }

        $requested = trim((string) $requestedCentre);

        return $requested === '' ? null : $requested;
    }

    public static function assertStudentCentre(?User $user, ?string $studentCentre): void
    {
        if (! StaffPermissions::isCoordinator($user)) {
            return;
        }

        if (! self::matches($user?->study_centre, $studentCentre)) {
            abort(403, 'This student is not in your study centre.');
        }
    }

    public static function assertGraduationCentre(?User $user, ?string $rowCentre): void
    {
        if (! StaffPermissions::isCoordinator($user)) {
            return;
        }

        if (! self::matches($user?->study_centre, $rowCentre)) {
            abort(403, 'This graduation-list row is not in your study centre.');
        }
    }
}
