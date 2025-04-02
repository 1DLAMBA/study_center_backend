<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BioRegistration extends Model
{
    use HasFactory;

    protected $table = 'bio_registration';

    protected $fillable = [
        'application_number',
        'next_of_kin',
        'sponsor_address',
        'next_of_kin_relationship',
        'next_of_kin_phone_number',
        'next_of_kin_address',
        'nationality',
        'level',
        'mode_of_entry',
        'session',
        'subject_combination',
    ];

    // Relationship with PersonalDetails model
    public function personalDetails()
    {
        return $this->belongsTo(PersonalDetail::class, 'id', 'application_number');
    }
}
