<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClearanceRequest;
use App\Models\Payment;
use App\Models\PersonalDetail;
use App\Services\BackupPersonalDetailSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Create a pending Payment row and hand back the reference the frontend should pass
     * to Paystack as the transaction reference itself — so the reference a student is
     * given and the reference an admin looks up are always the same string.
     */
    public function initiate(Request $request)
    {
        $validated = $request->validate([
            'pay_type' => ['required', 'string'],
            'amount' => ['nullable', 'integer'],
            'personal_detail_id' => ['nullable', 'integer'],
            'clearance_request_id' => ['nullable', 'integer'],
            'metadata' => ['nullable', 'array'],
        ]);

        $payment = Payment::create([
            'reference' => Payment::generateReference($validated['pay_type']),
            'pay_type' => $validated['pay_type'],
            'amount' => $validated['amount'] ?? null,
            'status' => Payment::STATUS_PENDING,
            'personal_detail_id' => $validated['personal_detail_id'] ?? null,
            'clearance_request_id' => $validated['clearance_request_id'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
        ]);

        return response()->json(['reference' => $payment->reference]);
    }

    /**
     * Admin: search/list payments for reconciliation.
     */
    public function adminIndex(Request $request)
    {
        $query = Payment::query()->with(['personalDetail', 'clearanceRequest']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('personalDetail', function ($pd) use ($search) {
                        $pd->where('matric_number', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%")
                            ->orWhere('surname', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($payType = $request->query('pay_type')) {
            $query->where('pay_type', $payType);
        }

        $payments = $query->orderByDesc('created_at')->paginate(20);

        return response()->json($payments);
    }

    /**
     * Admin: full detail for one payment by reference.
     */
    public function adminShow(string $reference)
    {
        $payment = Payment::with(['personalDetail', 'clearanceRequest'])
            ->where('reference', $reference)
            ->first();

        if (! $payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        return response()->json($payment);
    }

    /**
     * Admin: re-verify a payment against Paystack directly and sync our local status.
     */
    public function adminReverify(string $reference)
    {
        $payment = Payment::where('reference', $reference)->first();
        if (! $payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        $secret = env('PAYSTACK_SECRET_KEY');
        if (empty($secret)) {
            return response()->json(['status' => false, 'message' => 'Verification not configured'], 500);
        }

        $response = Http::withToken($secret)
            ->get("https://api.paystack.co/transaction/verify/{$reference}");
        $data = $response->json();

        if (! $response->successful() || ! ($data['status'] ?? false)) {
            return response()->json($data ?: ['status' => false, 'message' => 'Verification failed'], 400);
        }

        $txData = $data['data'] ?? [];
        $payment->status = ($txData['status'] ?? null) === 'success' ? Payment::STATUS_SUCCESS : Payment::STATUS_FAILED;
        $payment->amount = $txData['amount'] ?? $payment->amount;
        $payment->verified_at = now();
        $payment->save();

        return response()->json(['payment' => $payment, 'paystack' => $data]);
    }

    /**
     * Verify a Paystack transaction by reference (server-side; keeps secret key secure).
     */
    public function verifyTransaction(string $reference)
    {
        $secret = env('PAYSTACK_SECRET_KEY');
        if (empty($secret)) {
            return response()->json(['status' => false, 'message' => 'Verification not configured'], 500);
        }

        $response = Http::withToken($secret)
            ->get("https://api.paystack.co/transaction/verify/{$reference}");

        $data = $response->json();
        if (! $response->successful() || ! ($data['status'] ?? false)) {
            return response()->json($data ?: ['status' => false, 'message' => 'Verification failed'], 400);
        }

        return response()->json($data);
    }

    public function handleWebhook(Request $request, BackupPersonalDetailSyncService $backupSync)
    {
        $secret = env('PAYSTACK_SECRET_KEY');
        $signature = $request->header('x-paystack-signature');
        $hash = hash_hmac('sha512', $request->getContent(), $secret);

        // ── STEP 1: Signature validation ──────────────────────────────────────
        if ($signature !== $hash) {
            Log::warning('[Webhook] SIGNATURE MISMATCH — request rejected', [
                'expected' => $hash,
                'received' => $signature,
                'ip'       => $request->ip(),
            ]);
            return response()->json(['status' => 'invalid signature'], 401);
        }

        $payload   = $request->all();
        $event     = $payload['event'] ?? 'unknown';
        $reference = $payload['data']['reference'] ?? 'unknown';
        $payType   = $payload['data']['metadata']['pay_type'] ?? null;
        $userId    = $payload['data']['metadata']['id'] ?? null;

        $allowedFeeSessions = ['2024/2025', '2025/2026'];
        $rawFeeSession      = $payload['data']['metadata']['fee_session'] ?? null;
        $feeSession         = in_array($rawFeeSession, $allowedFeeSessions, true) ? $rawFeeSession : null;
        $schoolFeePayTypes  = ['complete_school_fees', 'partial_school_fees', 'school_fees_completion'];

        if (in_array($payType, $schoolFeePayTypes, true) && $feeSession === null) {
            Log::warning('[Webhook] Missing or invalid fee_session for school-fee pay_type', [
                'pay_type'   => $payType,
                'received'   => $rawFeeSession,
                'reference'  => $reference,
            ]);
        }

        // ── STEP 2: Log every verified incoming webhook ────────────────────────
        Log::info('[Webhook] Received', [
            'event'     => $event,
            'pay_type'  => $payType,
            'reference' => $reference,
            'user_id'   => $userId,
        ]);

        // ── STEP 3: Guard — pay_type must exist ───────────────────────────────
        if (!$payType) {
            Log::warning('[Webhook] Missing pay_type — ignoring', [
                'event'     => $event,
                'reference' => $reference,
            ]);
            return response()->json(['status' => 'ignored']);
        }

        // ── STEP 4: Only process confirmed payments ───────────────────────────
        if ($event !== 'charge.success') {
            Log::info('[Webhook] Event is not charge.success — ignoring', [
                'event'    => $event,
                'pay_type' => $payType,
            ]);
            return response()->json(['status' => 'ignored']);
        }

        // ── STEP 5: Record/confirm the payment for admin reconciliation ────────
        // Upserts by reference so this works whether the reference was generated by
        // our own /payments/initiate endpoint or auto-generated by Paystack (legacy
        // transactions, or any flow that hasn't been updated to call initiate yet).
        // Wrapped so a failure here never blocks the pay_type side effects below.
        try {
            Payment::updateOrCreate(
                ['reference' => $reference],
                [
                    'pay_type' => $payType,
                    'amount' => $payload['data']['amount'] ?? null,
                    'status' => Payment::STATUS_SUCCESS,
                    'personal_detail_id' => $payType !== 'clearance_acceptance' && is_numeric($userId) ? $userId : null,
                    'clearance_request_id' => $payType === 'clearance_acceptance'
                        ? ($payload['data']['metadata']['clearance_request_id'] ?? null)
                        : null,
                    'metadata' => $payload['data']['metadata'] ?? null,
                    'verified_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            Log::error('[Webhook] Failed to record Payment row', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);
        }

        switch ($payType) {

            case 'complete_school_fees':
            case 'partial_school_fees':
            case 'school_fees_completion':
                Log::info("[Webhook] {$payType} — looking up student", [
                    'user_id'     => $userId,
                    'reference'   => $reference,
                    'fee_session' => $feeSession,
                ]);
                $student = PersonalDetail::where('id', $userId)->first();
                if (! $student) {
                    Log::error("[Webhook] {$payType} — student NOT FOUND", [
                        'user_id'   => $userId,
                        'reference' => $reference,
                    ]);

                    return response()->json(['status' => 'error', 'message' => 'Student not found']);
                }

                $this->applySchoolFeePayment($student, $userId, $payType, $feeSession, $reference, $backupSync);

                Log::info("[Webhook] {$payType} — processed", [
                    'student_id'           => $student->id,
                    'fee_session'          => $feeSession,
                    'has_paid'             => $student->has_paid,
                    'course_paid'          => $student->course_paid,
                    'fee_academic_session' => $student->fee_academic_session,
                ]);

                return response()->json(['status' => 'success']);

            // ── Acceptance fee (₦3,000) — generates matric number ─────────────
            case 'acceptance_fees':
                Log::info('[Webhook] acceptance_fees — looking up student', [
                    'user_id'   => $userId,
                    'reference' => $reference,
                ]);
                $student = PersonalDetail::where('id', $userId)->first();
                if ($student) {
                    $matricNumber                  = PersonalDetail::generateMatricNumber($student->course, $student->desired_study_cent);
                    $student->matric_number        = $matricNumber;
                    $student->application_number   = $matricNumber;
                    $student->application_reference = $reference;
                    $student->save();
                    Log::info('[Webhook] acceptance_fees — matric number ASSIGNED', [
                        'student_id'   => $student->id,
                        'matric_number'=> $matricNumber,
                    ]);
                    return response()->json(['status' => 'success']);
                }
                Log::error('[Webhook] acceptance_fees — student NOT FOUND', [
                    'user_id'   => $userId,
                    'reference' => $reference,
                ]);
                return response()->json(['status' => 'error', 'message' => 'Student not found']);

            // ── IBBUL acceptance fee — forwarded to secondary service ─────────
            case 'ibbul_acceptance_fees':
                Log::info('[Webhook] ibbul_acceptance_fees — forwarding to port 9000', [
                    'reference' => $reference,
                ]);
                Http::post('http://127.0.0.1:9000/api/paystack/webhook', $payload);
                Log::info('[Webhook] ibbul_acceptance_fees — forwarded');
                return response()->json(['status' => 'forwarded']);

            // ── Clearance acceptance fee ───────────────────────────────────────
            case 'clearance_acceptance':
                $clearanceRequestId = $payload['data']['metadata']['clearance_request_id'] ?? null;
                Log::info('[Webhook] clearance_acceptance — looking up clearance request', [
                    'clearance_request_id' => $clearanceRequestId,
                    'reference'            => $reference,
                ]);
                $clearanceRequest = ClearanceRequest::find($clearanceRequestId);
                if ($clearanceRequest) {
                    $clearanceRequest->acceptance_paid      = true;
                    $clearanceRequest->acceptance_reference = $reference;
                    $clearanceRequest->acceptance_paid_at   = now();
                    $clearanceRequest->save();
                    Log::info('[Webhook] clearance_acceptance — clearance request UPDATED', [
                        'clearance_request_id' => $clearanceRequestId,
                    ]);
                    return response()->json(['status' => 'success']);
                }
                Log::error('[Webhook] clearance_acceptance — clearance request NOT FOUND', [
                    'clearance_request_id' => $clearanceRequestId,
                    'reference'            => $reference,
                ]);
                return response()->json(['status' => 'error', 'message' => 'Clearance request not found']);

            // ── Registration fee — frontend handles data save, no DB action ───
            case 'registration_fees':
                Log::info('[Webhook] registration_fees — no-op (frontend POSTs data directly)', [
                    'reference' => $reference,
                ]);
                return response()->json(['status' => 'success']);

            default:
                Log::warning('[Webhook] Unrecognised pay_type — ignoring', [
                    'pay_type'  => $payType,
                    'reference' => $reference,
                ]);
                return response()->json(['status' => 'ignored']);
        }
    }

    /**
     * 2024/2025 fees for students on the backup DB update backup only — not primary payment flags.
     */
    private function applySchoolFeePayment(
        PersonalDetail $student,
        int|string|null $userId,
        string $payType,
        ?string $feeSession,
        string $reference,
        BackupPersonalDetailSyncService $backupSync
    ): void {
        $isLastSession = $feeSession === BackupPersonalDetailSyncService::LAST_FEE_SESSION;
        $onBackup      = $isLastSession && $backupSync->existsOnBackup($userId);

        if ($onBackup && $backupSync->sync($userId, $payType, $feeSession, $reference)) {
            Log::info('[Webhook] School fee applied on backup only (primary flags unchanged)', [
                'user_id'     => $userId,
                'pay_type'    => $payType,
                'fee_session' => $feeSession,
            ]);

            return;
        }

        if ($payType === 'complete_school_fees' || $payType === 'school_fees_completion') {
            $student->has_paid    = true;
            $student->course_paid = true;
        } elseif ($payType === 'partial_school_fees') {
            $student->has_paid = true;
        }

        $student->couse_fee_date       = $reference;
        $student->course_fee_reference = now();
        if ($feeSession !== null) {
            $student->fee_academic_session = $feeSession;
        }
        $student->save();

        if ($isLastSession) {
            $backupSync->sync($userId, $payType, $feeSession, $reference);
        }
    }

}
