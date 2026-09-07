<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    public const TYPE_CODES = [
        'registration_fees' => 'REG',
        'acceptance_fees' => 'ACC',
        'complete_school_fees' => 'SCH',
        'partial_school_fees' => 'SCH',
        'school_fees_completion' => 'SCH',
        'clearance_acceptance' => 'CLR',
        'ibbul_acceptance_fees' => 'IBB',
    ];

    protected $fillable = [
        'reference',
        'pay_type',
        'amount',
        'status',
        'personal_detail_id',
        'clearance_request_id',
        'metadata',
        'verified_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'verified_at' => 'datetime',
    ];

    public function personalDetail()
    {
        return $this->belongsTo(PersonalDetail::class, 'personal_detail_id');
    }

    public function clearanceRequest()
    {
        return $this->belongsTo(ClearanceRequest::class, 'clearance_request_id');
    }

    /**
     * Generate a short, human-readable, unique reference such as COE-REG-260906-4F7K2A.
     * Passed to Paystack as the transaction reference itself, so the reference students
     * quote and the reference admins verify by are always the same string.
     */
    public static function generateReference(string $payType): string
    {
        // Excludes 0/O/1/I/L — read aloud over the phone to a bursar without ambiguity.
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $code = self::TYPE_CODES[$payType] ?? 'PAY';
        $date = now()->format('ymd');

        do {
            $suffix = '';
            for ($i = 0; $i < 6; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $reference = "COE-{$code}-{$date}-{$suffix}";
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }
}
