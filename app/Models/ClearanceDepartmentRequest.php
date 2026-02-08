<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClearanceDepartmentRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'clearance_request_id',
        'clearance_department_id',
        'status',
        'reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function clearanceRequest()
    {
        return $this->belongsTo(ClearanceRequest::class);
    }

    public function department()
    {
        return $this->belongsTo(ClearanceDepartment::class, 'clearance_department_id');
    }
}
