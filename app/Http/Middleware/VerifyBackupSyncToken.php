<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyBackupSyncToken
{
    /**
     * When X-Backup-Sync-Token is sent, it must match BACKUP_API_SYNC_TOKEN.
     * Requests without the header pass through (existing public API behaviour).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('X-Backup-Sync-Token');
        if ($header === null || $header === '') {
            return $next($request);
        }

        $expected = config('services.backup_api.sync_token', '');
        if ($expected === '' || ! hash_equals((string) $expected, (string) $header)) {
            return response()->json(['message' => 'Invalid backup sync token'], 403);
        }

        return $next($request);
    }
}
