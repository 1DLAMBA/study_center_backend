<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BackupPersonalDetailSyncService
{
    public const LAST_FEE_SESSION = '2024/2025';

    public function shouldSync(?string $feeSession): bool
    {
        return $feeSession === self::LAST_FEE_SESSION;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(string $payType, string $reference): array
    {
        $payload = [
            'couse_fee_date'       => $reference,
            'course_fee_reference' => now()->toDateString(),
        ];

        return match ($payType) {
            'complete_school_fees' => array_merge($payload, [
                'has_paid'    => true,
                'course_paid' => true,
            ]),
            'partial_school_fees' => array_merge($payload, [
                'has_paid' => true,
            ]),
            'school_fees_completion' => array_merge($payload, [
                'has_paid'    => true,
                'course_paid' => true,
            ]),
            default => [],
        };
    }

    public function existsOnBackup(int|string|null $userId): bool
    {
        if ($userId === null || $userId === '') {
            return false;
        }

        $baseUrl = config('services.backup_api.base_url', '');
        if ($baseUrl === '') {
            return false;
        }

        $url = "{$baseUrl}/personal-details/{$userId}";

        return Http::timeout(15)->acceptJson()->get($url)->successful();
    }

    public function sync(int|string|null $userId, string $payType, ?string $feeSession, string $reference): bool
    {
        if ($userId === null || $userId === '') {
            Log::warning('[BackupSync] Skipped — missing user id', [
                'pay_type'    => $payType,
                'fee_session' => $feeSession,
            ]);

            return false;
        }

        if (! $this->shouldSync($feeSession)) {
            return false;
        }

        $baseUrl = config('services.backup_api.base_url', '');
        if ($baseUrl === '') {
            Log::info('[BackupSync] Skipped — BACKUP_API_BASE_URL not configured', [
                'user_id' => $userId,
            ]);

            return false;
        }

        $body = $this->buildPayload($payType, $reference);
        if ($body === []) {
            Log::warning('[BackupSync] Skipped — unknown pay_type', [
                'pay_type' => $payType,
                'user_id'  => $userId,
            ]);

            return false;
        }

        $url = "{$baseUrl}/personal-details/{$userId}";

        $existsResponse = Http::timeout(15)->acceptJson()->get($url);
        if ($existsResponse->status() === 404) {
            Log::warning('[BackupSync] Skipped — student not found on backup API', [
                'user_id'     => $userId,
                'fee_session' => $feeSession,
            ]);

            return false;
        }

        if (! $existsResponse->successful()) {
            Log::error('[BackupSync] Backup GET failed before sync', [
                'user_id'  => $userId,
                'status'   => $existsResponse->status(),
                'body'     => $existsResponse->body(),
            ]);

            return false;
        }

        $request = Http::timeout(15)->acceptJson();
        $token   = config('services.backup_api.sync_token');
        if (! empty($token)) {
            $request = $request->withHeaders(['X-Backup-Sync-Token' => $token]);
        }

        $response = $request->put($url, $body);

        if ($response->successful()) {
            Log::info('[BackupSync] Backup personal-details updated', [
                'user_id'     => $userId,
                'pay_type'    => $payType,
                'fee_session' => $feeSession,
            ]);

            return true;
        }

        Log::error('[BackupSync] Backup PUT failed', [
            'user_id'     => $userId,
            'pay_type'    => $payType,
            'fee_session' => $feeSession,
            'status'      => $response->status(),
            'body'        => $response->body(),
        ]);

        return false;
    }
}
