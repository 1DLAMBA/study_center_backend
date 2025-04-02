<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BioData extends Model
{
    use HasFactory;

    protected $table = 'bio_data';

    protected $fillable = [
        'application_id',
        'full_name',
        'email',
        'phone_number',
        'gender',
        'date_of_birth',
        'place_of_birth',
        'marital_status',
        'religion',
        'nationality',
        'faculty',
        'department',
        'programme',
        'level',
        'current_semester',
        'current_session',
        'matric_number',
        'mode_of_entry',
        'study_mode',
        'entry_year',
        'program_duration',
        'award_in_view',
        'present_contact_address',
        'permanent_home_address',
        'next_of_kin',
        'next_of_kin_phone_number',
        'next_of_kin_relationship',
        'sponsor_address',
    ];

    /**
     * Define the relationship to the applications table.
     */
    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
