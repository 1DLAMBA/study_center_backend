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
    $payload = $request->all();

    Log::info('Webhook received', [
        // 'payload' => $request->all(),
        'metadata' => $payload['data']['metadata'] ?? null,
    ]);
    $signature = $request->header('x-paystack-signature');
    $secret = env('PAYSTACK_SECRET_KEY');
    $hash = hash_hmac('sha512', $request->getContent(), $secret);
    Log::info('Webhook received', [
        'signature' => $signature,
        'hash' => $hash,
        'payload' => $request->all(),
    ]);


    switch ($payload['data']['metadata']['pay_type']) {
        case 'complete_school_fees':
            // Handle successful charge
            Log::info('Webhook REACHED HERE', [
                'payload' => $request->all(),
            ]);
            if ($payload['event'] === 'charge.success') {
                $reference = $payload['data']['reference'];
                $email = $payload['data']['customer']['email'];
        
                // Optional: use metadata if you stored app_number or reg_number
                $user_id = $payload['data']['metadata']['id'] ?? null;
        
                // Find student by email or reg number
                $student = PersonalDetail::where('id', $user_id)->first();
                if ($student) {
                    $student->course_paid = true;
                    $student->couse_fee_date = $reference;
                    $student->course_fee_reference = now();
                    $student->has_paid = true;
                    $student->save();
                    return response()->json(['status' => 'success', 'student' => $student]);
                } else {
                    return response()->json(['status' => 'error', 'message' => 'Student not found']);
                }
        
            }
            break;
        case 'partial_school_fees':
            // Handle Partial Payment charge
            Log::info('Webhook Partial Payment HERE', [
                'payload' => $request->all(),
            ]);
            if ($payload['event'] === 'charge.success') {
                $reference = $payload['data']['reference'];
                $email = $payload['data']['customer']['email'];
        
                // Optional: use metadata if you stored app_number or reg_number
                $user_id = $payload['data']['metadata']['id'] ?? null;
        
                // Find student by email or reg number
                $student = PersonalDetail::where('id', $user_id)->first();
                if ($student) {
                    $student->couse_fee_date = $reference;
                    $student->course_fee_reference = now();
                    $student->has_paid = true;
                    $student->save();
                    return response()->json(['status' => 'success', 'student' => $student]);
                } else {
                    return response()->json(['status' => 'error', 'message' => 'Student not found']);
                }
        
            }
            break;
        case 'school_fees_completion':
            // Handle pending charge
            Log::info('Webhook Partial Payment HERE', [
                'payload' => $request->all(),
            ]);
            if ($payload['event'] === 'charge.success') {
                $reference = $payload['data']['reference'];
                $email = $payload['data']['customer']['email'];
        
                // Optional: use metadata if you stored app_number or reg_number
                $user_id = $payload['data']['metadata']['id'] ?? null;
        
                // Find student by email or reg number
                $student = PersonalDetail::where('id', $user_id)->first();
                if ($student) {
                    $student->couse_fee_date = $reference;
                    $student->course_fee_reference = now();
                    $student->course_paid = true;
                    $student->save();
                    return response()->json(['status' => 'success', 'student' => $student]);
                } else {
                    return response()->json(['status' => 'error', 'message' => 'Student not found']);
                }
        
            }
            
            break;
        case 'acceptance_fees':
            // Handle successful charge
            Log::info('Webhook Acceptance Fees HERE', [
                'payload' => $request->all(),
            ]);
            if ($payload['event'] === 'charge.success') {
                $reference = $payload['data']['reference'];
                $email = $payload['data']['customer']['email'];
        
                // Optional: use metadata if you stored app_number or reg_number
                $user_id = $payload['data']['metadata']['id'] ?? null;
        
                // Find student by email or reg number
                $student = PersonalDetail::where('id', $user_id)->first();
                if ($student) {
                    $matricNumber = PersonalDetail::generateMatricNumber($student->course, $student->desired_study_cent);
                    $student->matric_number = $matricNumber;
                    $student->application_number = $matricNumber;
                    $student->application_reference = $reference;
                    // $personalDetail->matric_number = $matricNumber;
                    $student->save();
                    return response()->json(['status' => 'success', 'student' => $student]);
                } else {
                    return response()->json(['status' => 'error', 'message' => 'Student not found']);
                }
        
            }
            break;
        case 'ibbul_acceptance_fees':
            // Handle successful charge
            Log::info('Webhook IBBUL Acceptance Fees HERE', [
                'payload' => $request->all(),
            ]);
            Http::post('http://127.0.0.1:9000/api/paystack/webhook', $payload);
            break;
        case 'clearance_acceptance':
            Log::info('Webhook Clearance Acceptance HERE', [
                'payload' => $request->all(),
            ]);
            if ($payload['event'] === 'charge.success') {
                $reference = $payload['data']['reference'];
                $clearanceRequestId = $payload['data']['metadata']['clearance_request_id'] ?? null;
                $clearanceRequest = ClearanceRequest::find($clearanceRequestId);

                if ($clearanceRequest) {
                    $clearanceRequest->acceptance_paid = true;
                    $clearanceRequest->acceptance_reference = $reference;
                    $clearanceRequest->acceptance_paid_at = now();
                    $clearanceRequest->save();

                    return response()->json([
                        'status' => 'success',
                        'clearance_request' => $clearanceRequest,
                    ]);
                }

                return response()->json(['status' => 'error', 'message' => 'Clearance request not found']);
            }
            break;
        default:
            return response()->json(['status' => 'ignored']);
    }
 

    return response()->json(['status' => 'ignored']);
}

}
