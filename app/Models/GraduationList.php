<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GraduationList extends Model
{
    use HasFactory;

    protected $table = 'graduation_list';

    protected $fillable = [
        'matric_number',
        'name',
        'course',
        'centre',
        'session',
    ];

    /**
     * Excel exports carry stray spaces / mixed case; every lookup and write
     * must go through the same normalisation or list membership checks miss.
     */
    public static function normalizeMatric(?string $matric): ?string
    {
        if ($matric === null) {
            return null;
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', trim($matric)));

        return $normalized === '' ? null : $normalized;
    }

    public static function containsMatric(?string $matric): bool
    {
        $normalized = self::normalizeMatric($matric);

        if ($normalized === null) {
            return false;
        }

        return self::where('matric_number', $normalized)->exists();
    }
}
