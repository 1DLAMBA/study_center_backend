<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClearanceRequest;
use App\Models\PersonalDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
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

    public function handleWebhook(Request $request)
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

        switch ($payType) {

            // ── Complete school fees (₦40,000) ────────────────────────────────
            case 'complete_school_fees':
                Log::info('[Webhook] complete_school_fees — looking up student', [
                    'user_id'   => $userId,
                    'reference' => $reference,
                ]);
                $student = PersonalDetail::where('id', $userId)->first();
                if ($student) {
                    $student->course_paid          = true;
                    $student->has_paid             = true;
                    $student->couse_fee_date       = $reference;
                    $student->course_fee_reference = now();
                    $student->save();
                    Log::info('[Webhook] complete_school_fees — student UPDATED', [
                        'student_id' => $student->id,
                        'has_paid'   => $student->has_paid,
                        'course_paid'=> $student->course_paid,
                    ]);
                    return response()->json(['status' => 'success']);
                }
                Log::error('[Webhook] complete_school_fees — student NOT FOUND', [
                    'user_id'   => $userId,
                    'reference' => $reference,
                ]);
                return response()->json(['status' => 'error', 'message' => 'Student not found']);

            // ── 60% partial school fees (₦24,000) ────────────────────────────
            case 'partial_school_fees':
                Log::info('[Webhook] partial_school_fees — looking up student', [
                    'user_id'   => $userId,
                    'reference' => $reference,
                ]);
                $student = PersonalDetail::where('id', $userId)->first();
                if ($student) {
                    $student->has_paid             = true;
                    $student->couse_fee_date       = $reference;
                    $student->course_fee_reference = now();
                    $student->save();
                    Log::info('[Webhook] partial_school_fees — student UPDATED', [
                        'student_id' => $student->id,
                        'has_paid'   => $student->has_paid,
                    ]);
                    return response()->json(['status' => 'success']);
                }
                Log::error('[Webhook] partial_school_fees — student NOT FOUND', [
                    'user_id'   => $userId,
                    'reference' => $reference,
                ]);
                return response()->json(['status' => 'error', 'message' => 'Student not found']);

            // ── 40% balance completion (₦16,000) ─────────────────────────────
            case 'school_fees_completion':
                Log::info('[Webhook] school_fees_completion — looking up student', [
                    'user_id'   => $userId,
                    'reference' => $reference,
                ]);
                $student = PersonalDetail::where('id', $userId)->first();
                if ($student) {
                    $student->course_paid          = true;
                    $student->couse_fee_date       = $reference;
                    $student->course_fee_reference = now();
                    $student->save();
                    Log::info('[Webhook] school_fees_completion — student UPDATED', [
                        'student_id'  => $student->id,
                        'course_paid' => $student->course_paid,
                    ]);
                    return response()->json(['status' => 'success']);
                }
                Log::error('[Webhook] school_fees_completion — student NOT FOUND', [
                    'user_id'   => $userId,
                    'reference' => $reference,
                ]);
                return response()->json(['status' => 'error', 'message' => 'Student not found']);

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

}
