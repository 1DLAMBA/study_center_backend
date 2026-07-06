<?php

namespace App\Services;

use App\Models\PersonalDetail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SchoolFeesGateService
{
    public function __construct(
        private readonly BackupPersonalDetailSyncService $backupSync
    ) {}

    public function isNewIntakeByMatric(?string $matricNumber): bool
    {
        return is_string($matricNumber) && str_contains($matricNumber, '/26/');
    }

    public function isPrimaryFullyPaid(PersonalDetail $student): bool
    {
        return (bool) $student->has_paid && (bool) $student->course_paid;
    }

    /**
     * Backup row has both fee flags set (2024/2025 complete on backup API).
     */
    public function isBackupFullyPaid(int|string|null $userId): bool
    {
        if ($userId === null || $userId === '') {
            return false;
        }

        $baseUrl = config('services.backup_api.base_url', '');
        if ($baseUrl === '') {
            return false;
        }

        $url = "{$baseUrl}/personal-details/{$userId}";

        try {
            $response = Http::timeout(15)->acceptJson()->get($url);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('[SchoolFeesGate] Last-session (backup) API unreachable', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);

            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        $data = $response->json();

        return $this->truthyFlag($data['has_paid'] ?? null)
            && $this->truthyFlag($data['course_paid'] ?? null);
    }

    /**
     * Clearance: backup full when student exists on backup; else primary full.
     * Graduating students are not required to pay 2025/2026 on primary.
     */
    public function canRequestClearance(PersonalDetail $student): bool
    {
        if ($this->isNewIntakeByMatric($student->matric_number)) {
            return $this->isPrimaryFullyPaid($student);
        }

        if ($this->backupSync->existsOnBackup($student->id)) {
            return $this->isBackupFullyPaid($student->id);
        }

        return $this->isPrimaryFullyPaid($student);
    }

    /**
     * Graduand fee rule: one session must be fully paid — either last
     * session (2024/2025, held on the last-session/backup DB via its API)
     * or the current session (2025/2026) on the primary DB.
     */
    public function hasPaidLastOrCurrentSession(PersonalDetail $student): bool
    {
        if ($this->isBackupFullyPaid($student->id)) {
            return true;
        }

        return $this->isPrimaryFullyPaid($student);
    }

    private function truthyFlag(mixed $value): bool
    {
        if ($value === false || $value === 0 || $value === '0' || $value === 'false') {
            return false;
        }

        return $value === true || $value === 1 || $value === '1' || $value === 'true';
    }
}
