<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'actor_id',
        'actor_name',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Record one audit entry. Never throws — a logging failure must not break
     * the action it's describing.
     */
    public static function record(
        ?User $actor,
        string $action,
        string $description,
        ?string $subjectType = null,
        int|string|null $subjectId = null,
        array $metadata = []
    ): void {
        try {
            self::create([
                'actor_id' => $actor?->id,
                'actor_name' => $actor?->name,
                'action' => $action,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'description' => $description,
                'metadata' => $metadata ?: null,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[AuditLog] Failed to record entry', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
