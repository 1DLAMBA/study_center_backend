<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_number',
        'first_school',
        'first_course',
        'p_school_name_1',
        'p_school_from_1',
        'p_school_to_1',
        'p_school_name_2',
        'p_school_from_2',
        'p_school_to_2',
        's_school_name_1',
        's_school_from_1',
        's_school_to_1',
        's_school_name_2',
        's_school_from_2',
        's_school_to_2',
        'second_school',
        'second_course',
    ];

    /**
     * Relationship with PersonalDetail.
     */
    public function personalDetail()
    {
        return $this->hasOne(PersonalDetail::class, 'ud', 'application_number');
    }
}
