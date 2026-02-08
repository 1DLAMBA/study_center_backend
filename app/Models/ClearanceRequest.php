<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClearanceRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'personal_detail_id',
        'matric_number',
        'status',
        'fees_receipt_path',
        'rejection_reason',
        'approved_at',
        'rejected_at',
        'acceptance_paid',
        'acceptance_reference',
        'acceptance_paid_at',
    ];

    protected $casts = [
        'acceptance_paid' => 'boolean',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'acceptance_paid_at' => 'datetime',
    ];

    public function personalDetail()
    {
        return $this->belongsTo(PersonalDetail::class, 'personal_detail_id');
    }

    public function departmentRequests()
    {
        return $this->hasMany(ClearanceDepartmentRequest::class);
    }
}
