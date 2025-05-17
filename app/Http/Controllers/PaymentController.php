<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PersonalDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    //

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
        default:
            return response()->json(['status' => 'ignored']);
    }
 

    return response()->json(['status' => 'ignored']);
}

}
